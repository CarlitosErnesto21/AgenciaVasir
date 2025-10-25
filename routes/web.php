<?php

use App\Http\Controllers\ProfileController;
use App\Http\Controllers\InformePDFController;
use App\Http\Controllers\RoleController;
use App\Http\Controllers\BackupController;
use App\Http\Controllers\ReservaController;
use App\Http\Controllers\ClienteController;
use App\Http\Controllers\SettingsController;
use App\Http\Controllers\TourController;
use App\Http\Controllers\SobreNosotrosController;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;
use App\Http\Middleware\RutasAdmin;

//Ruta principal
Route::get('/', fn() => Inertia::render('Inicio'))->name('inicio');

// Rutas protegidas
Route::middleware(['auth', 'verified', RutasAdmin::class])->group(function () {

    Route::get('dashboard', fn() => Inertia::render('Dashboard'))->name('dashboard');
    Route::get('transportes', fn() => Inertia::render('Catalogos/Transportes'))->name('transportes');
    Route::get('productos', fn() => Inertia::render('Catalogos/Productos'))->name('productos');
    Route::get('hoteles', fn() => Inertia::render('Catalogos/Hoteles'))->name('hoteles');
    Route::get('gestion-reserva-tours', fn() => Inertia::render('Catalogos/ReservaTours'))->name('reservatours');
    Route::get('tours', fn() => Inertia::render('Catalogos/Tours'))->name('tours');
    Route::get('aerolineas', fn() => Inertia::render('Catalogos/Aerolineas'))->name('aerolineas');
    Route::get('control-paises-provincias', fn() => Inertia::render('Catalogos/ControlPaisesProvincias'))->name('controlPaisesProvincias');
    Route::get('tipo-documentos', fn() => Inertia::render('Configuracion/TiposDocumento'))->name('tipodocumentos');
    Route::get('generar-informes', fn() => Inertia::render('Informes/Informes'))->name('informes');
    Route::get('clientes', fn() => Inertia::render('Configuracion/Clientes'))->name('clientes');
    Route::get('categorias-productos', fn() => Inertia::render('Catalogos/CategoriaProductos'))->name('catProductos');
    Route::get('categorias-hoteles', fn() => Inertia::render('Catalogos/CategoriaHoteles'))->name('catHoteles');
    Route::get('gestion-paquetes', fn() => Inertia::render('Catalogos/GestionPaquetes'))->name('gestionPaquetes');
    Route::get('ventas', fn() => Inertia::render('Catalogos/Ventas'))->name('ventas');
    Route::get('inventario', fn() => Inertia::render('Catalogos/Inventarios'))->name('inventario');

    //Rutas para informes
    Route::get('/descargar-informe', [InformePDFController::class, 'descargarInforme']);
    Route::get('/configuracion/backup', [BackupController::class, 'showBackupPage'])->name('backups')->middleware('password.confirm');
    Route::get('/configuracion/roles', [RoleController::class, 'index'])->name('roles')->middleware('role:Administrador');

    //Rutas para gestión de roles
    Route::post('/roles/users/{user}/update-roles', [RoleController::class, 'updateUserRoles'])->middleware('role:Administrador');
    Route::post('/roles/users/{user}/assign-role', [RoleController::class, 'assignRole'])->middleware('role:Administrador');
    Route::post('/roles/users/{user}/remove-role', [RoleController::class, 'removeRole'])->middleware('role:Administrador');
    Route::get('/roles/users/{user}/permissions', [RoleController::class, 'getUserPermissions'])->middleware('role:Administrador');
    Route::post('/roles/users/{user}/assign-permission', [RoleController::class, 'assignPermission'])->middleware('role:Administrador');
    Route::post('/roles/users/{user}/remove-permission', [RoleController::class, 'removePermission'])->middleware('role:Administrador');
    Route::put('/roles/users/{user}/sync-permissions', [RoleController::class, 'syncPermissions'])->middleware('role:Administrador');
    Route::post('/roles/create-user', [RoleController::class, 'createInternalUser'])->middleware('role:Administrador');
    Route::get('/roles/users/{user}/employee', [RoleController::class, 'getEmployeeData'])->middleware('role:Administrador');
    Route::put('/roles/users/{user}/employee', [RoleController::class, 'updateEmployeeData'])->middleware('role:Administrador');

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

    // Rutas de carrito y pagos (requieren autenticación web)
    Route::post('/carrito/create-venta', [\App\Http\Controllers\PagoController::class, 'createVentaFromCarrito']);
    Route::post('/wompi/payment-link', [\App\Http\Controllers\PagoController::class, 'createPaymentLinkFromCart']);
    Route::post('/pagos/venta', [\App\Http\Controllers\PagoController::class, 'procesarPagoVenta']);
    Route::get('/pagos/{pago}/estado', [\App\Http\Controllers\PagoController::class, 'consultarEstadoPago']);
});

//Rutas de clientes
Route::get('paquetes', fn() => Inertia::render('VistasClientes/Paquetes'))->name('paquetes');
Route::get('reservaciones', fn() => Inertia::render('VistasClientes/Reservaciones'))->name('reservaciones');
Route::get('tours-nacionales', fn() => Inertia::render('VistasClientes/ToursNacionales'))->name('tours-nacionales');
Route::get('tours-internacionales', fn() => Inertia::render('VistasClientes/ToursInternacionales'))->name('tours-internacionales');
Route::get('tienda', fn() => Inertia::render('VistasClientes/Tienda'))->name('tienda');
Route::get('sobre-nosotros', [SobreNosotrosController::class, 'index'])->name('sobre-nosotros');
Route::get('contactos', fn() => Inertia::render('VistasClientes/Contactos'))->name('contactos');

//Estas rutas muestran un tour ya sea nacional o internacional
Route::get('/tours-nacionales/{id}', [TourController::class, 'mostrarTourNacional'])->name('tour-nacional.show');
Route::get('/tours-internacionales/{id}', [TourController::class, 'mostrarTourInternacional'])->name('tour-internacional.show');

// Ruta para crear reservas de tours (accesible para usuarios autenticados)
Route::post('/reservas/tour', [ReservaController::class, 'crearReservaTour'])->middleware('auth')->name('reservas.tour');

// Ruta para obtener datos del cliente autenticado
Route::get('/api/cliente-datos', [ClienteController::class, 'obtenerDatosAutenticado'])->middleware('auth')->name('cliente.datos');

require __DIR__.'/auth.php';
