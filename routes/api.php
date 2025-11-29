<?php

use App\Http\Controllers\Auth\ApiAuthController;
use App\Http\Controllers\ClienteController;
use App\Http\Controllers\ProductoController;
use App\Http\Controllers\EmpleadoController;
use App\Http\Controllers\HotelController;
use App\Http\Controllers\ReservaController;
use App\Http\Controllers\TourController;
use App\Http\Controllers\VentaController;

use App\Http\Controllers\CategoriaProductoController;
use App\Http\Controllers\PaisController;
use App\Http\Controllers\ProvinciaController;
use App\Http\Controllers\TransporteController;

use App\Http\Controllers\InventarioController;
use App\Http\Controllers\PagoController;
use App\Http\Controllers\BackupController;
use App\Http\Controllers\PaqueteVisaController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Auth;


// ═══════════════════════════════════════════════════════════
// RUTAS PÚBLICAS (sin autenticación)
// ═══════════════════════════════════════════════════════════

// Rutas de autenticación
Route::post('/login', [ApiAuthController::class, 'login']);

// Ruta para obtener el token CSRF (debe ser la primera petición desde el frontend)
Route::get('/csrf-token', function() {
    return response()->json(['csrf_token' => csrf_token()]);
});

// Rutas para la tienda
Route::get('/productos', [ProductoController::class, 'index']);
Route::get('/categorias-productos', [CategoriaProductoController::class, 'index']);
Route::get('/tours', [TourController::class, 'index']);
Route::get('/tours/{id}', [TourController::class, 'show']);
Route::get('/hoteles', [HotelController::class, 'index']);
Route::get('/paquetes-visas', [PaqueteVisaController::class, 'index']);


// Ruta para que usuarios autenticados puedan crear su perfil de cliente
Route::middleware('auth:sanctum')->post('/registro-cliente', [ClienteController::class, 'registroCliente']);

// Ruta para verificar si el usuario tiene datos de cliente completos
Route::middleware('auth:sanctum')->get('/verificar-datos-cliente', [ClienteController::class, 'verificarDatosCompletos']);

// Ruta para obtener datos del cliente autenticado
Route::middleware('auth:sanctum')->get('/clientes/mi-perfil', [ClienteController::class, 'miPerfil']);

// Ruta alternativa para obtener datos del cliente autenticado
Route::middleware('auth:sanctum')->get('/cliente-datos', [ClienteController::class, 'miPerfil']);

// Ruta para validar teléfono único
Route::middleware('auth:sanctum')->post('/clientes/validar-telefono', [ClienteController::class, 'validarTelefono']);

// Ruta para validar documento único
Route::middleware('auth:sanctum')->post('/clientes/validar-documento', [ClienteController::class, 'validarDocumento']);

// Ruta para validar nombre único de usuarios
Route::middleware('auth:sanctum')->post('/users/validar-nombre', [ApiAuthController::class, 'validarNombre']);

// Ruta para validar email único de usuarios
Route::middleware('auth:sanctum')->post('/users/validar-email', [ApiAuthController::class, 'validarEmail']);

// ═══════════════════════════════════════════════════════════
// RUTAS DE WOMPI (PAGOS) - PÚBLICAS
// ═══════════════════════════════════════════════════════════
Route::get('/wompi/config', [PagoController::class, 'getPublicConfig']);

Route::get('/wompi/acceptance-token', [PagoController::class, 'getAcceptanceToken']);
Route::post('/wompi/payment-link', [PagoController::class, 'createPaymentLinkFromCart']);
Route::post('/wompi/payment-link-tour', [PagoController::class, 'createPaymentLinkFromTour']);



// Webhook de Wompi (debe ser público para que Wompi pueda llamarlo)
Route::post('/wompi/webhook', [PagoController::class, 'webhook']);

