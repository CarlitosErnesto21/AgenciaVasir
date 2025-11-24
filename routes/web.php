<?php

use App\Http\Controllers\ProfileController;
use App\Http\Controllers\InformePDFController;
use App\Http\Controllers\BackupController;
use App\Http\Controllers\ReservaController;
use App\Http\Controllers\ClienteController;
use App\Http\Controllers\SettingsController;
use App\Http\Controllers\TourController;
use App\Http\Controllers\SobreNosotrosController;
use App\Http\Controllers\VentaController;
use App\Http\Controllers\ProductoWebController;
use App\Http\Controllers\HotelWebController;
use App\Http\Controllers\EmpleadoController;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;
use App\Http\Middleware\RutasAdmin;

//Ruta principal
Route::get('/', fn() => Inertia::render('Inicio'))->name('inicio');

// Rutas protegidas
Route::middleware(['auth', 'verified', RutasAdmin::class])->group(function () {

    Route::get('dashboard', function () {
        $user = request()->user()->load(['roles', 'empleado']);
        return Inertia::render('Dashboard', [
            'user' => $user,
        ]);
    })->name('dashboard');
    Route::get('transportes', fn() => Inertia::render('Catalogos/Transportes'))->name('transportes');
    Route::get('productos', fn() => Inertia::render('Catalogos/Productos'))->name('productos');
    Route::get('hoteles', fn() => Inertia::render('Catalogos/Hoteles'))->name('hoteles');
    Route::get('reservas', fn() => Inertia::render('Catalogos/Reservas'))->name('reservas');
    Route::get('catalogos/reservas', fn() => Inertia::render('Catalogos/Reservas'))->name('catalogos.reservas');
    Route::get('tours', fn() => Inertia::render('Catalogos/Tours'))->name('tours');
    Route::get('catalogos/tours', fn() => Inertia::render('Catalogos/Tours'))->name('catalogos.tours');
    Route::get('control-paises-provincias', fn() => Inertia::render('Catalogos/ControlPaisesProvincias'))->name('controlPaisesProvincias');
    Route::get('paquetes-visas', fn() => Inertia::render('Catalogos/PaquetesVisas'))->name('paquetesVisas');

    Route::get('generar-informes', fn() => Inertia::render('Informes/Informes'))->name('informes');
    Route::get('clientes', [ClienteController::class, 'index'])->name('clientes');
    Route::get('clientes/{cliente}/reservas', [ClienteController::class, 'reservas'])->name('clientes.reservas');
    Route::get('clientes/{cliente}/ventas', [ClienteController::class, 'ventas'])->name('clientes.ventas');
    Route::get('categorias-productos', fn() => Inertia::render('Catalogos/CategoriaProductos'))->name('catProductos');

    Route::get('ventas', [VentaController::class, 'indexWeb'])->name('ventas');
    Route::get('inventario', fn() => Inertia::render('Catalogos/Inventarios'))->name('inventario');
    // Ruta para gestión de usuarios internos - Solo Administradores
    Route::get('gestion-usuarios', fn() => Inertia::render('Configuracion/GestionUsuarios'))->name('gestionUsuarios')->middleware('role:Administrador');

    //Rutas para informes
    Route::get('/descargar-informe', [InformePDFController::class, 'descargarInforme']);
    Route::get('/descargar-informe-inventario', [InformePDFController::class, 'descargarInformeInventario']);
    Route::get('/configuracion/backup', [BackupController::class, 'showBackupPage'])->name('backups')->middleware('password.confirm');

    // Configuración del Sistema - Solo para Administradores
    Route::get('/configuracion/settings', [SettingsController::class, 'index'])->name('settings')->middleware('role:Administrador');
    Route::post('/configuracion/settings', [SettingsController::class, 'update'])->name('settings.update')->middleware('role:Administrador');

    // Valores Corporativos - Solo para Administradores
    Route::post('/configuracion/values', [SettingsController::class, 'storeValue'])->name('settings.values.store')->middleware('role:Administrador');
    Route::put('/configuracion/values/{id}', [SettingsController::class, 'updateValue'])->name('settings.values.update')->middleware('role:Administrador');
    Route::delete('/configuracion/values/{id}', [SettingsController::class, 'destroyValue'])->name('settings.values.destroy')->middleware('role:Administrador');
});

//Rutas de perfil
Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');

    // ✅ PROTEGIDO: Rutas de carrito y pagos con validación de integridad
    Route::middleware('venta.integrity')->group(function () {
        Route::post('/carrito/create-venta', [\App\Http\Controllers\PagoController::class, 'createVentaFromCarrito']);
        Route::post('/wompi/payment-link', [\App\Http\Controllers\PagoController::class, 'createPaymentLinkFromCart']);
        Route::post('/pagos/venta', [\App\Http\Controllers\PagoController::class, 'procesarPagoVenta']);
        Route::get('/pagos/{pago}/estado', [\App\Http\Controllers\PagoController::class, 'consultarEstadoPago']);
    });

    // Ruta para completar datos de empleado
    Route::post('empleado/completar-datos', [EmpleadoController::class, 'completarDatos'])->name('empleado.completar-datos')->middleware(['auth']);
});

//Rutas de clientes
Route::get('reservaciones', fn() => Inertia::render('VistasClientes/Reservaciones'))->name('hoteles-clientes');
Route::get('tours-nacionales', fn() => Inertia::render('VistasClientes/ToursNacionales'))->name('tours-nacionales');
Route::get('tours-internacionales', fn() => Inertia::render('VistasClientes/ToursInternacionales'))->name('tours-internacionales');
Route::get('tienda', fn() => Inertia::render('VistasClientes/Tienda'))->name('tienda');
Route::get('sobre-nosotros', [SobreNosotrosController::class, 'index'])->name('sobre-nosotros');
Route::get('contactos', fn() => Inertia::render('VistasClientes/Contactos'))->name('contactos');

//Estas rutas muestran un tour ya sea nacional o internacional
Route::get('/tours-nacionales/{id}', [TourController::class, 'mostrarTourNacional'])->name('tour-nacional.show');
Route::get('/tours-internacionales/{id}', [TourController::class, 'mostrarTourInternacional'])->name('tour-internacional.show');

// Ruta para mostrar el detalle de un producto
Route::get('/tienda/producto/{id}', [ProductoWebController::class, 'mostrarDetalleProducto'])->name('producto.show');

// Ruta para mostrar el detalle de un hotel
Route::get('/hoteles/{id}', [HotelWebController::class, 'mostrarDetalleHotel'])->name('hotel.show');

// Ruta para crear reservas de tours (accesible para usuarios autenticados)
Route::post('/reservas/tour', [ReservaController::class, 'crearReservaTour'])->middleware('auth')->name('reservas.tour');

// Ruta para crear reservas de hoteles (accesible para usuarios autenticados)
Route::post('/reservas/hotel', [ReservaController::class, 'crearReservaHotel'])->middleware('auth')->name('reservas.hotel');

// Ruta para obtener datos del cliente autenticado
Route::get('/api/cliente-datos', [ClienteController::class, 'obtenerDatosAutenticado'])->middleware('auth')->name('cliente.datos');

require __DIR__.'/auth.php';
