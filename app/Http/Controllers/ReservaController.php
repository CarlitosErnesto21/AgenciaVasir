<?php

namespace App\Http\Controllers;

use App\Models\Reserva;
use App\Models\Cliente;
use App\Models\DetalleReservaTour;
use App\Models\DetalleReservaHotel;
use App\Models\Tour;
use App\Models\Hotel;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use App\Mail\ReservationConfirmedMail;
use App\Mail\ReservationRejectedMail;
use App\Mail\ReservationRescheduledMail;
use App\Mail\ReservationCompletedMail;
use Carbon\Carbon;

class ReservaController extends Controller
{
    /**
     * Display a listing of the resource with advanced filters for API.
     */
    public function index(Request $request)
    {
        try {
            $query = Reserva::with(['cliente', 'cliente.user', 'detallesTours.tour']);

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

            if ($request->filled('busqueda')) {
                $busqueda = $request->busqueda;
                $query->whereHas('cliente.user', function ($q) use ($busqueda) {
                    $q->where('name', 'like', "%{$busqueda}%")
                      ->orWhere('email', 'like', "%{$busqueda}%");
                })->orWhereHas('detallesTours.tour', function ($q) use ($busqueda) {
                    $q->where('nombre', 'like', "%{$busqueda}%");
                });
            }

            // Si no hay filtros, devolver formato simple (compatibilidad hacia atrás)
            if (!$request->hasAny(['tipo', 'estado', 'fecha_inicio', 'fecha_fin', 'busqueda', 'per_page'])) {
                $reservas = Reserva::with(['cliente', 'cliente.user', 'empleado'])->get();
                return response()->json($reservas);
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
                    'cliente' => [
                        'nombres' => $reserva->cliente && $reserva->cliente->user ?
                                   $reserva->cliente->user->name : 'Cliente no asignado',
                        'correo' => $reserva->cliente && $reserva->cliente->user ?
                                  $reserva->cliente->user->email : 'Sin correo',
                        'telefono' => $reserva->cliente ? $reserva->cliente->telefono : null,
                        'numero_identificacion' => $reserva->cliente ? $reserva->cliente->numero_identificacion : null,
                        'user' => $reserva->cliente && $reserva->cliente->user ? [
                            'name' => $reserva->cliente->user->name,
                            'email' => $reserva->cliente->user->email
                        ] : null
                    ],
                    'entidad_nombre' => $tourNombre,
                    'tipo' => 'Tour',
                    'total' => $reserva->total,
                    'mayores_edad' => $reserva->mayores_edad,
                    'menores_edad' => $reserva->menores_edad
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
                'cliente_data.tipo_documento_id' => 'required|integer|exists:tipos_documentos,id'
            ]);

            DB::beginTransaction();

            // 1. Verificar si ya existe un cliente para este usuario
            $cliente = Cliente::where('user_id', $user->id)->first();

            if (!$cliente) {
                // 2. Crear el cliente si no existe
                $cliente = Cliente::create([
                    'numero_identificacion' => $validated['cliente_data']['numero_identificacion'],
                    'fecha_nacimiento' => $validated['cliente_data']['fecha_nacimiento'],
                    'genero' => strtoupper($validated['cliente_data']['genero']),
                    'direccion' => $validated['cliente_data']['direccion'],
                    'telefono' => $validated['cliente_data']['telefono'],
                    'user_id' => $user->id,
                    'tipo_documento_id' => $validated['cliente_data']['tipo_documento_id']
                ]);
            } else {
                // Actualizar datos del cliente existente
                $cliente->update([
                    'numero_identificacion' => $validated['cliente_data']['numero_identificacion'],
                    'fecha_nacimiento' => $validated['cliente_data']['fecha_nacimiento'],
                    'genero' => strtoupper($validated['cliente_data']['genero']),
                    'direccion' => $validated['cliente_data']['direccion'],
                    'telefono' => $validated['cliente_data']['telefono'],
                    'tipo_documento_id' => $validated['cliente_data']['tipo_documento_id']
                ]);
            }

            // 3. Obtener información del hotel
            $hotel = \App\Models\Hotel::findOrFail($validated['hotel_id']);

            // 4. Calcular precio estimado (por ahora será 0, se puede ajustar según lógica de negocio)
            $precio_estimado = 0; // Se puede calcular según días * habitaciones * tarifa del hotel

            // 5. Crear la reserva (sin empleado asignado inicialmente)
            $reserva = Reserva::create([
                'fecha' => $validated['fecha_entrada'],
                'estado' => 'PENDIENTE',
                'mayores_edad' => $validated['cantidad_personas'],
                'menores_edad' => 0, // Para hoteles no manejamos menores por separado
                'total' => $precio_estimado,
                'cliente_id' => $cliente->id,
                'empleado_id' => null // El empleado será asignado posteriormente
            ]);

            // 6. Crear el detalle de reserva de hotel
            $detalleReserva = \App\Models\DetalleReservaHotel::create([
                'fecha_entrada' => $validated['fecha_entrada'],
                'fecha_salida' => $validated['fecha_salida'],
                'cantidad_persona' => $validated['cantidad_personas'],
                'cantidad_habitacion' => $validated['cantidad_habitaciones'],
                'subtotal' => $precio_estimado,
                'reserva_id' => $reserva->id,
                'hotel_id' => $hotel->id
            ]);

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Reserva de hotel creada exitosamente',
                'data' => [
                    'reserva' => $reserva,
                    'cliente' => $cliente,
                    'detalle' => $detalleReserva,
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
                'cliente_data.tipo_documento_id' => 'required|exists:tipos_documentos,id'
            ]);

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
                // 2. Crear el cliente si no existe
                $cliente = Cliente::create([
                    'numero_identificacion' => $validated['cliente_data']['numero_identificacion'],
                    'fecha_nacimiento' => $fechaNacimiento,
                    'genero' => strtoupper($validated['cliente_data']['genero']),
                    'direccion' => $validated['cliente_data']['direccion'],
                    'telefono' => $validated['cliente_data']['telefono'],
                    'user_id' => $user->id,
                    'tipo_documento_id' => $validated['cliente_data']['tipo_documento_id']
                ]);
            } else {
                // Actualizar datos del cliente existente
                $cliente->update([
                    'numero_identificacion' => $validated['cliente_data']['numero_identificacion'],
                    'fecha_nacimiento' => $fechaNacimiento,
                    'genero' => strtoupper($validated['cliente_data']['genero']),
                    'direccion' => $validated['cliente_data']['direccion'],
                    'telefono' => $validated['cliente_data']['telefono'],
                    'tipo_documento_id' => $validated['cliente_data']['tipo_documento_id']
                ]);
            }

            // 3. Obtener información del tour
            $tour = Tour::findOrFail($validated['tour_id']);

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
                'fecha' => Carbon::now()->toDateString(),
                'estado' => 'PENDIENTE',
                'mayores_edad' => $validated['cupos_adultos'],
                'menores_edad' => $validated['cupos_menores'] ?? null,
                'total' => $precio_total,
                'cliente_id' => $cliente->id,
                'empleado_id' => null // El empleado será asignado posteriormente
            ]);

            // 7. Crear el detalle de reserva de tour
            $detalleReserva = DetalleReservaTour::create([
                'fecha' => Carbon::now()->toDateString(),
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
                    $query->where('estado', '!=', 'cancelada');
                })
                ->sum('cupos_reservados');

            $cuposDisponiblesActualizados = max(0, $tour->cupo_max - $cuposReservadosTotal);

            // Debug log
            Log::info("Después de reserva - Tour {$tour->id}: cupo_max={$tour->cupo_max}, reservados={$cuposReservadosTotal}, disponibles={$cuposDisponiblesActualizados}");

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
        $reserva->load(['cliente', 'cliente.user', 'empleado']);
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
            'estado' => 'required|in:PENDIENTE,CONFIRMADA,RECHAZADA,REPROGRAMADA,FINALIZADA',
            'mayores_edad' => 'required|integer|min:1',
            'menores_edad' => 'nullable|integer|min:0',
            'cliente_id' => 'required|exists:clientes,id',
            'empleado_id' => 'required|exists:empleados,id',
            'total' => 'required|numeric|min:0'
        ]);

        // Actualizar la reserva
        $reserva->update($validated);

        return response()->json([
            'message' => 'Reserva actualizada exitosamente',
            'reserva' => $reserva,
        ]);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Reserva $reserva)
    {
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
            $toursResumen = DB::table('reservas')
                ->join('detalles_reservas_tours', 'reservas.id', '=', 'detalles_reservas_tours.reserva_id')
                ->join('tours', 'detalles_reservas_tours.tour_id', '=', 'tours.id')
                ->select(
                    'tours.nombre',
                    DB::raw("'tours' as tipo"),
                    DB::raw('COUNT(CASE WHEN reservas.estado = "Pendiente" THEN 1 END) as total_pendientes'),
                    DB::raw('COUNT(CASE WHEN reservas.estado = "Confirmado" THEN 1 END) as total_confirmadas'),
                    DB::raw('COUNT(CASE WHEN reservas.estado = "Rechazada" THEN 1 END) as total_rechazadas'),
                    DB::raw('COUNT(*) as total_reservas')
                )
                ->groupBy('tours.id', 'tours.nombre')
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
            $estadosPermitidos = ['PENDIENTE', 'Pendiente', 'pendiente'];
            if (!in_array($reserva->estado, $estadosPermitidos)) {
                return response()->json([
                    'success' => false,
                    'message' => "Solo se pueden confirmar reservas pendientes. Estado actual: {$reserva->estado}"
                ], 400);
            }

            $reserva->update([
                'estado' => 'CONFIRMADA'
            ]);

            // Recargar la reserva con relaciones
            $reserva = $reserva->fresh(['cliente.user', 'detallesTours.tour']);

            // Preparar datos para el email
            $reservationData = [
                'entidad_nombre' => $this->obtenerNombreEntidad($reserva),
                'fecha_reserva' => $reserva->fecha,
                'tipo' => $this->obtenerTipoReserva($reserva),
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

            // Ser más flexible con los estados
            $estadosPermitidos = [
                'PENDIENTE', 'Pendiente', 'pendiente',
                'CONFIRMADA', 'CONFIRMADO', 'Confirmada', 'Confirmado', 'confirmada', 'confirmado'
            ];

            if (!in_array($reserva->estado, $estadosPermitidos)) {
                return response()->json([
                    'success' => false,
                    'message' => "No se puede rechazar una reserva en estado: {$reserva->estado}"
                ], 400);
            }

            $motivo = trim($request->input('motivo'));

            $reserva->update([
                'estado' => 'RECHAZADA'
            ]);

            // Recargar la reserva con relaciones
            $reserva = $reserva->fresh(['cliente.user', 'detallesTours.tour']);

            // Preparar datos para el email de rechazo
            $reservationData = [
                'entidad_nombre' => $this->obtenerNombreEntidad($reserva),
                'fecha_reserva' => $reserva->fecha,
                'tipo' => $this->obtenerTipoReserva($reserva),
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

            return response()->json([
                'success' => true,
                'message' => 'Reserva rechazada exitosamente. Se ha enviado una notificación al cliente.',
                'data' => $reserva
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
     * Reprogramar una reserva
     */
    public function reprogramar(Request $request, $id): JsonResponse
    {
        try {
            $reserva = Reserva::findOrFail($id);

            // Ser más flexible con los estados
            $estadosPermitidos = [
                'PENDIENTE', 'Pendiente', 'pendiente',
                'CONFIRMADA', 'CONFIRMADO', 'Confirmada', 'Confirmado', 'confirmada', 'confirmado'
            ];

            if (!in_array($reserva->estado, $estadosPermitidos)) {
                return response()->json([
                    'success' => false,
                    'message' => "No se puede reprogramar una reserva en estado: {$reserva->estado}"
                ], 400);
            }

            $request->validate([
                'fecha_nueva' => 'required|date|after:today',
                'motivo' => 'required|string|max:255',
                'observaciones' => 'nullable|string|max:1000'
            ]);

            $fechaAnterior = $reserva->fecha;
            $fechaNueva = $request->fecha_nueva;
            $motivo = $request->motivo;
            $observaciones = $request->observaciones;

            $reserva->update([
                'fecha' => $fechaNueva,
                'estado' => 'REPROGRAMADA'
            ]);

            // Recargar la reserva con relaciones
            $reserva = $reserva->fresh(['cliente.user', 'detallesTours.tour']);

            // Preparar datos para el email de reprogramación
            $reservationData = [
                'entidad_nombre' => $this->obtenerNombreEntidad($reserva),
                'fecha_reserva' => $fechaAnterior, // Enviamos la fecha anterior para comparar
                'tipo' => $this->obtenerTipoReserva($reserva),
                'mayores_edad' => $reserva->mayores_edad,
                'menores_edad' => $reserva->menores_edad,
                'total' => $reserva->total
            ];

            $clientData = [
                'name' => $reserva->cliente->user->name ?? $reserva->cliente->nombres ?? 'Estimado cliente',
                'email' => $reserva->cliente->user->email ?? $reserva->cliente->correo ?? null
            ];

            // Enviar email de reprogramación solo si hay email
            if ($clientData['email']) {
                try {
                    Mail::to($clientData['email'])
                        ->send(new ReservationRescheduledMail(
                            $reservationData,
                            $clientData,
                            $motivo,
                            $fechaNueva,
                            $observaciones
                        ));

                    Log::info('Email de reprogramación enviado exitosamente', [
                        'reserva_id' => $id,
                        'email' => $clientData['email'],
                        'fecha_anterior' => $fechaAnterior,
                        'fecha_nueva' => $fechaNueva
                    ]);
                } catch (\Exception $emailError) {
                    Log::error('Error al enviar email de reprogramación', [
                        'reserva_id' => $id,
                        'email' => $clientData['email'],
                        'error' => $emailError->getMessage()
                    ]);
                    // No retornamos error aquí para no afectar la reprogramación de la reserva
                }
            }

            return response()->json([
                'success' => true,
                'message' => 'Reserva reprogramada exitosamente. Se ha notificado al cliente sobre los cambios.',
                'data' => $reserva
            ]);

        } catch (\Exception $e) {
            Log::error('Error al reprogramar reserva', [
                'id' => $id,
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Error al reprogramar la reserva',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Finalizar una reserva
     */
    public function finalizar(Request $request, $id): JsonResponse
    {
        try {
            $reserva = Reserva::findOrFail($id);

            // Ser más flexible con los estados
            $estadosPermitidos = [
                'CONFIRMADA', 'CONFIRMADO', 'Confirmada', 'Confirmado', 'confirmada', 'confirmado',
                'REPROGRAMADA', 'Reprogramada', 'reprogramada'
            ];

            if (!in_array($reserva->estado, $estadosPermitidos)) {
                return response()->json([
                    'success' => false,
                    'message' => "No se puede finalizar una reserva en estado: {$reserva->estado}. Solo se pueden finalizar reservas confirmadas o reprogramadas."
                ], 400);
            }

            $reserva->update([
                'estado' => 'FINALIZADA'
            ]);

            // Recargar la reserva con relaciones
            $reserva = $reserva->fresh(['cliente.user', 'detallesTours.tour']);

            // Preparar datos para el email de finalización
            $reservationData = [
                'entidad_nombre' => $this->obtenerNombreEntidad($reserva),
                'fecha_reserva' => $reserva->fecha,
                'tipo' => $this->obtenerTipoReserva($reserva),
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

                        // Enviar email de finalización
                        $reservationData = [
                            'entidad_nombre' => $this->obtenerNombreEntidad($reserva),
                            'fecha_reserva' => $reserva->fecha,
                            'tipo' => $this->obtenerTipoReserva($reserva),
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

    /**
     * Obtener listado específico de reservas de hoteles para la vista administrativa
     */
    public function indexHoteles(Request $request): JsonResponse
    {
        try {
            $query = Reserva::with(['cliente', 'cliente.user', 'detallesHoteles.hotel'])
                ->whereHas('detallesHoteles'); // Solo reservas que tienen detalles de hotel

            // Aplicar filtros
            if ($request->filled('estado')) {
                $query->where('estado', $request->estado);
            }

            if ($request->filled('fecha_inicio')) {
                $query->whereDate('fecha', '>=', $request->fecha_inicio);
            }

            if ($request->filled('fecha_fin')) {
                $query->whereDate('fecha', '<=', $request->fecha_fin);
            }

            if ($request->filled('busqueda')) {
                $busqueda = $request->busqueda;
                $query->whereHas('cliente.user', function ($q) use ($busqueda) {
                    $q->where('name', 'like', "%{$busqueda}%")
                      ->orWhere('email', 'like', "%{$busqueda}%");
                })->orWhereHas('detallesHoteles.hotel', function ($q) use ($busqueda) {
                    $q->where('nombre', 'like', "%{$busqueda}%");
                });
            }

            $reservas = $query->orderBy('fecha', 'desc')->get();

            // Transformar los datos para la respuesta
            $transformedData = $reservas->map(function ($reserva) {
                $detalleHotel = $reserva->detallesHoteles->first();
                $hotel = $detalleHotel ? $detalleHotel->hotel : null;

                return [
                    'id' => $reserva->id,
                    'fecha_reserva' => $reserva->fecha,
                    'fecha_entrada' => $detalleHotel ? $detalleHotel->fecha_entrada : null,
                    'fecha_salida' => $detalleHotel ? $detalleHotel->fecha_salida : null,
                    'estado' => $reserva->estado,
                    'cliente' => [
                        'nombres' => $reserva->cliente && $reserva->cliente->user ?
                                   $reserva->cliente->user->name : 'Cliente no asignado',
                        'correo' => $reserva->cliente && $reserva->cliente->user ?
                                  $reserva->cliente->user->email : 'Sin correo',
                        'telefono' => $reserva->cliente ? $reserva->cliente->telefono : null,
                        'numero_identificacion' => $reserva->cliente ? $reserva->cliente->numero_identificacion : null,
                    ],
                    'hotel' => [
                        'nombre' => $hotel ? $hotel->nombre : 'Hotel no especificado',
                        'direccion' => $hotel ? $hotel->direccion : null,
                        'telefono' => $hotel ? $hotel->telefono : null,
                    ],
                    'detalles' => [
                        'cantidad_personas' => $detalleHotel ? $detalleHotel->cantidad_persona : $reserva->mayores_edad,
                        'cantidad_habitaciones' => $detalleHotel ? $detalleHotel->cantidad_habitacion : null,
                    ],
                    'total' => $reserva->total,
                    'mayores_edad' => $reserva->mayores_edad,
                    'menores_edad' => $reserva->menores_edad
                ];
            });

            return response()->json([
                'success' => true,
                'data' => $transformedData
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al obtener las reservas de hoteles',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Mostrar detalles específicos de una reserva de hotel
     */
    public function showHotel($id): JsonResponse
    {
        try {
            $reserva = Reserva::with(['cliente', 'cliente.user', 'detallesHoteles.hotel', 'empleado'])
                ->whereHas('detallesHoteles') // Solo si tiene detalles de hotel
                ->findOrFail($id);

            $detalleHotel = $reserva->detallesHoteles->first();
            $hotel = $detalleHotel ? $detalleHotel->hotel : null;

            $response = [
                'id' => $reserva->id,
                'fecha_reserva' => $reserva->fecha,
                'fecha_entrada' => $detalleHotel ? $detalleHotel->fecha_entrada : null,
                'fecha_salida' => $detalleHotel ? $detalleHotel->fecha_salida : null,
                'estado' => $reserva->estado,
                'cliente' => [
                    'nombres' => $reserva->cliente && $reserva->cliente->user ?
                               $reserva->cliente->user->name : 'Cliente no asignado',
                    'correo' => $reserva->cliente && $reserva->cliente->user ?
                              $reserva->cliente->user->email : 'Sin correo',
                    'telefono' => $reserva->cliente ? $reserva->cliente->telefono : null,
                    'numero_identificacion' => $reserva->cliente ? $reserva->cliente->numero_identificacion : null,
                    'direccion' => $reserva->cliente ? $reserva->cliente->direccion : null,
                    'fecha_nacimiento' => $reserva->cliente ? $reserva->cliente->fecha_nacimiento : null,
                    'genero' => $reserva->cliente ? $reserva->cliente->genero : null,
                ],
                'hotel' => [
                    'nombre' => $hotel ? $hotel->nombre : 'Hotel no especificado',
                    'direccion' => $hotel ? $hotel->direccion : null,
                    'telefono' => $hotel ? $hotel->telefono : null,
                    'estrellas' => $hotel ? $hotel->estrellas : null,
                    'descripcion' => $hotel ? $hotel->descripcion : null,
                ],
                'detalles' => [
                    'cantidad_personas' => $detalleHotel ? $detalleHotel->cantidad_persona : $reserva->mayores_edad,
                    'cantidad_habitaciones' => $detalleHotel ? $detalleHotel->cantidad_habitacion : null,
                    'subtotal' => $detalleHotel ? $detalleHotel->subtotal : null,
                ],
                'total' => $reserva->total,
                'mayores_edad' => $reserva->mayores_edad,
                'menores_edad' => $reserva->menores_edad,
                'empleado' => $reserva->empleado ? [
                    'nombres' => $reserva->empleado->nombres,
                    'apellidos' => $reserva->empleado->apellidos,
                ] : null,
                'created_at' => $reserva->created_at,
                'updated_at' => $reserva->updated_at,
            ];

            return response()->json([
                'success' => true,
                'data' => $response
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al obtener los detalles de la reserva de hotel',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}
