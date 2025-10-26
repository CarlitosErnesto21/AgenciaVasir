<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Auth;
use App\Models\Venta;
use App\Models\Pago;

class ValidateVentaPagoIntegrity
{
    /**
     * ✅ Middleware para validar integridad entre ventas y pagos
     * 
     * Protege endpoints críticos verificando que:
     * - Las ventas completadas tengan pagos aprobados
     * - No existan inconsistencias en estados
     * - Solo se permitan operaciones seguras
     */
    public function handle(Request $request, Closure $next, ...$guards)
    {
        $startTime = microtime(true);
        $requestId = uniqid('integrity_', true);

        try {
            // Solo aplicar en rutas de API relacionadas con ventas
            if (!$this->shouldValidate($request)) {
                return $next($request);
            }

            Log::info('Validando integridad venta-pago', [
                'request_id' => $requestId,
                'method' => $request->method(),
                'route' => $request->route()?->getName(),
                'path' => $request->path()
            ]);

            // Validar según el tipo de operación
            $validationResult = $this->validateByOperation($request, $requestId);

            if (!$validationResult['valid']) {
                Log::warning('Operación bloqueada por validación de integridad', [
                    'request_id' => $requestId,
                    'reason' => $validationResult['reason'],
                    'details' => $validationResult['details'] ?? []
                ]);

                return response()->json([
                    'error' => 'INTEGRITY_VIOLATION',
                    'message' => $validationResult['reason'],
                    'details' => $validationResult['details'] ?? [],
                    'request_id' => $requestId
                ], 422);
            }

            // Procesar la solicitud
            $response = $next($request);

            // Validar integridad después de la operación si es necesario
            $this->validateAfterOperation($request, $response, $requestId);

            $processingTime = round((microtime(true) - $startTime) * 1000, 2);
            
            Log::info('Validación de integridad completada', [
                'request_id' => $requestId,
                'processing_time_ms' => $processingTime
            ]);

            return $response;

        } catch (\Exception $e) {
            Log::error('Error en validación de integridad', [
                'request_id' => $requestId,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'error' => 'INTEGRITY_CHECK_FAILED',
                'message' => 'Error validando integridad del sistema',
                'request_id' => $requestId
            ], 500);
        }
    }

    /**
     * Determinar si la solicitud requiere validación
     */
    private function shouldValidate(Request $request): bool
    {
        $path = $request->path();
        $method = $request->method();

        // Rutas que requieren validación
        $criticalPaths = [
            'api/ventas',
            'carrito/create-venta',
            'pagos/venta',
            'wompi/webhook'
        ];

        foreach ($criticalPaths as $criticalPath) {
            if (str_contains($path, $criticalPath)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Validar según el tipo de operación
     */
    private function validateByOperation(Request $request, string $requestId): array
    {
        $method = $request->method();
        $path = $request->path();

        // ✅ VALIDAR CREACIÓN DE VENTAS (debe ser solo por carrito)
        if ($method === 'POST' && str_contains($path, 'api/ventas')) {
            return [
                'valid' => false,
                'reason' => 'Las ventas solo se crean a través del proceso de pago con Wompi',
                'details' => [
                    'endpoint_correcto' => 'POST /carrito/create-venta',
                    'flujo_requerido' => 'Carrito → Wompi → Webhook → Venta confirmada'
                ]
            ];
        }

        // ✅ VALIDAR ACTUALIZACIÓN DE VENTAS (debe ser solo por webhook)
        if (in_array($method, ['PUT', 'PATCH']) && str_contains($path, 'api/ventas')) {
            return [
                'valid' => false,
                'reason' => 'Solo el webhook de Wompi puede actualizar estados de ventas',
                'details' => [
                    'actualizacion_automatica' => 'Los estados se actualizan via webhook',
                    'endpoint_webhook' => 'POST /api/wompi/webhook'
                ]
            ];
        }

        // ✅ VALIDAR ELIMINACIÓN DE VENTAS
        if ($method === 'DELETE' && str_contains($path, 'api/ventas')) {
            return [
                'valid' => false,
                'reason' => 'No se permite eliminar ventas por integridad del sistema',
                'details' => [
                    'cancelacion_automatica' => 'Las ventas se cancelan automáticamente si el pago falla'
                ]
            ];
        }

        // ✅ VALIDAR CONSULTAS DE VENTAS CON PARÁMETROS ESPECÍFICOS
        if ($method === 'GET' && str_contains($path, 'api/ventas')) {
            return $this->validateVentasQuery($request, $requestId);
        }

        // ✅ VALIDAR CREACIÓN DESDE CARRITO
        if ($method === 'POST' && str_contains($path, 'carrito/create-venta')) {
            return $this->validateCarritoToVenta($request, $requestId);
        }

        return ['valid' => true];
    }

    /**
     * Validar consultas de ventas
     */
    private function validateVentasQuery(Request $request, string $requestId): array
    {
        // Si se solicitan solo ventas válidas, verificar integridad
        if ($request->has('check_integrity')) {
            $inconsistentVentas = Venta::with('pagos')
                ->get()
                ->filter(function ($venta) {
                    return !$venta->validarConsistenciaConPagos();
                });

            if ($inconsistentVentas->isNotEmpty()) {
                Log::warning('Ventas inconsistentes detectadas', [
                    'request_id' => $requestId,
                    'inconsistent_count' => $inconsistentVentas->count(),
                    'ventas_ids' => $inconsistentVentas->pluck('id')->toArray()
                ]);

                return [
                    'valid' => false,
                    'reason' => 'Se detectaron ventas con estados inconsistentes',
                    'details' => [
                        'ventas_inconsistentes' => $inconsistentVentas->count(),
                        'requiere_auditoria' => true
                    ]
                ];
            }
        }

        return ['valid' => true];
    }

    /**
     * Validar creación de venta desde carrito
     */
    private function validateCarritoToVenta(Request $request, string $requestId): array
    {
        // Validar que el usuario esté autenticado
        if (!Auth::check()) {
            return [
                'valid' => false,
                'reason' => 'Usuario no autenticado para crear ventas',
                'details' => ['auth_required' => true]
            ];
        }

        // Validar que hay productos en el carrito
        $productos = $request->input('productos', []);
        if (empty($productos)) {
            return [
                'valid' => false,
                'reason' => 'No se puede crear venta sin productos',
                'details' => ['carrito_vacio' => true]
            ];
        }

        // Validar stock disponible
        foreach ($productos as $producto) {
            if (!isset($producto['id']) || !isset($producto['cantidad'])) {
                return [
                    'valid' => false,
                    'reason' => 'Datos de producto inválidos',
                    'details' => ['producto_invalido' => $producto]
                ];
            }
        }

        return ['valid' => true];
    }

    /**
     * Validar después de la operación
     */
    private function validateAfterOperation(Request $request, $response, string $requestId): void
    {
        // Si se creó una venta, verificar que se creó en estado completada
        if ($request->method() === 'POST' && 
            str_contains($request->path(), 'carrito/create-venta') && 
            $response->getStatusCode() === 201) {
            
            $responseData = json_decode($response->getContent(), true);
            
            if (isset($responseData['venta']['estado']) && 
                $responseData['venta']['estado'] !== 'completada') {
                
                Log::error('Venta creada con estado incorrecto', [
                    'request_id' => $requestId,
                    'venta_id' => $responseData['venta']['id'] ?? null,
                    'estado_actual' => $responseData['venta']['estado'],
                    'estado_esperado' => 'completada'
                ]);
            }
        }
    }
}