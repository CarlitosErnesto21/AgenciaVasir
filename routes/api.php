<?php

use App\Http\Controllers\Auth\ApiAuthController;
use App\Http\Controllers\ClienteController;
use App\Http\Controllers\ProductoController;
use App\Http\Controllers\EmpleadoController;
use App\Http\Controllers\HotelController;
use App\Http\Controllers\ReservaController;
use App\Http\Controllers\TourController;
use App\Http\Controllers\VentaController;
use App\Http\Controllers\CategoriaHotelController;
use App\Http\Controllers\CategoriaProductoController;
use App\Http\Controllers\PaisController;
use App\Http\Controllers\ProvinciaController;
use App\Http\Controllers\TransporteController;
use App\Http\Controllers\TipoDocumentoController;
use App\Http\Controllers\PaqueteController;
use App\Http\Controllers\InventarioController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Auth;

// ═══════════════════════════════════════════════════════════
// RUTAS PÚBLICAS (sin autenticación)
// ═══════════════════════════════════════════════════════════

// Rutas de autenticación
Route::post('/login', [ApiAuthController::class, 'login']);

// Rutas para la tienda
Route::get('/productos', [ProductoController::class, 'index']);
Route::get('/categorias-productos', [CategoriaProductoController::class, 'index']);
Route::get('/tours', [TourController::class, 'index']);
Route::get('/tours/{id}', [TourController::class, 'show']);
Route::get('/hoteles', [HotelController::class, 'index']);
Route::get('/paquetes', [PaqueteController::class, 'index']);
Route::get('/tipo-documentos', [TipoDocumentoController::class, 'index']);

// ═══════════════════════════════════════════════════════════
// RUTAS DE WOMPI (PAGOS) - PÚBLICAS
// ═══════════════════════════════════════════════════════════
use App\Http\Controllers\PagoController;

// Rutas públicas para configuración de Wompi
Route::get('/wompi/config', [PagoController::class, 'getPublicConfig']);
Route::get('/wompi/acceptance-token', [PagoController::class, 'getAcceptanceToken']);
Route::post('/wompi/payment-link', [PagoController::class, 'createPaymentLinkFromCart']);

// Ruta de prueba para verificar imágenes de productos (temporal)
Route::get('/debug/product-images', [PagoController::class, 'testProductImages']);

// Webhook de Wompi (debe ser público para que Wompi pueda llamarlo)
Route::post('/wompi/webhook', [PagoController::class, 'webhook']);

