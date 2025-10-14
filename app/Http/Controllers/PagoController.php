<?php

namespace App\Http\Controllers;

use App\Services\WompiService;
use App\Models\Pago;
use App\Models\Venta;
use App\Models\Reserva;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Auth;
use Exception;

class PagoController extends Controller
{
    protected $wompiService;

    public function __construct(WompiService $wompiService)
    {
        $this->wompiService = $wompiService;
    }

    /**
     * Obtener token de aceptación de Wompi
     */
    public function getAcceptanceToken()
    {
        $result = $this->wompiService->getAcceptanceToken();

        if ($result['success']) {
            return response()->json([
                'success' => true,
                'acceptance_token' => $result['acceptance_token']
            ]);
        }

        return response()->json([
            'success' => false,
            'message' => $result['error']
        ], 400);
    }

    /**
     * Obtener configuración pública de Wompi para el frontend
     */
    public function getPublicConfig()
    {
        return response()->json([
            'public_key' => config('services.wompi.public_key'),
            'widget_url' => config('services.wompi.widget_url'),
            'sandbox' => config('services.wompi.sandbox'),
            'client_id' => config('services.wompi.client_id')
        ]);
    }

    /**
     * Crear enlace de pago para una venta
     */
    public function createPaymentLink(Request $request)
    {
        try {
            $validated = $request->validate([
                'venta_id' => 'required|exists:ventas,id',
                'customer_email' => 'required|email',
                'customer_name' => 'string|nullable'
            ]);

            $venta = Venta::findOrFail($validated['venta_id']);

            // Crear enlace de pago con Wompi
            $paymentData = [
                'amount_in_cents' => $venta->total * 100, // Convertir a centavos
                'description' => "Venta #{$venta->id} - Productos Vasir",
                'reference' => "VENTA-{$venta->id}-" . time(),
                'customer_email' => $validated['customer_email'],
                'customer_name' => $validated['customer_name'] ?? 'Cliente'
            ];

            $result = $this->wompiService->createPaymentLink($paymentData);

            if ($result['success']) {
                return response()->json([
                    'success' => true,
                    'payment_link' => $result['payment_link'],
                    'link_id' => $result['link_id'],
                    'reference' => $result['reference']
                ]);
            }

            return response()->json([
                'success' => false,
                'message' => $result['error']
            ], 400);

        } catch (Exception $e) {
            Log::error('Error creando enlace de pago', [
                'error' => $e->getMessage(),
                'venta_id' => $request->venta_id
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Error interno del servidor'
            ], 500);
        }
    }