// ═══════════════════════════════════════════════════════════
// RUTAS PROTEGIDAS (requieren autenticación)
// ═══════════════════════════════════════════════════════════
Route::middleware('auth:sanctum')->group(function () {

    // RUTAS BÁSICAS DE USUARIO
    Route::post('/logout', [ApiAuthController::class, 'logout']);
    Route::get('/user', function (Request $request) {
        return $request->user();
    });

    // RUTAS DE TESTING (temporal)
    Route::get('/test-auth', function(Request $request) {
        return response()->json([
            'authenticated' => Auth::check(),
            'user_id' => Auth::id(),
            'user' => Auth::user(),
            'session_data' => session()->all()
        ]);
    });

        // RUTAS DE RESERVAS DE CLIENTES (requieren autenticación)
    Route::post('/reservas/tour', [ReservaController::class, 'crearReservaTour']);
    Route::post('/reservas/hotel', [ReservaController::class, 'crearReservaHotel']);

    // ───────────────────────────────────────────────────────
    // RUTAS ADMINISTRATIVAS (requieren rol admin/empleado)
    // ───────────────────────────────────────────────────────
    Route::middleware('rutas.admin')->group(function () {

        // Gestión de reservas
        Route::prefix('reservas')->group(function () {
            Route::get('/', [ReservaController::class, 'index']);
            Route::get('/{id}', [ReservaController::class, 'show']);
            Route::get('/resumen', [ReservaController::class, 'resumen']);
            Route::put('/{id}/confirmar', [ReservaController::class, 'confirmar']);
            Route::put('/{id}/rechazar', [ReservaController::class, 'rechazar']);
            Route::put('/{id}/reprogramar', [ReservaController::class, 'reprogramar']);
            Route::put('/{id}/finalizar', [ReservaController::class, 'finalizar']);
            Route::post('/finalizar-automaticamente', [ReservaController::class, 'finalizarAutomaticamente']);
            Route::get('/{id}/historial', [ReservaController::class, 'historial']);
        });

        // Recursos CRUD principales
        Route::apiResource('paquetes-visas', PaqueteVisaController::class)->except(['index']);
        Route::apiResource('productos', ProductoController::class)->except(['index']);
        Route::apiResource('hoteles', HotelController::class)->except(['index']);
        Route::apiResource('tours', TourController::class)->except(['index', 'show']);

        // Rutas específicas de clientes (antes del apiResource para evitar conflictos)
        Route::get('clientes/buscar', [ClienteController::class, 'buscarClientes']);
        Route::apiResource('clientes', ClienteController::class);
        Route::put('tours/{id}/cambiar-estado', [TourController::class, 'cambiarEstado']);
        Route::put('tours/{id}/finalizar', [TourController::class, 'finalizarTour']);
        Route::get('tours/{id}/verificar-reservas', [TourController::class, 'verificarReservas']);
        Route::get('tours/{id}/reservas', [TourController::class, 'obtenerReservas']);
        Route::get('tours/{id}/informacion-eliminacion', [TourController::class, 'informacionEliminacion']);
        Route::post('tours/validar-fechas', [TourController::class, 'validarFechasTour']);
        Route::post('tours/validar-conflictos-fechas', [TourController::class, 'validarConflictosFechas']);
        Route::get('tours-reservas-pendientes', [TourController::class, 'toursConReservasPendientes']);
        // Ruta adicional para estadísticas de hoteles
        Route::get('hoteles/{id}/estadisticas', [HotelController::class, 'obtenerEstadisticas']);

        //PROTEGIDO: Rutas de ventas con validación de integridad
        Route::apiResource('ventas', VentaController::class)
            ->middleware('venta.integrity')
            ->only(['index', 'show']);
        //RUTAS ADICIONALES SEGURAS PARA VENTAS
        Route::prefix('ventas')->middleware('venta.integrity')->group(function () {
            Route::get('/por-estado/{estado}', [VentaController::class, 'porEstado']);
            Route::get('/completadas-validas', [VentaController::class, 'index'])->defaults('solo_validas', true);
            Route::get('/resumen', [VentaController::class, 'resumen']);
        });
        // RUTAS PARA GESTIÓN DE VENTAS
        Route::post('ventas/{venta}/cancelar', [VentaController::class, 'cancelar'])->name('ventas.cancelar');
        Route::delete('ventas/{venta}/eliminar', [VentaController::class, 'eliminar'])->name('ventas.eliminar');

        Route::get('empleados/check-telefono', [EmpleadoController::class, 'checkTelefonoAvailability']);
        Route::apiResource('empleados', EmpleadoController::class);
        Route::put('empleados/{id}/password', [EmpleadoController::class, 'updatePassword']);

        Route::apiResource('categorias-productos', CategoriaProductoController::class)->except(['index']);
        Route::apiResource('paises', PaisController::class)->parameter('paises', 'pais');
        Route::apiResource('provincias', ProvinciaController::class)->parameter('provincias', 'provincia');
        Route::apiResource('transportes', TransporteController::class);


        //Gestión específica de clientes
        Route::prefix('clientes')->group(function () {
            Route::get('/', [ClienteController::class, 'getClientes']);
            Route::post('/', [ClienteController::class, 'store']);
            Route::get('/tipos-documento-options', [ClienteController::class, 'getTiposDocumento']); // SE PUEDE ELIMINAR
            Route::get('/{cliente}/estadisticas-eliminacion', [ClienteController::class, 'getEstadisticasEliminacion']);
            Route::get('/{cliente}', [ClienteController::class, 'show']);
            Route::put('/{cliente}', [ClienteController::class, 'update']);
            Route::delete('/{cliente}', [ClienteController::class, 'destroy']);
            Route::patch('/{cliente}/toggle-status', [ClienteController::class, 'toggleStatus']);
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
            Route::delete('/{inventario}', [InventarioController::class, 'destroy'])->name('destroy');
        });

        // Rutas adicionales específicas para productos (sin conflicto)
        Route::prefix('admin/productos')->group(function () {
            Route::get('/disponibles', [ProductoController::class, 'disponibles']);
            Route::get('/stock-bajo', [ProductoController::class, 'stockBajo']);
            Route::get('/agotados', [ProductoController::class, 'agotados']);
        });

        // Ruta específica para actualizar stock de productos
        Route::patch('productos/{id}/actualizar-stock', [ProductoController::class, 'actualizarStock']);

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

        // ═══════════════════════════════════════════════════════════
        // RUTAS DE BACKUPS (PROTEGIDAS - SOLO ADMIN)
        // ═══════════════════════════════════════════════════════════
        Route::prefix('backups')->name('backups.')->group(function () {
            Route::get('/', [BackupController::class, 'index'])->name('index');
            Route::post('/generate', [BackupController::class, 'generate'])->name('generate');
            Route::delete('/{id}', [BackupController::class, 'delete'])->name('delete');
            Route::post('/cleanup', [BackupController::class, 'cleanup'])->name('cleanup');
        });
    });

});

// ═══════════════════════════════════════════════════════════
// RUTA DE DESCARGA DE BACKUPS (SIN MIDDLEWARE RESTRICTIVO)
// Permite descargas directas para usuarios autenticados en el frontend
// ═══════════════════════════════════════════════════════════
Route::get('/backups/{id}/download', [BackupController::class, 'download'])->name('backups.download');
