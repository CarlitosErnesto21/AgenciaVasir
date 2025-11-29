<?php

namespace App\Http\Controllers;

use App\Models\Reserva;
use App\Models\Cliente;
use App\Models\DetalleReservaTour;
use App\Models\Tour;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use App\Mail\ReservationConfirmedMail;
use App\Mail\ReservationRejectedMail;
use App\Mail\NewReservationClientMail;
use App\Mail\NewReservationStaffMail;
use App\Mail\ReservationRescheduledMail;
use App\Mail\ReservationCompletedMail;
use App\Models\Pago;
use Carbon\Carbon;

class ReservaController extends Controller
{
    /**
     * Display a listing of the resource with advanced filters for API.
     */
    public function index(Request $request)
    {
        try {
            $query = Reserva::with(['cliente', 'cliente.user', 'detallesTours.tour', 'pagos', 'pagoActivo']);

            // Aplicar filtros por tipo (solo tours por ahora)
            if ($request->filled('tipo')) {
                switch ($request->tipo) {
                    case 'tours':
                        $query->whereHas('detallesTours');
                        break;
                    // Para hoteles se pueden agregar más adelante
                    case 'hoteles':
                        // $query->whereHas('detallesHoteles');
                        break;
                }
            }

            if ($request->filled('estado')) {
                $query->where('estado', $request->estado);
            }

            if ($request->filled('fecha_inicio')) {
                $query->whereDate('fecha', '>=', $request->fecha_inicio);
            }

            if ($request->filled('fecha_fin')) {
                $query->whereDate('fecha', '<=', $request->fecha_fin);
            }

            // Soporte para parámetro 'desde' del dashboard
            if ($request->filled('desde')) {
                $query->whereDate('fecha', '>=', $request->desde);
            }

            if ($request->filled('busqueda')) {
                $busqueda = $request->busqueda;
                $query->whereHas('cliente.user', function ($q) use ($busqueda) {
                    $q->where('name', 'like', "%{$busqueda}%")
                      ->orWhere('email', 'like', "%{$busqueda}%");
                })->orWhereHas('detallesTours.tour', function ($q) use ($busqueda) {
                    $q->where('nombre', 'like', "%{$busqueda}%");
                });
            }

            // Filtro por tour específico
            if ($request->filled('tour_id')) {
                $query->whereHas('detallesTours.tour', function ($q) use ($request) {
                    $q->where('id', $request->tour_id);
                });
            }

            // Si no hay filtros, devolver formato transformado con relaciones completas
            if (!$request->hasAny(['tipo', 'estado', 'fecha_inicio', 'fecha_fin', 'busqueda', 'per_page', 'tour_id', 'desde'])) {
                $reservas = Reserva::with(['cliente', 'cliente.user', 'empleado', 'detallesTours.tour', 'pagos', 'pagoActivo'])->get();

                // Transformar los datos igual que en el flujo paginado
                $transformedData = $reservas->map(function ($reserva) {
                    $tourNombre = $reserva->detallesTours->first() ?
                                 $reserva->detallesTours->first()->tour->nombre : 'N/A';

                    return [
                        'id' => $reserva->id,
                        'fecha_reserva' => $reserva->fecha,
                        'estado' => $reserva->estado,
                        'cliente' => $reserva->cliente ? [
                            'id' => $reserva->cliente->id,
                            'nombres' => $reserva->cliente->user ? $reserva->cliente->user->name : 'Cliente no asignado',
                            'correo' => $reserva->cliente->user ? $reserva->cliente->user->email : 'Sin correo',
                            'telefono' => $reserva->cliente->telefono,
                            'numero_identificacion' => $reserva->cliente->numero_identificacion,
                            'user' => $reserva->cliente->user ? [
                                'name' => $reserva->cliente->user->name,
                                'email' => $reserva->cliente->user->email
                            ] : null
                        ] : null,
                        'empleado' => $reserva->empleado,
                        'entidad_nombre' => $tourNombre,
                        'tipo' => 'Tour',
                        'total' => $reserva->total,
                        'mayores_edad' => $reserva->mayores_edad,
                        'menores_edad' => $reserva->menores_edad,
                        'pagos' => $reserva->pagos
                    ];
                });

                return response()->json($transformedData);
            }

            $reservas = $query->orderBy('fecha', 'desc')
                           ->paginate($request->get('per_page', 15));

            // Transformar los datos para la respuesta
            $transformedData = $reservas->getCollection()->map(function ($reserva) {
                $tourNombre = $reserva->detallesTours->first() ?
                             $reserva->detallesTours->first()->tour->nombre : 'N/A';

                return [
                    'id' => $reserva->id,
                    'fecha_reserva' => $reserva->fecha,
                    'estado' => $reserva->estado,
                    'cliente' => $reserva->cliente ? [
                        'id' => $reserva->cliente->id,
                        'nombres' => $reserva->cliente->user ? $reserva->cliente->user->name : 'Cliente no asignado',
                        'correo' => $reserva->cliente->user ? $reserva->cliente->user->email : 'Sin correo',
                        'telefono' => $reserva->cliente->telefono,
                        'numero_identificacion' => $reserva->cliente->numero_identificacion,
                        'user' => $reserva->cliente->user ? [
                            'name' => $reserva->cliente->user->name,
                            'email' => $reserva->cliente->user->email
                        ] : null
                    ] : null,
                    'empleado' => $reserva->empleado,
                    'entidad_nombre' => $tourNombre,
                    'tipo' => 'Tour',
                    'total' => $reserva->total,
                    'mayores_edad' => $reserva->mayores_edad,
                    'menores_edad' => $reserva->menores_edad,
                    'pagos' => $reserva->pagos
                ];
            });

            return response()->json([
                'success' => true,
                'data' => $transformedData,
                'pagination' => [
                    'current_page' => $reservas->currentPage(),
                    'total_pages' => $reservas->lastPage(),
                    'per_page' => $reservas->perPage(),
                    'total' => $reservas->total()
                ]
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al obtener las reservas',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        // Validar los datos del formulario
        $validated = $request->validate([
            'fecha' => 'required|date',
            'estado' => 'required|in:PENDIENTE,CONFIRMADA,RECHAZADA,REPROGRAMADA,FINALIZADA',
            'mayores_edad' => 'required|integer|min:1',
            'menores_edad' => 'nullable|integer|min:0',
            'cliente_id' => 'required|exists:clientes,id',
            'empleado_id' => 'required|exists:empleados,id',
            'total' => 'required|numeric|min:0'
        ]);

        // Crear una nueva reserva
        $reserva = Reserva::create($validated);

        return response()->json([
            'message' => 'Reserva creada exitosamente',
            'reserva' => $reserva,
        ]);
    }

    /**
     * Crear reserva de hotel desde el modal del cliente
     */
    public function crearReservaHotel(Request $request)
    {
        try {
            // Verificar que el usuario esté autenticado
            if (!Auth::check()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Debe iniciar sesión para realizar una reserva.'
                ], 401);
            }

            $user = Auth::user();

            // Verificar que el usuario tenga rol de cliente usando consulta directa
            $hasClienteRole = DB::table('model_has_roles')
                ->join('roles', 'model_has_roles.role_id', '=', 'roles.id')
                ->where('model_has_roles.model_type', 'App\\Models\\User')
                ->where('model_has_roles.model_id', $user->id)
                ->where('roles.name', 'cliente')
                ->exists();

            if (!$hasClienteRole) {
                return response()->json([
                    'success' => false,
                    'message' => 'Solo los usuarios con rol de cliente pueden generar reservas. Su rol actual no permite esta acción.'
                ], 403);
            }

            // Validar los datos de entrada
            $validated = $request->validate([
                'hotel_id' => 'required|exists:hoteles,id',
                'fecha_entrada' => 'required|date|after:today',
                'fecha_salida' => 'required|date|after:fecha_entrada',
                'cantidad_personas' => 'required|integer|min:1|max:10',
                'cantidad_habitaciones' => 'required|integer|min:1|max:5',
                'cliente_data' => 'required|array',
                'cliente_data.numero_identificacion' => 'required|string|max:25',
                'cliente_data.fecha_nacimiento' => 'required|date|before:today',
                'cliente_data.genero' => 'required|in:MASCULINO,FEMENINO',
                'cliente_data.direccion' => 'required|string|max:200',
                'cliente_data.telefono' => 'required|string|max:30',
                'cliente_data.tipo_documento' => 'required|in:DUI,PASAPORTE'
            ]);

            // Validar edad mínima de 18 años usando la función del ClienteController
            $clienteController = new \App\Http\Controllers\ClienteController();
            $ageValidationResult = $clienteController->validateMinimumAge($validated['cliente_data']['fecha_nacimiento']);
            if (!$ageValidationResult['success']) {
                return response()->json($ageValidationResult, 422);
            }

            // Normalizar número de identificación si es PASAPORTE
            if ($validated['cliente_data']['tipo_documento'] === 'PASAPORTE') {
                $validated['cliente_data']['numero_identificacion'] = strtoupper(preg_replace('/[\s-]/', '', $validated['cliente_data']['numero_identificacion']));
            }

            DB::beginTransaction();

            // 1. Verificar si ya existe un cliente para este usuario
            $cliente = Cliente::where('user_id', $user->id)->first();

            if (!$cliente) {
                // 2. Verificar si ya existe un cliente con este número de identificación
                $clienteExistente = Cliente::where('numero_identificacion', $validated['cliente_data']['numero_identificacion'])->first();

                if ($clienteExistente) {
                    DB::rollback();
                    return response()->json([
                        'success' => false,
                        'warning' => true,
                        'message' => 'No puedes usar este número de identificación porque pertenece a otro usuario.'
                    ], 400);
                }

                // 2.1. Verificar si ya existe un cliente con este teléfono
                $clienteConTelefono = Cliente::where('telefono', $validated['cliente_data']['telefono'])->first();

                if ($clienteConTelefono) {
                    DB::rollback();
                    return response()->json([
                        'success' => false,
                        'warning' => true,
                        'message' => 'No puedes usar este número de teléfono porque pertenece a otro usuario.'
                    ], 400);
                }

                // 3. Crear el cliente si no existe
                $cliente = Cliente::create([
                    'numero_identificacion' => $validated['cliente_data']['numero_identificacion'],
                    'fecha_nacimiento' => $validated['cliente_data']['fecha_nacimiento'],
                    'genero' => strtoupper($validated['cliente_data']['genero']),
                    'direccion' => $validated['cliente_data']['direccion'],
                    'telefono' => $validated['cliente_data']['telefono'],
                    'user_id' => $user->id,
                    'tipo_documento' => $validated['cliente_data']['tipo_documento']
                ]);
            } else {
                // Verificar si el número de identificación que se quiere actualizar ya existe en otro cliente
                $clienteConMismoDocumento = Cliente::where('numero_identificacion', $validated['cliente_data']['numero_identificacion'])
                    ->where('id', '!=', $cliente->id)
                    ->first();

                if ($clienteConMismoDocumento) {
                    DB::rollback();
                    return response()->json([
                        'success' => false,
                        'warning' => true,
                        'message' => 'No puedes usar este número de identificación porque pertenece a otro usuario.'
                    ], 400);
                }

                // Verificar si el teléfono que se quiere actualizar ya existe en otro cliente
                $clienteConMismoTelefono = Cliente::where('telefono', $validated['cliente_data']['telefono'])
                    ->where('id', '!=', $cliente->id)
                    ->first();

                if ($clienteConMismoTelefono) {
                    DB::rollback();
                    return response()->json([
                        'success' => false,
                        'warning' => true,
                        'message' => 'No puedes usar este número de teléfono porque pertenece a otro usuario.'
                    ], 400);
                }

                // Actualizar datos del cliente existente
                $cliente->update([
                    'numero_identificacion' => $validated['cliente_data']['numero_identificacion'],
                    'fecha_nacimiento' => $validated['cliente_data']['fecha_nacimiento'],
                    'genero' => strtoupper($validated['cliente_data']['genero']),
                    'direccion' => $validated['cliente_data']['direccion'],
                    'telefono' => $validated['cliente_data']['telefono'],
                    'tipo_documento' => $validated['cliente_data']['tipo_documento']
                ]);
            }

            // 3. Obtener información del hotel
            $hotel = \App\Models\Hotel::findOrFail($validated['hotel_id']);

            // 4. Calcular precio estimado (por ahora será 0, se puede ajustar según lógica de negocio)
            $precio_estimado = 0; // Se puede calcular según días * habitaciones * tarifa del hotel

            // 5. Crear la reserva (sin empleado asignado inicialmente)
            $reserva = Reserva::create([
                'fecha' => Carbon::now(), // Fecha y hora actual cuando se hace la reserva
                'estado' => Reserva::PENDIENTE,
                'mayores_edad' => $validated['cantidad_personas'],
                'menores_edad' => 0, // Para hoteles no manejamos menores por separado
                'total' => $precio_estimado,
                'cliente_id' => $cliente->id,
                'empleado_id' => null // El empleado será asignado posteriormente
            ]);



            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Reserva de hotel creada exitosamente',
                'data' => [
                    'reserva' => $reserva,
                    'cliente' => $cliente,
                    'hotel' => $hotel
                ]
            ]);

        } catch (\Illuminate\Validation\ValidationException $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Error de validación',
                'errors' => $e->errors()
            ], 422);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Error interno del servidor: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Crear reserva de tour desde el modal del cliente
     */
    public function crearReservaTour(Request $request)
    {
        try {
            // Verificar que el usuario esté autenticado
            if (!Auth::check()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Debe iniciar sesión para realizar una reserva.'
                ], 401);
            }

            $user = Auth::user();

            // Verificar que el usuario tenga rol de cliente usando consulta directa
            $hasClienteRole = DB::table('model_has_roles')
                ->join('roles', 'model_has_roles.role_id', '=', 'roles.id')
                ->where('model_has_roles.model_type', 'App\\Models\\User')
                ->where('model_has_roles.model_id', $user->id)
                ->where('roles.name', 'cliente')
                ->exists();

            if (!$hasClienteRole) {
                return response()->json([
                    'success' => false,
                    'message' => 'Solo los usuarios con rol de cliente pueden generar reservas. Su rol actual no permite esta acción.'
                ], 403);
            }

            // Validar los datos de entrada
            $validated = $request->validate([
                'tour_id' => 'required|exists:tours,id',
                'cupos_adultos' => 'required|integer|min:1',
                'cupos_menores' => 'nullable|integer|min:0',
                'cliente_data' => 'required|array',
                'cliente_data.numero_identificacion' => 'required|string|max:25',
                'cliente_data.fecha_nacimiento' => 'required|date',
                'cliente_data.genero' => 'required|in:MASCULINO,FEMENINO',
                'cliente_data.direccion' => 'required|string|max:200',
                'cliente_data.telefono' => 'required|string|max:30',
                'cliente_data.tipo_documento' => 'required|in:DUI,PASAPORTE'
            ]);

            // Validar edad mínima de 18 años
            $clienteController = new \App\Http\Controllers\ClienteController();
            $ageValidationResult = $clienteController->validateMinimumAge($validated['cliente_data']['fecha_nacimiento']);
            if (!$ageValidationResult['success']) {
                return response()->json($ageValidationResult, 422);
            }

            // Normalizar número de identificación si es PASAPORTE
            if ($validated['cliente_data']['tipo_documento'] === 'PASAPORTE') {
                $validated['cliente_data']['numero_identificacion'] = strtoupper(preg_replace('/[\s-]/', '', $validated['cliente_data']['numero_identificacion']));
            }

            // Validar que la fecha de nacimiento sea válida y no sea futura
            try {
                $fechaNacimientoCarbon = Carbon::parse($validated['cliente_data']['fecha_nacimiento']);
                if ($fechaNacimientoCarbon->isFuture()) {
                    return response()->json([
                        'success' => false,
                        'message' => 'La fecha de nacimiento no puede ser futura.'
                    ], 422);
                }
            } catch (\Exception $e) {
                return response()->json([
                    'success' => false,
                    'message' => 'Formato de fecha de nacimiento inválido.'
                ], 422);
            }

            DB::beginTransaction();

            // 1. Verificar si ya existe un cliente para este usuario
            $cliente = Cliente::where('user_id', $user->id)->first();

            // Formatear la fecha de nacimiento para MySQL
            $fechaNacimiento = $fechaNacimientoCarbon->format('Y-m-d');

            if (!$cliente) {
                // 2. Verificar si ya existe un cliente con este número de identificación
                $clienteExistente = Cliente::where('numero_identificacion', $validated['cliente_data']['numero_identificacion'])->first();

                if ($clienteExistente) {
                    DB::rollback();
                    return response()->json([
                        'success' => false,
                        'warning' => true,
                        'message' => 'No puedes usar este número de identificación porque pertenece a otro usuario.'
                    ], 400);
                }

                // 2.1. Verificar si ya existe un cliente con este teléfono
                $clienteConTelefono = Cliente::where('telefono', $validated['cliente_data']['telefono'])->first();

                if ($clienteConTelefono) {
                    DB::rollback();
                    return response()->json([
                        'success' => false,
                        'warning' => true,
                        'message' => 'No puedes usar este número de teléfono porque pertenece a otro usuario.'
                    ], 400);
                }

                // 3. Crear el cliente si no existe
                $cliente = Cliente::create([
                    'numero_identificacion' => $validated['cliente_data']['numero_identificacion'],
                    'fecha_nacimiento' => $fechaNacimiento,
                    'genero' => strtoupper($validated['cliente_data']['genero']),
                    'direccion' => $validated['cliente_data']['direccion'],
                    'telefono' => $validated['cliente_data']['telefono'],
                    'user_id' => $user->id,
                    'tipo_documento' => $validated['cliente_data']['tipo_documento']
                ]);

                // Recargar cliente con la relación user
                $cliente = $cliente->fresh(['user']);
            } else {
                // Verificar si el número de identificación que se quiere actualizar ya existe en otro cliente
                $clienteConMismoDocumento = Cliente::where('numero_identificacion', $validated['cliente_data']['numero_identificacion'])
                    ->where('id', '!=', $cliente->id)
                    ->first();

                if ($clienteConMismoDocumento) {
                    DB::rollback();
                    return response()->json([
                        'success' => false,
                        'warning' => true,
                        'message' => 'No puedes usar este número de identificación porque pertenece a otro usuario.'
                    ], 400);
                }

                // Verificar si el teléfono que se quiere actualizar ya existe en otro cliente
                $clienteConMismoTelefono = Cliente::where('telefono', $validated['cliente_data']['telefono'])
                    ->where('id', '!=', $cliente->id)
                    ->first();

                if ($clienteConMismoTelefono) {
                    DB::rollback();
                    return response()->json([
                        'success' => false,
                        'warning' => true,
                        'message' => 'No puedes usar este número de teléfono porque pertenece a otro usuario.'
                    ], 400);
                }

                // Actualizar datos del cliente existente
                $cliente->update([
                    'numero_identificacion' => $validated['cliente_data']['numero_identificacion'],
                    'fecha_nacimiento' => $fechaNacimiento,
                    'genero' => strtoupper($validated['cliente_data']['genero']),
                    'direccion' => $validated['cliente_data']['direccion'],
                    'telefono' => $validated['cliente_data']['telefono'],
                    'tipo_documento' => $validated['cliente_data']['tipo_documento']
                ]);
            }

            // 3. Obtener información del tour
            $tour = Tour::findOrFail($validated['tour_id']);

            // 3.1. Verificar que el cliente no tenga ya una reserva activa para este tour
            $reservaExistente = Reserva::where('cliente_id', $cliente->id)
                ->whereHas('detallesTours', function($query) use ($validated) {
                    $query->where('tour_id', $validated['tour_id']);
                })
                ->whereNotIn('estado', [Reserva::CANCELADA, Reserva::FINALIZADA])
                ->first();

            if ($reservaExistente) {
                DB::rollBack();
                return response()->json([
                    'success' => false,
                    'message' => "Ya tiene una reserva activa para este tour (Estado: {$reservaExistente->estado}). No puede reservar el mismo tour múltiples veces. Si necesita modificar su reserva, póngase en contacto con nosotros.",
                    'data' => [
                        'reserva_existente' => $reservaExistente->load('detallesTours.tour')
                    ]
                ], 422);
            }

            // 3.2. Verificar que el cliente no tenga reservas activas en la misma fecha de salida
            $fechaSalidaTour = Carbon::parse($tour->fecha_salida)->toDateString();

            $reservaEnMismaFecha = Reserva::where('cliente_id', $cliente->id)
                ->whereHas('detallesTours.tour', function($query) use ($fechaSalidaTour) {
                    $query->whereDate('fecha_salida', $fechaSalidaTour);
                })
                ->whereNotIn('estado', [Reserva::CANCELADA, Reserva::FINALIZADA])
                ->with('detallesTours.tour')
                ->first();

            if ($reservaEnMismaFecha) {
                $tourConflicto = $reservaEnMismaFecha->detallesTours->first()->tour ?? null;
                $nombreTourConflicto = $tourConflicto ? $tourConflicto->nombre : 'Tour desconocido';

                DB::rollBack();
                return response()->json([
                    'success' => false,
                    'message' => "Ya tiene una reserva activa para el día " . Carbon::parse($fechaSalidaTour)->format('d/m/Y') . " (Tour: {$nombreTourConflicto}). No puede estar en dos tours el mismo día. Si necesita cambiar su reserva, póngase en contacto con nosotros.",
                    'data' => [
                        'reserva_en_misma_fecha' => $reservaEnMismaFecha,
                        'tour_en_conflicto' => $nombreTourConflicto
                    ]
                ], 422);
            }

            // 4. Calcular totales
            $cupos_totales = $validated['cupos_adultos'] + ($validated['cupos_menores'] ?? 0);

            // 5. Verificar cupos disponibles
            $cuposDisponibles = $tour->cupos_disponibles;
            if ($cupos_totales > $cuposDisponibles) {
                return response()->json([
                    'success' => false,
                    'message' => "Solo hay {$cuposDisponibles} cupos disponibles para este tour. Usted está intentando reservar {$cupos_totales} cupos."
                ], 422);
            }

            $precio_total = $cupos_totales * $tour->precio;

            // 6. Crear la reserva (sin empleado asignado inicialmente)
            $reserva = Reserva::create([
                'fecha' => Carbon::now(),
                'estado' => 'PENDIENTE',
                'mayores_edad' => $validated['cupos_adultos'],
                'menores_edad' => $validated['cupos_menores'] ?? null,
                'total' => $precio_total,
                'cliente_id' => $cliente->id,
                'empleado_id' => null // El empleado será asignado posteriormente
            ]);

            // 7. Crear el detalle de reserva de tour
            $detalleReserva = DetalleReservaTour::create([
                'cupos_reservados' => $cupos_totales,
                'precio_unitario' => $tour->precio,
                'precio_total' => $precio_total,
                'reserva_id' => $reserva->id,
                'tour_id' => $tour->id
            ]);

            DB::commit();

            // Refrescar el tour para obtener los cupos actualizados
            $tour->refresh();

            // Recalcular cupos disponibles después de la reserva
            $cuposReservadosTotal = $tour->detalleReservas()
                ->whereHas('reserva', function($query) {
                    $query->where('estado', '!=', Reserva::CANCELADA);
                })
                ->sum('cupos_reservados');

            $cuposDisponiblesActualizados = max(0, $tour->cupo_max - $cuposReservadosTotal);

            // Cambiar estado del tour automáticamente si se completó el cupo máximo
            if ($cuposReservadosTotal >= $tour->cupo_max && $tour->estado === 'DISPONIBLE') {
                $tour->update(['estado' => 'COMPLETO']);
                Log::info("Tour {$tour->id} cambió automáticamente a estado COMPLETO - cupos reservados: {$cuposReservadosTotal}/{$tour->cupo_max}");
            }

            // Debug log
            Log::info("Después de reserva - Tour {$tour->id}: cupo_max={$tour->cupo_max}, reservados={$cuposReservadosTotal}, disponibles={$cuposDisponiblesActualizados}");

            // Enviar correos de notificación
            try {
                // Preparar datos para los correos
                $reservationData = [
                    'id' => $reserva->id,
                    'estado' => $reserva->estado,
                    'mayores_edad' => $reserva->mayores_edad,
                    'menores_edad' => $reserva->menores_edad,
                    'precio_total' => $detalleReserva->precio_total,
                    'created_at' => $reserva->created_at
                ];

                $clientData = [
                    'name' => $cliente->user ? $cliente->user->name : 'Cliente Sin Nombre',
                    'nombres' => $cliente->user ? $cliente->user->name : 'Sin nombre',
                    'apellidos' => '',
                    'nombre_completo' => $cliente->user ? $cliente->user->name : 'Cliente Sin Nombre',
                    'email' => $user->email,
                    'telefono' => $cliente->telefono,
                    'tipo_documento' => $cliente->tipo_documento,
                    'numero_identificacion' => $cliente->numero_identificacion,
                    'fecha_nacimiento' => $cliente->fecha_nacimiento,
                    'genero' => $cliente->genero,
                    'direccion' => $cliente->direccion
                ];

                $tourData = [
                    'nombre' => $tour->nombre,
                    'categoria' => $tour->categoria,
                    'estado' => $tour->estado,
                    'fecha_salida' => $tour->fecha_salida,
                    'fecha_regreso' => $tour->fecha_regreso,
                    'cupo_min' => $tour->cupo_min,
                    'cupo_max' => $tour->cupo_max,
                    'precio' => $tour->precio,
                    'cupos_disponibles' => $cuposDisponiblesActualizados
                ];

                // 1. Enviar correo al cliente
                Mail::to($user->email)->send(new NewReservationClientMail($reservationData, $clientData, $tourData));
                Log::info("Correo de nueva reserva enviado al cliente: {$user->email}");

                // 2. Enviar correos a empleados y administradores
                $staffUsers = User::role(['Empleado', 'Administrador'])->get();

                foreach ($staffUsers as $staffUser) {
                    Mail::to($staffUser->email)->send(new NewReservationStaffMail($reservationData, $clientData, $tourData, $staffUser));
                    Log::info("Correo de notificación de nueva reserva enviado a staff: {$staffUser->email}");
                }

            } catch (\Exception $emailException) {
                // Los correos fallan pero la reserva ya se creó exitosamente
                Log::error("Error enviando correos de nueva reserva: " . $emailException->getMessage());
            }

            return response()->json([
                'success' => true,
                'message' => 'Reserva creada exitosamente',
                'data' => [
                    'reserva' => $reserva,
                    'cliente' => $cliente,
                    'detalle' => $detalleReserva,
                    'tour' => $tour,
                    'cupos_disponibles_actualizados' => $cuposDisponiblesActualizados
                ]
            ]);

        } catch (\Illuminate\Validation\ValidationException $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Error de validación',
                'errors' => $e->errors()
            ], 422);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Error interno del servidor: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Display the specified resource.
     */
    public function show(Reserva $reserva)
    {
        // Mostrar los detalles de una reserva específica con sus relaciones
        $reserva->load(['cliente', 'cliente.user', 'empleado', 'pagos', 'pagoActivo']);
        return response()->json($reserva);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Reserva $reserva)
    {
        // Validar los datos del formulario
        $validated = $request->validate([
            'fecha' => 'required|date',
            'estado' => 'required|in:' . implode(',', Reserva::ESTADOS),
            'mayores_edad' => 'required|integer|min:1',
            'menores_edad' => 'nullable|integer|min:0',
            'cliente_id' => 'required|exists:clientes,id',
            'empleado_id' => 'required|exists:empleados,id',
            'total' => 'required|numeric|min:0'
        ]);

        // Verificar si el estado está cambiando a CANCELADA para reajustar cupos
        $estadoAnterior = $reserva->estado;
        $estadoNuevo = $validated['estado'];

        // Actualizar la reserva
        $reserva->update($validated);

        // MANEJAR CAMBIOS DE ESTADO ESPECIALES
        if ($estadoAnterior !== $estadoNuevo) {
            // Caso 1: Cambio de activa a CANCELADA - eliminar reserva completamente
            if ($estadoAnterior !== 'CANCELADA' && $estadoNuevo === 'CANCELADA') {
                // Cargar relaciones antes de eliminar
                $reserva->load(['detallesTours.tour']);

                // Liberar cupos antes de eliminar
                $this->liberarCuposReserva($reserva, 'eliminación por actualización a CANCELADA');

                // Eliminar la reserva
                $reservaId = $reserva->id;

                // 1. Eliminar pagos asociados (si existen)
                \App\Models\Pago::where('reserva_id', $reserva->id)->delete();

                // 2. Eliminar manualmente los detalles (por si CASCADE no funciona)
                \App\Models\DetalleReservaTour::where('reserva_id', $reserva->id)->delete();

                // 3. Luego eliminar la reserva
                $reserva->delete();

                Log::info('Reserva eliminada por actualización a CANCELADA', [
                    'reserva_id' => $reservaId,
                    'estado_anterior' => $estadoAnterior
                ]);

                return response()->json([
                    'message' => 'Reserva eliminada exitosamente al cambiar estado a CANCELADA',
                    'reserva' => ['id' => $reservaId, 'eliminada' => true],
                ]);
            }

            // Caso 2: Cambio de CANCELADA a activa - esto no debería pasar ya que eliminamos las canceladas
            // Pero lo mantenemos por si acaso
            elseif ($estadoAnterior === 'CANCELADA' && $estadoNuevo !== 'CANCELADA') {
                $reserva = $reserva->fresh(['detallesTours.tour']);
                $this->ocuparCuposReserva($reserva, 'reactivación desde CANCELADA');
            }
        }

        return response()->json([
            'message' => 'Reserva actualizada exitosamente',
            'reserva' => $reserva,
        ]);
    }

    /**
     * Liberar cupos de una reserva (cuando se cancela)
     */
    private function liberarCuposReserva($reserva, $motivo = 'cancelación')
    {
        if ($reserva->detallesTours && $reserva->detallesTours->isNotEmpty()) {
            foreach ($reserva->detallesTours as $detalle) {
                if ($detalle->tour) {
                    $tour = $detalle->tour;
                    $personasLiberadas = ($reserva->mayores_edad ?? 0) + ($reserva->menores_edad ?? 0);

                    // Incrementar cupos disponibles
                    $nuevos_cupos = ($tour->cupos_disponibles ?? 0) + $personasLiberadas;

                    // Verificar si el tour debe cambiar de estado
                    $nuevo_estado_tour = $tour->estado;
                    if ($tour->estado === 'COMPLETO' && $nuevos_cupos > 0) {
                        $nuevo_estado_tour = 'DISPONIBLE';
                    }
                    // Si el tour estaba CANCELADA pero ahora no tiene reservas, debe volver a DISPONIBLE
                    elseif ($tour->estado === 'CANCELADA') {
                        // Verificar si quedan reservas activas en este tour
                        $reservasActivas = Reserva::whereHas('detallesTours', function($query) use ($tour) {
                            $query->where('tour_id', $tour->id);
                        })
                        ->whereIn('estado', [Reserva::PENDIENTE, Reserva::CONFIRMADA, Reserva::EN_CURSO, Reserva::REPROGRAMADA])
                        ->where('id', '!=', $reserva->id) // Excluir la reserva que se está eliminando
                        ->count();

                        if ($reservasActivas === 0) {
                            $nuevo_estado_tour = 'DISPONIBLE';
                        }
                    }

                    $tour->update([
                        'cupos_disponibles' => $nuevos_cupos,
                        'estado' => $nuevo_estado_tour
                    ]);

                    Log::info("Cupos liberados por {$motivo}", [
                        'reserva_id' => $reserva->id,
                        'tour_id' => $tour->id,
                        'personas_liberadas' => $personasLiberadas,
                        'cupos_anteriores' => $tour->cupos_disponibles - $personasLiberadas,
                        'cupos_nuevos' => $nuevos_cupos,
                        'estado_anterior_tour' => $tour->estado === $nuevo_estado_tour ? 'sin cambio' : $tour->estado,
                        'estado_nuevo_tour' => $nuevo_estado_tour
                    ]);
                }
            }
        }
    }

    /**
     * Ocupar cupos de una reserva (cuando se reactiva)
     */
    private function ocuparCuposReserva($reserva, $motivo = 'reactivación')
    {
        if ($reserva->detallesTours && $reserva->detallesTours->isNotEmpty()) {
            foreach ($reserva->detallesTours as $detalle) {
                if ($detalle->tour) {
                    $tour = $detalle->tour;
                    $personasOcupadas = ($reserva->mayores_edad ?? 0) + ($reserva->menores_edad ?? 0);

                    // Decrementar cupos disponibles
                    $nuevos_cupos = max(0, ($tour->cupos_disponibles ?? 0) - $personasOcupadas);

                    // Verificar si el tour debe cambiar a COMPLETO
                    $nuevo_estado_tour = $tour->estado;
                    if ($nuevos_cupos <= 0 && $tour->estado === 'DISPONIBLE') {
                        $nuevo_estado_tour = 'COMPLETO';
                    }

                    $tour->update([
                        'cupos_disponibles' => $nuevos_cupos,
                        'estado' => $nuevo_estado_tour
                    ]);

                    Log::info("Cupos ocupados por {$motivo}", [
                        'reserva_id' => $reserva->id,
                        'tour_id' => $tour->id,
                        'personas_ocupadas' => $personasOcupadas,
                        'cupos_anteriores' => $tour->cupos_disponibles + $personasOcupadas,
                        'cupos_nuevos' => $nuevos_cupos,
                        'estado_anterior_tour' => $tour->estado === $nuevo_estado_tour ? 'sin cambio' : $tour->estado,
                        'estado_nuevo_tour' => $nuevo_estado_tour
                    ]);
                }
            }
        }
    }



    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Reserva $reserva)
    {
        // REAJUSTAR CUPOS: Liberar cupos antes de eliminar la reserva
        // Solo si la reserva no estaba ya cancelada
        if ($reserva->estado !== 'CANCELADA') {
            // Cargar relaciones antes de eliminar
            $reserva->load(['detallesTours.tour']);
            $this->liberarCuposReserva($reserva, 'eliminación de reserva');
        }

        // Eliminar pagos asociados (si existen)
        \App\Models\Pago::where('reserva_id', $reserva->id)->delete();

        // Eliminar manualmente los detalles (por si CASCADE no funciona)
        \App\Models\DetalleReservaTour::where('reserva_id', $reserva->id)->delete();

        // Eliminar la reserva
        $reserva->delete();

        return response()->json([
            'message' => 'Reserva eliminada exitosamente',
        ]);
    }

    /**
     * Obtener resumen de reservas agrupadas
     */
    public function resumen(Request $request): JsonResponse
    {
        try {
            // Resumen de tours usando la estructura correcta de BD
            // Solo incluir tours con estado FINALIZADO de los últimos 6 meses (dinámico)
            $fechaLimite = now()->subMonths(6);

            $toursResumen = DB::table('reservas')
                ->join('detalles_reservas_tours', 'reservas.id', '=', 'detalles_reservas_tours.reserva_id')
                ->join('tours', 'detalles_reservas_tours.tour_id', '=', 'tours.id')
                ->where('tours.estado', 'FINALIZADO')
                ->where('tours.fecha_regreso', '>=', $fechaLimite) // Solo tours finalizados en los últimos 6 meses
                ->select(
                    'tours.nombre',
                    'tours.fecha_regreso',
                    DB::raw("'tours' as tipo"),
                    DB::raw('COUNT(CASE WHEN reservas.estado = "PENDIENTE" THEN 1 END) as total_pendientes'),
                    DB::raw('COUNT(CASE WHEN reservas.estado = "CONFIRMADA" THEN 1 END) as total_confirmadas'),
                    DB::raw('COUNT(CASE WHEN reservas.estado = "CANCELADA" THEN 1 END) as total_canceladas'),
                    DB::raw('COUNT(CASE WHEN reservas.estado = "FINALIZADA" THEN 1 END) as total_finalizadas'),
                    DB::raw('COUNT(*) as total_reservas')
                )
                ->groupBy('tours.id', 'tours.nombre', 'tours.fecha_regreso')
                ->orderByRaw('COUNT(*) DESC')
                ->limit(5)
                ->get();

            // Por ahora solo manejamos tours, pero se puede extender para hoteles
            $resumen = $toursResumen;

            return response()->json([
                'success' => true,
                'data' => $resumen
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al obtener el resumen de reservas',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Confirmar una reserva
     */
    public function confirmar(Request $request, $id): JsonResponse
    {
        try {
            $reserva = Reserva::findOrFail($id);

            // Validar que se pueda confirmar
            $estadosPermitidos = [Reserva::PENDIENTE, 'Pendiente', 'pendiente'];
            if (!in_array($reserva->estado, $estadosPermitidos)) {
                return response()->json([
                    'success' => false,
                    'message' => "Solo se pueden confirmar reservas pendientes. Estado actual: {$reserva->estado}"
                ], 400);
            }

            $reserva->update([
                'estado' => Reserva::CONFIRMADA
            ]);

            // Verificar si hay que cambiar el estado del tour a COMPLETO
            if ($reserva->detallesTours && $reserva->detallesTours->isNotEmpty()) {
                $tour = $reserva->detallesTours->first()->tour;
                if ($tour) {
                    // Recalcular cupos reservados
                    $cuposReservadosTotal = $tour->detalleReservas()
                        ->whereHas('reserva', function($query) {
                            $query->where('estado', '!=', Reserva::CANCELADA);
                        })
                        ->sum('cupos_reservados');

                    // Cambiar estado del tour automáticamente si se completó el cupo máximo
                    if ($cuposReservadosTotal >= $tour->cupo_max && $tour->estado === 'DISPONIBLE') {
                        $tour->update(['estado' => 'COMPLETO']);
                        Log::info("Tour {$tour->id} cambió automáticamente a estado COMPLETO al confirmar reserva - cupos reservados: {$cuposReservadosTotal}/{$tour->cupo_max}");
                    }
                }
            }

            // Recargar la reserva con relaciones
            $reserva = $reserva->fresh(['cliente.user', 'detallesTours.tour']);

            // Obtener las fechas del tour si existe
            $fechaSalida = null;
            $fechaRegreso = null;
            if ($reserva->detallesTours && $reserva->detallesTours->isNotEmpty()) {
                $primerDetalle = $reserva->detallesTours->first();
                if ($primerDetalle && $primerDetalle->tour) {
                    $fechaSalida = $primerDetalle->tour->fecha_salida;
                    $fechaRegreso = $primerDetalle->tour->fecha_regreso;
                }
            }

            // Obtener categoría del tour
            $categoria = 'N/A';
            if ($reserva->detallesTours && $reserva->detallesTours->isNotEmpty()) {
                $primerDetalle = $reserva->detallesTours->first();
                if ($primerDetalle && $primerDetalle->tour) {
                    $categoria = $primerDetalle->tour->categoria;
                }
            }

            // Preparar datos para el email
            $reservationData = [
                'entidad_nombre' => $this->obtenerNombreEntidad($reserva),
                'fecha_reserva' => $reserva->fecha,
                'fecha_salida' => $fechaSalida,
                'fecha_regreso' => $fechaRegreso,
                'categoria' => $categoria,
                'mayores_edad' => $reserva->mayores_edad,
                'menores_edad' => $reserva->menores_edad,
                'total' => $reserva->total
            ];

            $clientData = [
                'name' => $reserva->cliente->user->name ?? $reserva->cliente->nombres ?? 'Estimado cliente',
                'email' => $reserva->cliente->user->email ?? $reserva->cliente->correo ?? null
            ];

            // Enviar email de confirmación solo si hay email
            if ($clientData['email']) {
                try {
                    Mail::to($clientData['email'])
                        ->send(new ReservationConfirmedMail($reservationData, $clientData));
                } catch (\Exception $emailError) {
                    Log::error('Error al enviar email de confirmación', [
                        'reserva_id' => $id,
                        'email' => $clientData['email'],
                        'error' => $emailError->getMessage()
                    ]);
                    // No retornamos error aquí para no afectar la confirmación de la reserva
                }
            }

            return response()->json([
                'success' => true,
                'message' => 'Reserva confirmada exitosamente. Se ha enviado un email de confirmación al cliente.',
                'data' => $reserva
            ]);

        } catch (\Exception $e) {
            Log::error('Error al confirmar reserva', [
                'id' => $id,
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Error al confirmar la reserva',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Obtener el nombre de la entidad reservada
     */
    private function obtenerNombreEntidad(Reserva $reserva): string
    {
        // Para tours
        if ($reserva->detallesTours && $reserva->detallesTours->count() > 0) {
            return $reserva->detallesTours->first()->tour->nombre ?? 'Tour no especificado';
        }

        // Para hoteles (cuando se implemente)
        // if ($reserva->detallesHoteles && $reserva->detallesHoteles->count() > 0) {
        //     return $reserva->detallesHoteles->first()->hotel->nombre ?? 'Hotel no especificado';
        // }

        return 'Servicio no especificado';
    }

    /**
     * Obtener el tipo de reserva
     */
    private function obtenerTipoReserva(Reserva $reserva): string
    {
        if ($reserva->detallesTours && $reserva->detallesTours->count() > 0) {
            return 'tours';
        }

        // Para futuras implementaciones
        // if ($reserva->detallesHoteles && $reserva->detallesHoteles->count() > 0) {
        //     return 'hoteles';
        // }

        return 'servicio';
    }

    /**
     * Rechazar una reserva
     */
    public function rechazar(Request $request, $id): JsonResponse
    {
        try {
            $reserva = Reserva::findOrFail($id);

            // Verificar que el motivo esté presente
            if (!$request->has('motivo') || empty(trim($request->input('motivo')))) {
                return response()->json([
                    'success' => false,
                    'message' => 'El motivo del rechazo es requerido'
                ], 400);
            }

            // Ser más flexible con los estados - Incluir REPROGRAMADA para permitir cancelación
            $estadosPermitidos = [
                'PENDIENTE', 'Pendiente', 'pendiente',
                'CONFIRMADA', 'CONFIRMADO', 'Confirmada', 'Confirmado', 'confirmada', 'confirmado',
                'REPROGRAMADA', 'Reprogramada', 'reprogramada'
            ];

            if (!in_array($reserva->estado, $estadosPermitidos)) {
                return response()->json([
                    'success' => false,
                    'message' => "No se puede rechazar una reserva en estado: {$reserva->estado}. Los estados permitidos son: PENDIENTE, CONFIRMADA, REPROGRAMADA"
                ], 400);
            }

            $motivo = trim($request->input('motivo'));

            // Cargar relaciones ANTES de eliminar
            $reserva->load(['cliente.user', 'detallesTours.tour']);

            // REAJUSTAR CUPOS: Liberar cupos antes de eliminar la reserva
            if ($reserva->detallesTours && $reserva->detallesTours->isNotEmpty()) {
                foreach ($reserva->detallesTours as $detalle) {
                    if ($detalle->tour) {
                        $tour = $detalle->tour;
                        $personasCanceladas = ($reserva->mayores_edad ?? 0) + ($reserva->menores_edad ?? 0);

                        // Incrementar cupos disponibles
                        $nuevos_cupos = ($tour->cupos_disponibles ?? 0) + $personasCanceladas;

                        // Verificar si el tour debe cambiar de estado
                        $nuevo_estado = $tour->estado;
                        if ($tour->estado === 'COMPLETO' && $nuevos_cupos > 0) {
                            $nuevo_estado = 'DISPONIBLE';
                        }
                        // Si el tour estaba CANCELADA pero ahora no tiene reservas, debe volver a DISPONIBLE
                        elseif ($tour->estado === 'CANCELADA') {
                            // Verificar si quedan reservas activas en este tour después de rechazar esta
                            $reservasActivas = \App\Models\Reserva::whereHas('detallesTours', function($query) use ($tour) {
                                $query->where('tour_id', $tour->id);
                            })
                            ->whereIn('estado', [\App\Models\Reserva::PENDIENTE, \App\Models\Reserva::CONFIRMADA, \App\Models\Reserva::EN_CURSO, \App\Models\Reserva::REPROGRAMADA])
                            ->where('id', '!=', $reserva->id) // Excluir la reserva que se está rechazando
                            ->count();

                            if ($reservasActivas === 0) {
                                $nuevo_estado = 'DISPONIBLE';
                            }
                        }

                        $tour->update([
                            'cupos_disponibles' => $nuevos_cupos,
                            'estado' => $nuevo_estado
                        ]);

                        Log::info('Cupos reajustados por eliminación de reserva rechazada', [
                            'reserva_id' => $reserva->id,
                            'tour_id' => $tour->id,
                            'personas_liberadas' => $personasCanceladas,
                            'cupos_anteriores' => $tour->cupos_disponibles - $personasCanceladas,
                            'cupos_nuevos' => $nuevos_cupos,
                            'estado_anterior' => $tour->estado === $nuevo_estado ? 'sin cambio' : $tour->estado,
                            'estado_nuevo' => $nuevo_estado
                        ]);
                    }
                }
            }

            // Obtener la fecha de salida del tour si existe
            $fechaSalida = null;
            if ($reserva->detallesTours && $reserva->detallesTours->isNotEmpty()) {
                $primerDetalle = $reserva->detallesTours->first();
                if ($primerDetalle && $primerDetalle->tour) {
                    $fechaSalida = $primerDetalle->tour->fecha_salida;
                }
            }

            // Obtener categoría del tour
            $categoria = 'N/A';
            if ($reserva->detallesTours && $reserva->detallesTours->isNotEmpty()) {
                $primerDetalle = $reserva->detallesTours->first();
                if ($primerDetalle && $primerDetalle->tour) {
                    $categoria = $primerDetalle->tour->categoria;
                }
            }

            // Preparar datos para el email de rechazo
            $reservationData = [
                'entidad_nombre' => $this->obtenerNombreEntidad($reserva),
                'fecha_reserva' => $reserva->fecha,
                'fecha_salida' => $fechaSalida,
                'categoria' => $categoria,
                'mayores_edad' => $reserva->mayores_edad,
                'menores_edad' => $reserva->menores_edad,
                'total' => $reserva->total
            ];

            $clientData = [
                'name' => $reserva->cliente->user->name ?? $reserva->cliente->nombres ?? 'Estimado cliente',
                'email' => $reserva->cliente->user->email ?? $reserva->cliente->correo ?? null
            ];

            // Enviar email de rechazo solo si hay email
            if ($clientData['email']) {
                try {
                    Mail::to($clientData['email'])
                        ->send(new ReservationRejectedMail($reservationData, $clientData, $motivo));
                } catch (\Exception $emailError) {
                    Log::error('Error al enviar email de rechazo', [
                        'reserva_id' => $id,
                        'email' => $clientData['email'],
                        'error' => $emailError->getMessage()
                    ]);
                    // No retornamos error aquí para no afectar el rechazo de la reserva
                }
            }

            // ELIMINAR RESERVA: En lugar de cambiar estado, eliminar completamente
            $reservaId = $reserva->id;

            // 1. Eliminar pagos asociados (si existen)
            $pagosEliminados = Pago::where('reserva_id', $reserva->id)->count();
            Pago::where('reserva_id', $reserva->id)->delete();

            // 2. Eliminar manualmente los detalles (por si CASCADE no funciona)
            DetalleReservaTour::where('reserva_id', $reserva->id)->delete();

            // 3. Luego eliminar la reserva
            $reserva->delete();

            Log::info('Reserva eliminada por rechazo', [
                'reserva_id' => $reservaId,
                'motivo' => $motivo,
                'pagos_eliminados' => $pagosEliminados,
                'email_enviado' => !empty($clientData['email'])
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Reserva eliminada exitosamente. Se ha enviado una notificación al cliente.',
                'data' => ['id' => $reservaId, 'eliminada' => true]
            ]);

        } catch (\Exception $e) {
            Log::error('Error al rechazar reserva', [
                'id' => $id,
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Error al rechazar la reserva',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Reprogramar una reserva (DEPRECADO)
     *
     * Las reservas ahora se reprograman automáticamente cuando se reprograma el TOUR asociado.
     * Para reprogramar reservas, usar la funcionalidad de reprogramar TOURS desde la vista de Tours.
     */
    public function reprogramar(Request $request, $id): JsonResponse
    {
        // Buscar la reserva para obtener información del tour asociado
        $reserva = Reserva::with('detallesTours.tour')->findOrFail($id);

        $tourAsociado = null;
        if ($reserva->detallesTours && $reserva->detallesTours->isNotEmpty()) {
            $tourAsociado = $reserva->detallesTours->first()->tour;
        }

        return response()->json([
            'success' => false,
            'message' => 'Las reservas individuales ya no se pueden reprogramar. Para reprogramar reservas, debe reprogramar el TOUR asociado desde la vista de Tours.',
            'redirect_info' => [
                'tour_id' => $tourAsociado?->id,
                'tour_nombre' => $tourAsociado?->nombre,
                'mensaje' => 'Vaya a la vista de Tours, busque el tour "' . ($tourAsociado?->nombre ?? 'asociado') . '" y use la opción "Cambiar Estado" para reprogramarlo. Esto actualizará automáticamente todas las reservas asociadas.'
            ]
        ], 422); // 422 Unprocessable Entity

        // El resto de la lógica fue eliminada ya que ahora las reservas
        // se reprograman automáticamente desde el TOUR
    }

    /**
     * Finalizar una reserva
     */
    public function finalizar(Request $request, $id): JsonResponse
    {
        try {
            $reserva = Reserva::findOrFail($id);

            // Estados permitidos para finalizar (estados unificados)
            $estadosPermitidos = [Reserva::CONFIRMADA, Reserva::EN_CURSO, Reserva::REPROGRAMADA];

            if (!in_array($reserva->estado, $estadosPermitidos)) {
                return response()->json([
                    'success' => false,
                    'message' => "No se puede finalizar una reserva en estado: {$reserva->estado}. Solo se pueden finalizar reservas confirmadas, en curso o reprogramadas."
                ], 400);
            }

            $reserva->update([
                'estado' => Reserva::FINALIZADA
            ]);

            // Recargar la reserva con relaciones
            $reserva = $reserva->fresh(['cliente.user', 'detallesTours.tour']);

            // Obtener la fecha de regreso del tour si existe
            $fechaRegreso = null;
            if ($reserva->detallesTours && $reserva->detallesTours->isNotEmpty()) {
                $primerDetalle = $reserva->detallesTours->first();
                if ($primerDetalle && $primerDetalle->tour) {
                    $fechaRegreso = $primerDetalle->tour->fecha_regreso;
                }
            }

            // Obtener categoría del tour
            $categoria = 'N/A';
            if ($reserva->detallesTours && $reserva->detallesTours->isNotEmpty()) {
                $primerDetalle = $reserva->detallesTours->first();
                if ($primerDetalle && $primerDetalle->tour) {
                    $categoria = $primerDetalle->tour->categoria;
                }
            }

            // Preparar datos para el email de finalización
            $reservationData = [
                'entidad_nombre' => $this->obtenerNombreEntidad($reserva),
                'fecha_reserva' => $reserva->fecha,
                'fecha_regreso' => $fechaRegreso,
                'categoria' => $categoria,
                'mayores_edad' => $reserva->mayores_edad,
                'menores_edad' => $reserva->menores_edad,
                'total' => $reserva->total
            ];

            $clientData = [
                'name' => $reserva->cliente->user->name ?? $reserva->cliente->nombres ?? 'Estimado cliente',
                'email' => $reserva->cliente->user->email ?? $reserva->cliente->correo ?? null
            ];

            // Enviar email de finalización solo si hay email
            if ($clientData['email']) {
                try {
                    Mail::to($clientData['email'])
                        ->send(new ReservationCompletedMail($reservationData, $clientData));
                } catch (\Exception $emailError) {
                    Log::error('Error al enviar email de finalización', [
                        'reserva_id' => $id,
                        'email' => $clientData['email'],
                        'error' => $emailError->getMessage()
                    ]);
                    // No retornamos error aquí para no afectar la finalización de la reserva
                }
            }

            return response()->json([
                'success' => true,
                'message' => 'Reserva finalizada exitosamente. Se ha enviado un email de agradecimiento al cliente.',
                'data' => $reserva
            ]);

        } catch (\Exception $e) {
            Log::error('Error al finalizar reserva', [
                'id' => $id,
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Error al finalizar la reserva',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Finalizar automáticamente las reservas que han llegado a su fecha y hora
     */
    public function finalizarAutomaticamente(): JsonResponse
    {
        try {
            $now = now();

            // Buscar reservas confirmadas o reprogramadas cuya fecha ya ha pasado
            $reservas = Reserva::whereIn('estado', ['CONFIRMADA', 'REPROGRAMADA'])
                ->where('fecha', '<=', $now->toDateString())
                ->with(['cliente', 'cliente.user', 'detallesTours.tour'])
                ->get();

            $reservasFinalizadas = 0;
            $errores = [];

            foreach ($reservas as $reserva) {
                try {
                    // Lógica simplificada: finalizar reservas con fecha anterior o igual a hoy
                    $debeFinalizarse = $reserva->fecha <= $now->toDateString();

                    if ($debeFinalizarse) {
                        $reserva->update(['estado' => 'FINALIZADA']);

                        // Obtener la fecha de regreso del tour si existe
                        $fechaRegreso = null;
                        if ($reserva->detallesTours && $reserva->detallesTours->isNotEmpty()) {
                            $primerDetalle = $reserva->detallesTours->first();
                            if ($primerDetalle && $primerDetalle->tour) {
                                $fechaRegreso = $primerDetalle->tour->fecha_regreso;
                            }
                        }

                        // Obtener categoría del tour
                        $categoria = 'N/A';
                        if ($reserva->detallesTours && $reserva->detallesTours->isNotEmpty()) {
                            $primerDetalle = $reserva->detallesTours->first();
                            if ($primerDetalle && $primerDetalle->tour) {
                                $categoria = $primerDetalle->tour->categoria;
                            }
                        }

                        // Enviar email de finalización
                        $reservationData = [
                            'entidad_nombre' => $this->obtenerNombreEntidad($reserva),
                            'fecha_reserva' => $reserva->fecha,
                            'fecha_regreso' => $fechaRegreso,
                            'categoria' => $categoria,
                            'mayores_edad' => $reserva->mayores_edad,
                            'menores_edad' => $reserva->menores_edad,
                            'total' => $reserva->total
                        ];

                        $clientData = [
                            'name' => $reserva->cliente->user->name ?? $reserva->cliente->nombres ?? 'Estimado cliente',
                            'email' => $reserva->cliente->user->email ?? $reserva->cliente->correo ?? null
                        ];

                        if ($clientData['email']) {
                            try {
                                Mail::to($clientData['email'])
                                    ->send(new ReservationCompletedMail($reservationData, $clientData));
                            } catch (\Exception $emailError) {
                                Log::error('Error al enviar email de finalización automática', [
                                    'reserva_id' => $reserva->id,
                                    'email' => $clientData['email'],
                                    'error' => $emailError->getMessage()
                                ]);
                            }
                        }

                        $reservasFinalizadas++;

                        Log::info('Reserva finalizada automáticamente', [
                            'reserva_id' => $reserva->id,
                            'fecha_reserva' => $reserva->fecha,
                            'cliente' => $clientData['name']
                        ]);
                    }
                } catch (\Exception $e) {
                    $errores[] = [
                        'reserva_id' => $reserva->id,
                        'error' => $e->getMessage()
                    ];

                    Log::error('Error al finalizar automáticamente reserva', [
                        'reserva_id' => $reserva->id,
                        'error' => $e->getMessage()
                    ]);
                }
            }

            return response()->json([
                'success' => true,
                'message' => "Proceso automático completado. {$reservasFinalizadas} reservas finalizadas.",
                'reservas_finalizadas' => $reservasFinalizadas,
                'reservas_procesadas' => $reservas->count(),
                'data' => [
                    'reservas_finalizadas' => $reservasFinalizadas,
                    'total_procesadas' => $reservas->count(),
                    'errores' => $errores
                ]
            ]);

        } catch (\Exception $e) {
            Log::error('Error en finalización automática', [
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Error en el proceso de finalización automática',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Obtener historial de una reserva
     */
    public function historial($id): JsonResponse
    {
        try {
            $reserva = Reserva::with(['cliente', 'cliente.user', 'detallesTours.tour'])->findOrFail($id);

            // Simulamos el historial básico con la información disponible
            $historial = [
                [
                    'tipo' => 'creacion',
                    'fecha' => $reserva->created_at,
                    'descripcion' => 'Reserva creada',
                    'usuario' => 'Sistema',
                    'detalles' => null
                ]
            ];

            // Agregar eventos basados en el estado actual
            if ($reserva->estado === 'Confirmado') {
                $historial[] = [
                    'tipo' => 'confirmacion',
                    'fecha' => $reserva->updated_at,
                    'descripcion' => 'Reserva confirmada',
                    'usuario' => 'Administrador',
                    'detalles' => null
                ];
            } elseif ($reserva->estado === 'Rechazada') {
                $historial[] = [
                    'tipo' => 'rechazo',
                    'fecha' => $reserva->updated_at,
                    'descripcion' => 'Reserva rechazada',
                    'usuario' => 'Administrador',
                    'detalles' => null
                ];
            } elseif ($reserva->estado === 'Reprogramada') {
                $historial[] = [
                    'tipo' => 'reprogramacion',
                    'fecha' => $reserva->updated_at,
                    'descripcion' => 'Reserva reprogramada',
                    'usuario' => 'Administrador',
                    'detalles' => null
                ];
            }

            return response()->json([
                'success' => true,
                'data' => [
                    'reserva' => $reserva,
                    'historial' => $historial
                ]
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al obtener el historial',
                'error' => $e->getMessage()
            ], 500);
        }
    }




}
