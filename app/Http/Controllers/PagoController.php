<?php

namespace App\Http\Controllers;

use App\Services\WompiService;
use App\Models\Pago;
use App\Models\Venta;
use App\Models\Reserva;
use App\Models\StockReservation;
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
            'productos.*.imagen' => 'nullable|string', // Campo opcional para compatibilidad
            'productos.*.subtotal' => 'nullable|numeric|min:0', // Campo opcional para compatibilidad
            'customer_email' => 'required|email',
            // Validaciones para datos del cliente (opcionales)
            'cliente_data' => 'nullable|array',
            'cliente_data.id' => 'nullable|exists:clientes,id',
            'cliente_data.numero_identificacion' => 'nullable|string|max:25',
            'cliente_data.telefono' => 'nullable|string|max:30',
            'cliente_data.direccion' => 'nullable|string|max:200',
            'cliente_data.tipo_documento_id' => 'nullable|exists:tipos_documentos,id',
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
            $clienteDataRequest = $request->input('cliente_data');

            Log::info('🧑‍💼 Verificando cliente', [
                'user_id' => $user->id,
                'user_email' => $user->email,
                'user_name' => $user->name,
                'cliente_exists' => !!$cliente,
                'cliente_data_received' => !!$clienteDataRequest,
                'cliente_data' => $cliente ? $cliente->toArray() : null
            ]);

            // Si se proporcionan datos de cliente y ya existe un cliente, actualizar datos
            if ($cliente && $clienteDataRequest && isset($clienteDataRequest['id']) && $clienteDataRequest['id'] == $cliente->id) {
                Log::info('📝 Actualizando datos del cliente existente', [
                    'cliente_id' => $cliente->id,
                    'nuevos_datos' => $clienteDataRequest
                ]);

                // Actualizar solo los campos que no sean por defecto o vacíos
                $datosActualizacion = [];
                if (!empty($clienteDataRequest['numero_identificacion']) && $clienteDataRequest['numero_identificacion'] !== $user->email) {
                    $datosActualizacion['numero_identificacion'] = $clienteDataRequest['numero_identificacion'];
                }
                if (!empty($clienteDataRequest['telefono']) && $clienteDataRequest['telefono'] !== 'No especificado') {
                    $datosActualizacion['telefono'] = $clienteDataRequest['telefono'];
                }
                if (!empty($clienteDataRequest['direccion']) && $clienteDataRequest['direccion'] !== 'No especificada') {
                    $datosActualizacion['direccion'] = $clienteDataRequest['direccion'];
                }
                if (!empty($clienteDataRequest['tipo_documento_id'])) {
                    $datosActualizacion['tipo_documento_id'] = $clienteDataRequest['tipo_documento_id'];
                }

                if (!empty($datosActualizacion)) {
                    $cliente->update($datosActualizacion);
                    Log::info('✅ Cliente actualizado correctamente', [
                        'cliente_id' => $cliente->id,
                        'datos_actualizados' => $datosActualizacion
                    ]);
                }
            }

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

            // ✅ NUEVO FLUJO: Crear venta como PENDIENTE (sin reducir stock aún)
            $venta = Venta::create([
                'fecha' => now(),
                'cliente_id' => $cliente->id,
                'estado' => 'pendiente', // ⚠️ CAMBIO CRÍTICO: Pendiente hasta confirmar pago
                'total' => 0
            ]);

            $total = 0;

            // Crear detalles de venta SIN reducir stock todavía
            foreach ($request->productos as $item) {
                $producto = \App\Models\Producto::find($item['id']);
                $subtotal = $item['cantidad'] * $item['precio'];

                // Crear detalle de venta
                $venta->detalleVentas()->create([
                    'producto_id' => $item['id'],
                    'cantidad' => $item['cantidad'],
                    'precio_unitario' => $item['precio'],
                    'subtotal' => $subtotal
                ]);

                // 🔄 NO REDUCIR STOCK NI CREAR MOVIMIENTO DE INVENTARIO AÚN
                // Esto se hará cuando se confirme el pago vía webhook

                Log::info('🛍️ Detalle de venta creado (stock no reducido aún)', [
                    'producto_id' => $producto->id,
                    'producto_nombre' => $producto->nombre,
                    'cantidad_solicitada' => $item['cantidad'],
                    'stock_actual' => $producto->stock_actual,
                    'venta_id' => $venta->id,
                    'estado_venta' => 'pendiente'
                ]);

                $total += $subtotal;
            }

            // Actualizar total de la venta
            $venta->update(['total' => $total]);

            DB::commit();

            Log::info('🛍️ Venta creada como PENDIENTE desde carrito', [
                'venta_id' => $venta->id,
                'cliente_id' => $cliente->id,
                'total' => $venta->total,
                'productos_count' => count($request->productos),
                'estado' => 'pendiente',
                'stock_reducido' => 'NO - Pendiente de confirmación de pago'
            ]);

            return response()->json([
                'success' => true,
                'venta' => $venta->load(['cliente', 'detalleVentas.producto']),
                'message' => 'Venta creada como pendiente. El stock se reducirá cuando se confirme el pago.',
                'warning' => 'La venta quedará cancelada automáticamente si no se completa el pago en 30 minutos.'
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

            // Verificar que la venta esté completada (estado único ahora)
            if ($venta->estaCancelada()) {
                return response()->json([
                    'success' => false,
                    'message' => 'La venta fue cancelada'
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

            // ✅ Las ventas ya se crean como 'completada', no necesitan cambio de estado
            if (strtolower($wompiData['status']) === 'approved') {
                Log::info('Pago aprobado - Venta ya está completada', [
                    'venta_id' => $venta->id,
                    'pago_status' => $wompiData['status'],
                    'venta_estado' => $venta->estado
                ]);
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

                        // ✅ Las ventas ya se crean como 'completada'
                        if (strtolower($wompiData['status']) === 'approved') {
                            if ($pago->venta_id) {
                                Log::info('Pago aprobado - Venta ya está completada', [
                                    'venta_id' => $pago->venta_id,
                                    'pago_id' => $pago->id,
                                    'venta_estado' => $pago->venta->estado
                                ]);
                            }
                            if ($pago->reserva_id) {
                                $pago->reserva->update(['estado' => 'confirmada']); // Reservas sí se confirman
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
     * ✅ MEJORADO: Webhook de Wompi con validación robusta y sincronización segura
     */
    public function webhook(Request $request)
    {
        $startTime = microtime(true);
        $requestId = uniqid('webhook_', true);

        // 🚨 LOGGING DIRECTO (backup si Laravel logging falla)
        $logData = [
            'request_id' => $requestId,
            'timestamp' => now()->toDateTimeString(),
            'ip' => $request->ip(),
            'user_agent' => $request->header('User-Agent'),
            'method' => $request->method(),
            'url' => $request->fullUrl(),
            'headers' => $request->headers->all(),
            'raw_content' => $request->getContent()
        ];

        // Logging directo a archivo
        file_put_contents(
            storage_path('logs/webhooks.log'),
            '[' . date('Y-m-d H:i:s') . '] === WEBHOOK RECIBIDO === ' . json_encode($logData) . PHP_EOL,
            FILE_APPEND | LOCK_EX
        );

        // También intentar Laravel logging
        Log::info("=== WEBHOOK RECIBIDO ===", $logData);

        try {
            Log::info("Iniciando procesamiento de webhook", [
                'request_id' => $requestId,
                'ip' => $request->ip(),
                'user_agent' => $request->header('User-Agent')
            ]);

            $payload = $request->getContent();
            $wompiHash = $request->header('wompi_hash');
            $signature = $request->header('wompi-signature');
            $timestamp = $request->header('wompi-timestamp');

            // ✅ VALIDACIÓN HMAC MEJORADA
            if (!$this->validateWompiWebhookSignature($payload, $wompiHash, $signature, $timestamp)) {
                Log::warning('Webhook rechazado por signature inválida', [
                    'request_id' => $requestId,
                    'provided_hash' => $wompiHash,
                    'provided_signature' => $signature
                ]);
                return response()->json(['error' => 'Invalid signature'], 401);
            }

            $data = json_decode($payload, true);

            // 🇸🇻 ADAPTACIÓN PARA WOMPI EL SALVADOR
            if (!$data) {
                Log::error('Webhook con payload inválido', [
                    'request_id' => $requestId,
                    'payload' => $payload
                ]);
                return response()->json(['error' => 'Invalid payload'], 400);
            }

            // Detectar formato: Wompi Colombia vs Wompi El Salvador
            if (isset($data['data']['transaction'])) {
                // Formato Wompi Colombia
                $transactionData = $data['data']['transaction'];
                $transactionId = $transactionData['id'];
                $newStatus = strtolower($transactionData['status']);
                $reference = $transactionData['reference'] ?? null;
            } elseif (isset($data['IdTransaccion']) && isset($data['ResultadoTransaccion'])) {
                // 🇸🇻 Formato Wompi El Salvador
                $transactionId = $data['IdTransaccion'];
                $resultadoTransaccion = $data['ResultadoTransaccion'];
                $reference = $data['IdExterno'] ?? null;

                // Mapear estados de El Salvador a nuestros estados
                $newStatus = match($resultadoTransaccion) {
                    'ExitosaAprobada' => 'approved',
                    'ExitosaRechazada', 'Rechazada', 'Fallida' => 'declined',
                    'Pendiente' => 'pending',
                    default => 'pending'
                };

                Log::info('Webhook Wompi El Salvador detectado', [
                    'request_id' => $requestId,
                    'transaction_id' => $transactionId,
                    'resultado_original' => $resultadoTransaccion,
                    'estado_mapeado' => $newStatus,
                    'reference' => $reference
                ]);
            } else {
                Log::error('Formato de webhook no reconocido', [
                    'request_id' => $requestId,
                    'payload_keys' => array_keys($data)
                ]);
                return response()->json(['error' => 'Invalid payload format'], 400);
            }

            Log::info('Procesando webhook válido', [
                'request_id' => $requestId,
                'transaction_id' => $transactionId,
                'status' => $newStatus,
                'amount' => $transactionData['amount'] ?? null
            ]);

            // ✅ BUSCAR PAGO CON VALIDACIONES MEJORADAS
            $pago = Pago::where('wompi_transaction_id', $transactionId)->first();

            // Si no se encuentra por transaction_id, buscar por referencia (para payment links)
            if (!$pago && !empty($reference)) {
                $pago = Pago::where('referencia_wompi', $reference)->first();

                Log::info('Búsqueda por referencia', [
                    'request_id' => $requestId,
                    'reference' => $reference,
                    'pago_encontrado' => !!$pago
                ]);

                // Si encontramos el pago por referencia, actualizar transaction_id
                if ($pago) {
                    $pago->update(['wompi_transaction_id' => $transactionId]);

                    Log::info('Transaction ID actualizado desde referencia', [
                        'request_id' => $requestId,
                        'pago_id' => $pago->id,
                        'transaction_id' => $transactionId,
                        'reference' => $reference
                    ]);
                }
            }

            if (!$pago) {
                Log::warning('Webhook para transacción no encontrada (ni por ID ni por referencia)', [
                    'request_id' => $requestId,
                    'transaction_id' => $transactionId,
                    'reference' => $transactionData['reference'] ?? 'N/A'
                ]);
                return response()->json(['error' => 'Transaction not found'], 404);
            }

            // ✅ VALIDAR ESTADOS ANTES DE ACTUALIZAR
            if ($pago->estado === $newStatus) {
                Log::info('Webhook duplicado - estado ya actualizado', [
                    'request_id' => $requestId,
                    'pago_id' => $pago->id,
                    'estado_actual' => $pago->estado
                ]);
                return response()->json(['status' => 'already_processed']);
            }

            // ✅ PROCESAR EN TRANSACCIÓN PARA CONSISTENCIA
            DB::transaction(function () use ($pago, $newStatus, $transactionData, $requestId) {
                $oldStatus = $pago->estado;

                // Actualizar el pago
                $pago->update([
                    'estado' => $newStatus,
                    'response_data' => $transactionData,
                    'updated_at' => now()
                ]);

                Log::info('Pago actualizado', [
                    'request_id' => $requestId,
                    'pago_id' => $pago->id,
                    'estado_anterior' => $oldStatus,
                    'estado_nuevo' => $newStatus
                ]);

                // ✅ SINCRONIZACIÓN SEGURA DE ESTADOS
                $this->sincronizarEstadosConPago($pago, $newStatus, $requestId);
            });

            $processingTime = round((microtime(true) - $startTime) * 1000, 2);

            Log::info('Webhook procesado exitosamente', [
                'request_id' => $requestId,
                'pago_id' => $pago->id,
                'processing_time_ms' => $processingTime
            ]);

            return response()->json([
                'status' => 'success',
                'request_id' => $requestId,
                'processing_time_ms' => $processingTime
            ]);

        } catch (Exception $e) {
            $processingTime = round((microtime(true) - $startTime) * 1000, 2);

            Log::error('Error crítico procesando webhook', [
                'request_id' => $requestId,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'payload' => $request->getContent(),
                'processing_time_ms' => $processingTime
            ]);

            return response()->json([
                'error' => 'Internal server error',
                'request_id' => $requestId
            ], 500);
        }
    }

    /**
     * ✅ NUEVO: Validación robusta de signature de Wompi
     */
    private function validateWompiWebhookSignature($payload, $wompiHash, $signature, $timestamp)
    {
        try {
            // Validar que tenemos los headers necesarios
            if (!$wompiHash && !$signature) {
                return false;
            }

            // Obtener el API Secret de Wompi (CLIENT_SECRET para El Salvador)
            $apiSecret = config('wompi.client_secret', env('WOMPI_CLIENT_SECRET'));

            if (!$apiSecret) {
                Log::error('API Secret de Wompi no configurado');
                return false;
            }

            // Método 1: Validar con wompi_hash (HMAC SHA256)
            if ($wompiHash) {
                $expectedHash = hash_hmac('sha256', $payload, $apiSecret);
                if (hash_equals($expectedHash, $wompiHash)) {
                    return true;
                }
            }

            // Método 2: Validar con wompi-signature (si está disponible)
            if ($signature && $timestamp) {
                // Implementar validación adicional si Wompi usa este método
                // Por ahora, usar el método de hash HMAC
                return false;
            }

            return false;

        } catch (Exception $e) {
            Log::error('Error validando signature de webhook', [
                'error' => $e->getMessage()
            ]);
            return false;
        }
    }

    /**
     * ✅ NUEVO: Sincronización segura de estados entre pago, venta/reserva y STOCK
     */
    private function sincronizarEstadosConPago($pago, $newStatus, $requestId)
    {
        try {
            if ($newStatus === 'approved') {
                // ✅ PAGO APROBADO - CONFIRMAR RESERVAS Y REDUCIR STOCK

                // 1. Confirmar reservas de stock por referencia
                $reservasConfirmadas = StockReservation::confirmarReservasPorReferencia($pago->referencia_wompi);

                Log::info('🎉 Pago aprobado - Reservas confirmadas y stock reducido', [
                    'request_id' => $requestId,
                    'pago_id' => $pago->id,
                    'referencia_wompi' => $pago->referencia_wompi,
                    'total_reservas_confirmadas' => $reservasConfirmadas->count()
                ]);

                // 2. Si hay venta asociada, actualizar a completada y crear movimientos de inventario
                if ($pago->venta_id) {
                    $venta = $pago->venta;

                    // Cambiar estado de venta de pendiente a completada
                    if ($venta->estado === 'pendiente') {
                        $venta->update(['estado' => 'completada']);

                        // Crear movimientos de inventario para cada detalle
                        foreach ($venta->detalleVentas as $detalle) {
                            \App\Models\Inventario::create([
                                'fecha_movimiento' => now(),
                                'cantidad' => $detalle->cantidad,
                                'tipo_movimiento' => 'SALIDA',
                                'motivo' => 'venta_confirmada',
                                'observacion' => "Venta #{$venta->id} confirmada por pago Wompi - {$detalle->producto->nombre}",
                                'user_id' => 1, // Sistema
                                'producto_id' => $detalle->producto_id,
                                'venta_id' => $venta->id
                            ]);
                        }

                        Log::info('✅ Venta confirmada y movimientos de inventario creados', [
                            'request_id' => $requestId,
                            'venta_id' => $venta->id,
                            'pago_id' => $pago->id,
                            'estado_anterior' => 'pendiente',
                            'estado_nuevo' => 'completada'
                        ]);
                    }
                }

                // 3. Si hay reserva de tour asociada
                if ($pago->reserva_id) {
                    $reserva = $pago->reserva;
                    if ($reserva->estado === 'pendiente') {
                        $reserva->update(['estado' => 'confirmada']);

                        Log::info('Reserva de tour confirmada por pago aprobado', [
                            'request_id' => $requestId,
                            'reserva_id' => $reserva->id,
                            'pago_id' => $pago->id
                        ]);
                    }
                }

            } elseif (in_array($newStatus, ['declined', 'error', 'failed', 'voided'])) {
                // ❌ PAGO RECHAZADO/FALLIDO - CANCELAR RESERVAS

                // 1. Cancelar reservas de stock (liberar stock reservado)
                $reservasCanceladas = StockReservation::cancelarReservasPorReferencia(
                    $pago->referencia_wompi,
                    "Pago {$newStatus} - Wompi webhook"
                );

                Log::info('❌ Pago rechazado - Reservas de stock canceladas', [
                    'request_id' => $requestId,
                    'pago_id' => $pago->id,
                    'referencia_wompi' => $pago->referencia_wompi,
                    'motivo' => $newStatus,
                    'total_reservas_canceladas' => $reservasCanceladas->count()
                ]);

                // 2. Si hay venta asociada, cancelar
                if ($pago->venta_id) {
                    $venta = $pago->venta;
                    if ($venta->estado === 'pendiente') {
                        $venta->update(['estado' => 'cancelada']);

                        Log::info('❌ Venta cancelada por pago rechazado', [
                            'request_id' => $requestId,
                            'venta_id' => $venta->id,
                            'pago_id' => $pago->id,
                            'razon' => $newStatus
                        ]);
                    }
                }

                // 3. Si hay reserva de tour asociada
                if ($pago->reserva_id) {
                    $reserva = $pago->reserva;
                    if ($reserva->estado === 'pendiente') {
                        $reserva->update(['estado' => 'cancelada']);

                        Log::info('❌ Reserva de tour cancelada por pago rechazado', [
                            'request_id' => $requestId,
                            'reserva_id' => $reserva->id,
                            'pago_id' => $pago->id,
                            'razon' => $newStatus
                        ]);
                    }
                }

                if ($pago->reserva_id) {
                    $reserva = $pago->reserva;
                    if ($reserva->estado !== 'cancelada') {
                        $reserva->update(['estado' => 'cancelada']);

                        Log::info('Reserva cancelada por pago rechazado', [
                            'request_id' => $requestId,
                            'reserva_id' => $reserva->id,
                            'pago_id' => $pago->id,
                            'razon' => $newStatus
                        ]);
                    }
                }
            }

        } catch (Exception $e) {
            Log::error('Error sincronizando estados', [
                'request_id' => $requestId,
                'pago_id' => $pago->id,
                'new_status' => $newStatus,
                'error' => $e->getMessage()
            ]);

            // Re-lanzar la excepción para que la transacción falle
            throw $e;
        }
    }

    /**
     * ✅ ARREGLADO: Crear enlace de pago directamente desde el carrito CON registro de pago
     */
    public function createPaymentLinkFromCart(Request $request)
    {
        Log::info('🔗 Iniciando createPaymentLinkFromCart', [
            'request_data' => $request->all(),
            'user_id' => Auth::id(),
            'is_authenticated' => Auth::check()
        ]);

        try {
            $validated = $request->validate([
                'customer_email' => 'required|email',
                'amount' => 'required|numeric|min:0.01',
                'description' => 'string|nullable',
                'reference' => 'string|nullable',
                'productos' => 'required|array|min:1',
                'productos.*.id' => 'required|integer',
                'productos.*.nombre' => 'required|string',
                'productos.*.precio' => 'required|numeric|min:0',
                'productos.*.cantidad' => 'required|integer|min:1',
                'productos.*.subtotal' => 'required|numeric|min:0',
                // ✅ NUEVO: Parámetro opcional para vincular con venta existente
                'venta_id' => 'nullable|integer|exists:ventas,id'
            ]);

            // Calcular total de items para descripción
            $totalItems = 0;
            foreach ($validated['productos'] as $producto) {
                $totalItems += $producto['cantidad'];
            }

            // Crear descripción simple
            if (count($validated['productos']) == 1) {
                $prod = $validated['productos'][0];
                if ($prod['cantidad'] == 1) {
                    $descripcionDetallada = "{$prod['nombre']} - \${$prod['precio']}";
                } else {
                    $descripcionDetallada = "{$prod['cantidad']}x {$prod['nombre']} - \${$prod['precio']} c/u";
                }
            } else {
                $detalles = [];
                foreach ($validated['productos'] as $producto) {
                    if ($producto['cantidad'] == 1) {
                        $detalles[] = "1x {$producto['nombre']} - \${$producto['precio']}";
                    } else {
                        $detalles[] = "{$producto['cantidad']}x {$producto['nombre']} - \${$producto['precio']} c/u";
                    }
                }
                $descripcionDetallada = implode(" | ", $detalles);
            }

            // No enviar imagen ya que no se muestra en Wompi
            $imagenProducto = null;

            // Crear nombre de producto simple
            if (count($validated['productos']) === 1) {
                $prod = $validated['productos'][0];
                if ($prod['cantidad'] == 1) {
                    $nombreProducto = $prod['nombre'];
                } else {
                    $nombreProducto = "{$prod['cantidad']}x {$prod['nombre']}";
                }
            } else {
                $nombreProducto = "Paquete de {$totalItems} productos - VASIR";
            }

            // Crear enlace de pago con Wompi (sin imagen)
            $paymentData = [
                'amount_in_cents' => $validated['amount'] * 100,
                'description' => $descripcionDetallada,
                'reference' => $validated['reference'] ?? 'CART-' . time(),
                'customer_email' => $validated['customer_email'],
                'product_name' => $nombreProducto,
                'productos_detalle' => $validated['productos']
            ];

            // ✅ BUSCAR VENTA EXISTENTE (si se proporciona venta_id)
            $venta = null;
            if (!empty($validated['venta_id'])) {
                $venta = Venta::find($validated['venta_id']);

                if (!$venta || $venta->estaCancelada()) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Venta no encontrada o fue cancelada'
                    ], 400);
                }

                Log::info('🔗 Venta existente encontrada', [
                    'venta_id' => $venta->id,
                    'venta_total' => $venta->total,
                    'venta_estado' => $venta->estado
                ]);
            }

            Log::info('🔗 PagoController - Datos antes de enviar a Wompi', [
                'validated_amount' => $validated['amount'],
                'amount_in_cents' => $paymentData['amount_in_cents'],
                'amount_final' => $paymentData['amount_in_cents'] / 100,
                'product_name' => $paymentData['product_name'],
                'reference' => $paymentData['reference'],
                'full_payment_data' => $paymentData
            ]);

            // ✅ CREAR ENLACE DE PAGO CON WOMPI
            $result = $this->wompiService->createPaymentLink($paymentData);

            Log::info('🔗 PagoController - Resultado de Wompi', [
                'success' => $result['success'],
                'result' => $result
            ]);

            if (!$result['success']) {
                Log::error('❌ Error creando enlace de pago con Wompi', [
                    'error' => $result['error'],
                    'payment_data' => $paymentData
                ]);

                return response()->json([
                    'success' => false,
                    'message' => $result['error']
                ], 400);
            }

            // ✅ CREAR REGISTRO DE PAGO Y RESERVAS DE STOCK
            try {
                DB::beginTransaction();

                $pago = Pago::create([
                    'venta_id' => $venta ? $venta->id : null,
                    'monto' => $validated['amount'],
                    'moneda' => 'COP',
                    'referencia_wompi' => $paymentData['reference'],
                    'estado' => 'pending',
                    'metodo_pago' => 'payment_link',
                    'email_cliente' => $validated['customer_email'],
                    'wompi_payment_link_id' => $result['link_id'] ?? null,
                    'wompi_payment_link' => $result['payment_link'],
                    'productos_detalle' => json_encode($validated['productos'])
                ]);

                // 🔒 CREAR RESERVAS DE STOCK (NUEVO)
                try {
                    $reservas = StockReservation::crearReservasParaCarrito(
                        $validated['productos'],
                        $paymentData['reference'],
                        30 // 30 minutos de expiración
                    );

                    // Asociar reservas con el pago
                    foreach ($reservas as $reserva) {
                        $reserva->update(['pago_id' => $pago->id]);
                    }

                    Log::info('✅ Reservas de stock creadas', [
                        'pago_id' => $pago->id,
                        'referencia_wompi' => $pago->referencia_wompi,
                        'total_reservas' => $reservas->count(),
                        'expiran_en' => now()->addMinutes(30)->toISOString()
                    ]);

                } catch (\Exception $reservaError) {
                    // Si falla la creación de reservas, cancelar todo
                    DB::rollBack();

                    Log::error('❌ Error creando reservas de stock', [
                        'error' => $reservaError->getMessage(),
                        'pago_id' => $pago->id ?? null,
                        'referencia' => $paymentData['reference']
                    ]);

                    return response()->json([
                        'success' => false,
                        'message' => 'Error reservando stock: ' . $reservaError->getMessage()
                    ], 400);
                }

                Log::info('✅ Registro de pago y reservas creados', [
                    'pago_id' => $pago->id,
                    'referencia_wompi' => $pago->referencia_wompi,
                    'venta_id' => $pago->venta_id,
                    'wompi_link_id' => $result['link_id']
                ]);

                DB::commit();

                return response()->json([
                    'success' => true,
                    'payment_link' => $result['payment_link'],
                    'link_id' => $result['link_id'],
                    'reference' => $result['reference'],
                    'pago_id' => $pago->id
                ]);

            } catch (Exception $e) {
                DB::rollBack();

                Log::error('❌ Error creando registro de pago', [
                    'error' => $e->getMessage(),
                    'reference' => $paymentData['reference']
                ]);

                return response()->json([
                    'success' => false,
                    'message' => 'Error creando registro de pago: ' . $e->getMessage()
                ], 500);
            }

        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Datos inválidos',
                'errors' => $e->errors()
            ], 422);
        } catch (Exception $e) {
            Log::error('❌ Error general en createPaymentLinkFromCart', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Error interno del servidor'
            ], 500);
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