// ═══════════════════════════════════════════════════════════
// RUTAS PROTEGIDAS (requieren autenticación)
// ═══════════════════════════════════════════════════════════
Route::middleware('auth:sanctum')->group(function () {

    // ───────────────────────────────────────────────────────
    // RUTAS BÁSICAS DE USUARIO
    // ───────────────────────────────────────────────────────
    Route::post('/logout', [ApiAuthController::class, 'logout']);
    Route::get('/user', function (Request $request) {
        return $request->user();
    });



    // ───────────────────────────────────────────────────────
    // RUTAS DE TESTING (temporal)
    // ───────────────────────────────────────────────────────
    Route::get('/test-auth', function(Request $request) {
        return response()->json([
            'authenticated' => Auth::check(),
            'user_id' => Auth::id(),
            'user' => Auth::user(),
            'session_data' => session()->all()
        ]);
    });

    // ───────────────────────────────────────────────────────
    // RUTAS ADMINISTRATIVAS (requieren rol admin/empleado)
    // ───────────────────────────────────────────────────────
    Route::middleware('rutas.admin')->group(function () {



        // Gestión de reservas
        Route::prefix('reservas')->group(function () {
            Route::get('/', [ReservaController::class, 'index']);
            Route::get('/resumen', [ReservaController::class, 'resumen']);
            Route::put('/{id}/confirmar', [ReservaController::class, 'confirmar']);
            Route::put('/{id}/rechazar', [ReservaController::class, 'rechazar']);
            Route::put('/{id}/reprogramar', [ReservaController::class, 'reprogramar']);
            Route::put('/{id}/finalizar', [ReservaController::class, 'finalizar']);
            Route::post('/finalizar-automaticamente', [ReservaController::class, 'finalizarAutomaticamente']);
            Route::get('/{id}/historial', [ReservaController::class, 'historial']);
        });

        // Recursos CRUD principales
        Route::apiResource('productos', ProductoController::class)->except(['index']);
        Route::apiResource('hoteles', HotelController::class)->except(['index']);
        // Ruta adicional para estadísticas de hoteles
        Route::get('hoteles/{id}/estadisticas', [HotelController::class, 'obtenerEstadisticas']);

        Route::apiResource('tours', TourController::class)->except(['index', 'show']);

        // Rutas adicionales para tours
        Route::put('tours/{id}/cambiar-estado', [TourController::class, 'cambiarEstado']);

        Route::apiResource('paquetes', PaqueteController::class)->except(['index']);
        Route::apiResource('clientes', ClienteController::class);
        // ✅ PROTEGIDO: Rutas de ventas con validación de integridad
        Route::apiResource('ventas', VentaController::class)
            ->middleware('venta.integrity')
            ->only(['index', 'show']); // Solo permitir consultas

        // ✅ RUTAS ADICIONALES SEGURAS PARA VENTAS
        Route::prefix('ventas')->middleware('venta.integrity')->group(function () {
            Route::get('/por-estado/{estado}', [VentaController::class, 'porEstado']);
            Route::get('/completadas-validas', [VentaController::class, 'index'])->defaults('solo_validas', true);
            Route::get('/pendientes-con-pago', [VentaController::class, 'index'])->defaults('con_pago_aprobado', false);
            Route::get('/resumen', [VentaController::class, 'resumen']);
        });
        Route::apiResource('empleados', EmpleadoController::class);
        Route::put('empleados/{id}/password', [EmpleadoController::class, 'updatePassword']);
        Route::apiResource('categorias-hoteles', CategoriaHotelController::class);
        Route::apiResource('categorias-productos', CategoriaProductoController::class)->except(['index']);
        Route::apiResource('paises', PaisController::class)->parameter('paises', 'pais');
        Route::apiResource('provincias', ProvinciaController::class)->parameter('provincias', 'provincia');
        Route::apiResource('transportes', TransporteController::class);
        Route::apiResource('tipo-documentos', TipoDocumentoController::class)->except(['index']);

        //Gestión específica de clientes
        Route::prefix('clientes')->group(function () {
            Route::get('/', [ClienteController::class, 'getClientes']);
            Route::post('/', [ClienteController::class, 'store']);
            Route::get('/{cliente}', [ClienteController::class, 'show']);
            Route::put('/{cliente}', [ClienteController::class, 'update']);
            Route::delete('/{cliente}', [ClienteController::class, 'destroy']);
            Route::patch('/{cliente}/toggle-status', [ClienteController::class, 'toggleStatus']);
            Route::get('/tipos-documento-options', [ClienteController::class, 'getTiposDocumento']);
        });

        // Rutas de Inventario
        Route::prefix('inventario')->name('inventario.')->group(function () {
            Route::get('/', [InventarioController::class, 'index'])->name('index');
            Route::post('/agregar-stock', [InventarioController::class, 'agregarStock'])->name('agregar-stock');
            Route::post('/ajustar-stock', [InventarioController::class, 'ajustarStock'])->name('ajustar-stock');
            Route::get('/resumen', [InventarioController::class, 'resumen'])->name('resumen');
            Route::get('/stock-bajo', [InventarioController::class, 'stockBajo'])->name('stock-bajo');
            Route::get('/agotados', [InventarioController::class, 'agotados'])->name('agotados');
            Route::get('/producto/{producto}/historial', [InventarioController::class, 'historialProducto'])->name('producto.historial');
            Route::get('/{inventario}', [InventarioController::class, 'show'])->name('show');
        });

        // Rutas adicionales específicas para productos (sin conflicto)
        Route::prefix('admin/productos')->group(function () {
            Route::get('/stock-bajo', [ProductoController::class, 'stockBajo']);
            Route::get('/agotados', [ProductoController::class, 'agotados']);
        });

        // Ruta específica para actualizar stock de productos
        Route::patch('productos/{id}/actualizar-stock', [ProductoController::class, 'actualizarStock']);

        // ❌ RUTAS ELIMINADAS: procesar y cancelar no son seguras
        // Las ventas se procesan automáticamente via webhook de Wompi

        // ═══════════════════════════════════════════════════════════
        // RUTAS DE PAGOS WOMPI (PROTEGIDAS)
        // ═══════════════════════════════════════════════════════════
        Route::prefix('pagos')->name('pagos.')->group(function () {
            // Procesar pagos
            Route::post('/venta', [PagoController::class, 'procesarPagoVenta'])->name('venta');
            Route::post('/reserva', [PagoController::class, 'procesarPagoReserva'])->name('reserva');

            // Consultar estado de pagos
            Route::get('/{pago}/estado', [PagoController::class, 'consultarEstadoPago'])->name('estado');

            // Administración de pagos (solo para admin)
            Route::get('/', [PagoController::class, 'index'])->name('index');
        });
    });
});