    /**
     * Crear venta desde carrito de compras (público)
     */
    public function createVentaFromCarrito(Request $request)
    {
        Log::info('🛒 Iniciando createVentaFromCarrito', [
            'request_data' => $request->all(),
            'user_id' => Auth::id(),
            'is_authenticated' => Auth::check()
        ]);

        $validator = Validator::make($request->all(), [
            'productos' => 'required|array|min:1',
            'productos.*.id' => 'required|exists:productos,id',
            'productos.*.cantidad' => 'required|integer|min:1',
            'productos.*.precio' => 'required|numeric|min:0',
            'productos.*.nombre' => 'required|string',
            'customer_email' => 'required|email',
        ]);

        if ($validator->fails()) {
            Log::warning('🛒 Validación fallida en createVentaFromCarrito', [
                'errors' => $validator->errors()->toArray(),
                'request_data' => $request->all()
            ]);

            return response()->json([
                'success' => false,
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            DB::beginTransaction();

            // Verificar que el usuario esté autenticado
            $user = Auth::user();
            if (!$user) {
                return response()->json([
                    'success' => false,
                    'message' => 'Usuario no autenticado'
                ], 401);
            }

            // Obtener o crear cliente asociado al usuario
            $cliente = \App\Models\Cliente::where('user_id', $user->id)->first();

            Log::info('🧑‍💼 Verificando cliente', [
                'user_id' => $user->id,
                'user_email' => $user->email,
                'user_name' => $user->name,
                'cliente_exists' => !!$cliente,
                'cliente_data' => $cliente ? $cliente->toArray() : null
            ]);

            if (!$cliente) {
                Log::warning('❌ Usuario sin cliente asociado - Creando automáticamente', [
                    'user_id' => $user->id,
                    'user_email' => $user->email
                ]);

                // Crear cliente automáticamente
                try {
                    // Obtener tipo de documento por defecto (cédula, etc.)
                    $tipoDocumentoDefault = \App\Models\TipoDocumento::first();

                    if (!$tipoDocumentoDefault) {
                        // Crear tipo de documento si no existe ninguno
                        $tipoDocumentoDefault = \App\Models\TipoDocumento::create([
                            'nombre' => 'Cédula de Identidad'
                        ]);
                    }

                    $cliente = \App\Models\Cliente::create([
                        'user_id' => $user->id,
                        'numero_identificacion' => $user->email, // Temporal
                        'fecha_nacimiento' => now()->subYears(25), // Fecha por defecto
                        'genero' => 'No especificado',
                        'direccion' => 'No especificada',
                        'telefono' => 'No especificado',
                        'tipo_documento_id' => $tipoDocumentoDefault->id
                    ]);

                    Log::info('✅ Cliente creado automáticamente', [
                        'cliente_id' => $cliente->id,
                        'user_id' => $user->id
                    ]);

                } catch (Exception $e) {
                    Log::error('❌ Error creando cliente automáticamente', [
                        'user_id' => $user->id,
                        'error' => $e->getMessage()
                    ]);

                    return response()->json([
                        'success' => false,
                        'message' => 'Error creando información de cliente: ' . $e->getMessage()
                    ], 500);
                }
            }            // Verificar stock de todos los productos
            foreach ($request->productos as $item) {
                $producto = \App\Models\Producto::find($item['id']);
                if (!$producto) {
                    return response()->json([
                        'success' => false,
                        'message' => "Producto con ID {$item['id']} no encontrado"
                    ], 400);
                }

                if ($producto->stock_actual < $item['cantidad']) {
                    return response()->json([
                        'success' => false,
                        'message' => "Stock insuficiente para {$producto->nombre}. Disponible: {$producto->stock_actual}, Requerido: {$item['cantidad']}"
                    ], 400);
                }
            }

            // Obtener método de pago por defecto (tarjeta de crédito/Wompi)
            $metodoPago = \App\Models\MetodoPago::where('metodo_pago', 'like', '%tarjeta%')
                ->orWhere('metodo_pago', 'like', '%wompi%')
                ->orWhere('metodo_pago', 'like', '%credito%')
                ->first();

            if (!$metodoPago) {
                // Crear método de pago si no existe
                $metodoPago = \App\Models\MetodoPago::create([
                    'metodo_pago' => 'Tarjeta de Crédito - Wompi'
                ]);
            }

            // Obtener empleado por defecto (puede ser un empleado sistema)
            $empleado = \App\Models\Empleado::first();
            if (!$empleado) {
                return response()->json([
                    'success' => false,
                    'message' => 'No se encontró empleado para procesar la venta'
                ], 400);
            }

            // Crear la venta
            $venta = Venta::create([
                'fecha' => now(),
                'cliente_id' => $cliente->id,
                'empleado_id' => $empleado->id,
                'metodo_pago_id' => $metodoPago->id,
                'estado' => 'pendiente',
                'total' => 0
            ]);

            $total = 0;

            // Crear detalles de venta
            foreach ($request->productos as $item) {
                $subtotal = $item['cantidad'] * $item['precio'];

                $venta->detalleVentas()->create([
                    'producto_id' => $item['id'],
                    'cantidad' => $item['cantidad'],
                    'precio_unitario' => $item['precio'],
                    'subtotal' => $subtotal
                ]);

                $total += $subtotal;
            }

            // Actualizar total de la venta
            $venta->update(['total' => $total]);

            DB::commit();

            return response()->json([
                'success' => true,
                'venta' => $venta->load(['cliente', 'empleado', 'metodoPago', 'detalleVentas.producto']),
                'message' => 'Venta creada exitosamente desde carrito'
            ]);

        } catch (Exception $e) {
            DB::rollBack();
            Log::error('Error creando venta desde carrito', [
                'error' => $e->getMessage(),
                'productos' => $request->productos
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Error interno del servidor: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Procesar pago para una venta
     */
    public function procesarPagoVenta(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'venta_id' => 'required|exists:ventas,id',
            'token' => 'required|string',
            'customer_email' => 'required|email',
            'acceptance_token' => 'required|string',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            DB::beginTransaction();

            // Obtener la venta
            $venta = Venta::with(['cliente', 'detalleVentas'])->findOrFail($request->venta_id);

            // Verificar que la venta esté pendiente
            if ($venta->estado !== 'pendiente') {
                return response()->json([
                    'success' => false,
                    'message' => 'La venta ya fue procesada o cancelada'
                ], 400);
            }

            // Generar referencia única
            $reference = $this->wompiService->generateReference('VENTA');

            // Convertir el total a centavos
            $amountInCents = $this->wompiService->convertTocents($venta->total);

            // Crear registro de pago
            $pago = Pago::create([
                'venta_id' => $venta->id,
                'monto' => $venta->total,
                'moneda' => 'COP',
                'referencia_wompi' => $reference,
                'estado' => 'pending',
                'metodo_pago' => 'tarjeta_credito',
                'email_cliente' => $request->customer_email
            ]);

            // Procesar transacción con Wompi
            $transactionData = [
                'amount_in_cents' => $amountInCents,
                'currency' => 'COP',
                'customer_email' => $request->customer_email,
                'payment_source_token' => $request->token,
                'reference' => $reference,
                'redirect_url' => url('/payment/success')
            ];

            $result = $this->wompiService->processTransaction($transactionData);

            if (!$result['success']) {
                $pago->update([
                    'estado' => 'failed',
                    'mensaje_error' => $result['error']
                ]);

                DB::rollBack();

                return response()->json([
                    'success' => false,
                    'message' => $result['error']
                ], 400);
            }

            // Actualizar pago con información de Wompi
            $wompiData = $result['data'];
            $pago->update([
                'wompi_transaction_id' => $wompiData['id'],
                'estado' => strtolower($wompiData['status']),
                'wompi_reference' => $wompiData['reference'],
                'response_data' => json_encode($wompiData)
            ]);

            // Si el pago fue aprobado, actualizar la venta
            if (strtolower($wompiData['status']) === 'approved') {
                $venta->update(['estado' => 'completada']);
            }

            DB::commit();

            return response()->json([
                'success' => true,
                'pago' => $pago,
                'wompi_data' => $wompiData,
                'message' => 'Pago procesado exitosamente'
            ]);

        } catch (Exception $e) {
            DB::rollBack();
            Log::error('Error procesando pago para venta', [
                'venta_id' => $request->venta_id,
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Error interno del servidor'
            ], 500);
        }
    }

    /**
     * Procesar pago para una reserva
     */
    public function procesarPagoReserva(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'reserva_id' => 'required|exists:reservas,id',
            'token' => 'required|string',
            'customer_email' => 'required|email',
            'acceptance_token' => 'required|string',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            DB::beginTransaction();

            // Obtener la reserva
            $reserva = Reserva::with(['cliente'])->findOrFail($request->reserva_id);

            // Verificar que la reserva esté pendiente
            if ($reserva->estado !== 'pendiente') {
                return response()->json([
                    'success' => false,
                    'message' => 'La reserva ya fue procesada o cancelada'
                ], 400);
            }

            // Generar referencia única
            $reference = $this->wompiService->generateReference('RESERVA');

            // Convertir el total a centavos
            $amountInCents = $this->wompiService->convertTocents($reserva->total);

            // Crear registro de pago
            $pago = Pago::create([
                'reserva_id' => $reserva->id,
                'monto' => $reserva->total,
                'moneda' => 'COP',
                'referencia_wompi' => $reference,
                'estado' => 'pending',
                'metodo_pago' => 'tarjeta_credito',
                'email_cliente' => $request->customer_email
            ]);

            // Procesar transacción con Wompi
            $transactionData = [
                'amount_in_cents' => $amountInCents,
                'currency' => 'COP',
                'customer_email' => $request->customer_email,
                'payment_source_token' => $request->token,
                'reference' => $reference,
                'redirect_url' => url('/payment/success')
            ];

            $result = $this->wompiService->processTransaction($transactionData);

            if (!$result['success']) {
                $pago->update([
                    'estado' => 'failed',
                    'mensaje_error' => $result['error']
                ]);

                DB::rollBack();

                return response()->json([
                    'success' => false,
                    'message' => $result['error']
                ], 400);
            }

            // Actualizar pago con información de Wompi
            $wompiData = $result['data'];
            $pago->update([
                'wompi_transaction_id' => $wompiData['id'],
                'estado' => strtolower($wompiData['status']),
                'wompi_reference' => $wompiData['reference'],
                'response_data' => json_encode($wompiData)
            ]);

            // Si el pago fue aprobado, actualizar la reserva
            if (strtolower($wompiData['status']) === 'approved') {
                $reserva->update(['estado' => 'confirmada']);
            }

            DB::commit();

            return response()->json([
                'success' => true,
                'pago' => $pago,
                'wompi_data' => $wompiData,
                'message' => 'Pago procesado exitosamente'
            ]);

        } catch (Exception $e) {
            DB::rollBack();
            Log::error('Error procesando pago para reserva', [
                'reserva_id' => $request->reserva_id,
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Error interno del servidor'
            ], 500);
        }
    }

    /**
     * Consultar estado de un pago
     */
    public function consultarEstadoPago($pagoId)
    {
        try {
            $pago = Pago::findOrFail($pagoId);

            if ($pago->wompi_transaction_id) {
                // Consultar estado actual en Wompi
                $result = $this->wompiService->getTransaction($pago->wompi_transaction_id);

                if ($result['success']) {
                    $wompiData = $result['data'];

                    // Actualizar estado local si cambió
                    if (strtolower($wompiData['status']) !== $pago->estado) {
                        $pago->update([
                            'estado' => strtolower($wompiData['status']),
                            'response_data' => json_encode($wompiData)
                        ]);

                        // Actualizar estado de venta/reserva si es necesario
                        if (strtolower($wompiData['status']) === 'approved') {
                            if ($pago->venta_id) {
                                $pago->venta->update(['estado' => 'completada']);
                            }
                            if ($pago->reserva_id) {
                                $pago->reserva->update(['estado' => 'confirmada']);
                            }
                        }
                    }
                }
            }

            return response()->json([
                'success' => true,
                'pago' => $pago->fresh()
            ]);

        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Pago no encontrado'
            ], 404);
        }
    }

    /**
     * Webhook de Wompi para recibir notificaciones de estado
     */
    public function webhook(Request $request)
    {
        try {
            $payload = $request->getContent();
            $signature = $request->header('wompi-signature');
            $timestamp = $request->header('wompi-timestamp');

            // Validar signature del webhook
            if (!$this->wompiService->validateWebhookSignature($payload, $signature, $timestamp)) {
                Log::warning('Webhook de Wompi con signature inválida');
                return response()->json(['error' => 'Invalid signature'], 401);
            }

            $data = json_decode($payload, true);

            Log::info('Webhook recibido de Wompi', $data);

            // Buscar el pago por wompi_transaction_id
            $pago = Pago::where('wompi_transaction_id', $data['data']['transaction']['id'])->first();

            if (!$pago) {
                Log::warning('Webhook para transacción desconocida', ['transaction_id' => $data['data']['transaction']['id']]);
                return response()->json(['error' => 'Transaction not found'], 404);
            }

            // Actualizar estado del pago
            $newStatus = strtolower($data['data']['transaction']['status']);
            $pago->update([
                'estado' => $newStatus,
                'response_data' => json_encode($data['data']['transaction'])
            ]);

            // Actualizar estado de venta/reserva según el resultado
            if ($newStatus === 'approved') {
                if ($pago->venta_id) {
                    $pago->venta->update(['estado' => 'completada']);
                }
                if ($pago->reserva_id) {
                    $pago->reserva->update(['estado' => 'confirmada']);
                }
            } elseif ($newStatus === 'declined' || $newStatus === 'error') {
                if ($pago->venta_id) {
                    $pago->venta->update(['estado' => 'cancelada']);
                }
                if ($pago->reserva_id) {
                    $pago->reserva->update(['estado' => 'cancelada']);
                }
            }

            return response()->json(['status' => 'success']);

        } catch (Exception $e) {
            Log::error('Error procesando webhook de Wompi', [
                'error' => $e->getMessage(),
                'payload' => $request->getContent()
            ]);

            return response()->json(['error' => 'Internal server error'], 500);
        }
    }

    /**
     * Listar pagos (para administración)
     */
    public function index()
    {
        $pagos = Pago::with(['venta.cliente', 'reserva.cliente'])
            ->orderBy('created_at', 'desc')
            ->paginate(20);

        return response()->json($pagos);
    }
}
