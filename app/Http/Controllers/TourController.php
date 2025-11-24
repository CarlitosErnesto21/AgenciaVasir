<?php

namespace App\Http\Controllers;

use App\Models\Tour;
use App\Models\Transporte;
use App\Models\Reserva;
use App\Models\DetalleReservaTour;
use App\Mail\ReservationRescheduledMail;
use App\Mail\ReservationCompletedMail;
use App\Mail\ReservationInProgressMail;
use App\Mail\ReservationRejectedMail;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Mail;
use Carbon\Carbon;

class TourController extends Controller
{
    /**
     * Valida y formatea el nombre del tour
     */
    private function validateAndFormatNombre($nombre)
    {
        if (empty($nombre)) {
            return $nombre;
        }

        // Convertir a mayúsculas
        $nombre = mb_strtoupper($nombre, 'UTF-8');

        // Remover caracteres especiales (mantener solo letras, números, espacios y tildes)
        // Permite: A-Z, 0-9, espacios, y vocales acentuadas (ÁÉÍÓÚ), Ñ
        $nombre = preg_replace('/[^A-ZÁÉÍÓÚÑ0-9\s]/u', '', $nombre);

        // Reemplazar múltiples espacios consecutivos con uno solo
        $nombre = preg_replace('/\s+/', ' ', $nombre);

        // Eliminar espacios al inicio y al final
        $nombre = trim($nombre);

        return $nombre;
    }

    /**
     * Valida y formatea el punto de salida
     */
    private function validateAndFormatPuntoSalida($puntoSalida)
    {
        if (empty($puntoSalida)) {
            return $puntoSalida;
        }

        // Convertir a mayúsculas
        $puntoSalida = mb_strtoupper($puntoSalida, 'UTF-8');

        // Reemplazar múltiples espacios consecutivos con uno solo
        $puntoSalida = preg_replace('/\s+/', ' ', $puntoSalida);

        // Eliminar espacios al inicio y al final
        $puntoSalida = trim($puntoSalida);

        return $puntoSalida;
    }

    /**
     * Display a listing of the resource.
     * Soporta filtrado por categoría: ?categoria=nacional|internacional
     */
    public function index(Request $request)
    {
        $query = Tour::with(['transporte', 'imagenes'])
            ->orderBy('fecha_salida', 'asc');

        // Filtrar por categoría si se especifica
        if ($request->has('categoria')) {
            $categoria = strtoupper($request->input('categoria'));

            if (in_array($categoria, ['NACIONAL', 'INTERNACIONAL'])) {
                $query->where('categoria', $categoria);
            }
        }

        $tours = $query->get();

        // Agregar cupos_disponibles a cada tour
        $tours->each(function ($tour) {
            $cuposReservados = $tour->detalleReservas()
                ->whereHas('reserva', function($query) {
                    $query->where('estado', '!=', 'cancelada');
                })
                ->sum('cupos_reservados');

            $cuposDisponibles = max(0, $tour->cupo_max - $cuposReservados);

            $tour->cupos_disponibles = $cuposDisponibles;
        });

        // Siempre devolver JSON para API
        return response()->json($tours);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        // Primero validación básica para obtener transporte_id válido
        $request->validate([
            'transporte_id' => 'required|exists:transportes,id',
        ]);

        // Obtener la capacidad del transporte
        $transporte = Transporte::find($request->transporte_id);
        $capacidadMaxima = $transporte ? $transporte->capacidad : 30;

        $validated = $request->validate([
            'nombre' => ['required', 'string', 'max:200', 'regex:/^[A-ZÁÉÍÓÚÑ0-9\s]+$/u'],
            'incluye' => 'nullable|string',
            'no_incluye' => 'nullable|string',
            'cupo_min' => 'required|integer|min:1|max:' . $capacidadMaxima,
            'cupo_max' => 'required|integer|min:1|max:' . $capacidadMaxima,
            'punto_salida' => 'required|string|max:200',
            'fecha_salida' => 'required|date|after:today',
            'fecha_regreso' => 'required|date|after:fecha_salida',
            'precio' => 'required|numeric|min:0.01|max:9999.99',
            'categoria' => 'required|in:NACIONAL,INTERNACIONAL',
            'transporte_id' => 'required|exists:transportes,id',
            'imagenes' => 'nullable|array|max:5',
            'imagenes.*' => 'image|max:2048',
        ], [
            'cupo_min.max' => 'El cupo mínimo no puede ser mayor que la capacidad del transporte (' . $capacidadMaxima . ').',
            'cupo_max.max' => 'El cupo máximo no puede ser mayor que la capacidad del transporte (' . $capacidadMaxima . ').',
            'nombre.regex' => 'El nombre del tour solo puede contener letras, números, espacios y tildes. No se permiten caracteres especiales.',
        ]);

        // Aplicar validaciones de formato a los campos específicos
        $validated['nombre'] = $this->validateAndFormatNombre($validated['nombre']);
        $validated['punto_salida'] = $this->validateAndFormatPuntoSalida($validated['punto_salida']);

        // Preparar datos para crear el tour
        $tourData = $validated;
        unset($tourData['imagenes']); // Remover imagenes del array principal

        // Crear tour
        $tour = Tour::create($tourData);

        // Guardar imágenes nuevas usando Storage Laravel (persistente)
        if ($request->hasFile('imagenes')) {
            foreach ($request->file('imagenes') as $imagen) {
                if ($imagen instanceof \Illuminate\Http\UploadedFile && $imagen->isValid()) {
                    // Usar Storage::disk('public') que es persistente en Render
                    $path = $imagen->store('tours', 'public');
                    $nombreArchivo = basename($path);

                    if (empty($path)) {
                        Log::error('ERROR: No se pudo guardar la imagen');
                        continue;
                    }

                    $tour->imagenes()->create([
                        'nombre' => $nombreArchivo,
                        'imageable_type' => Tour::class,
                        'imageable_id' => $tour->id
                    ]);
                }
            }
        }

        return response()->json([
            'message' => 'Tour creado exitosamente',
            'tour' => $tour->load(['imagenes', 'transporte']),
        ]);
    }

    /**
     * Display the specified resource.
     */
    public function show($id)
    {
        $tour = Tour::with(['transporte', 'imagenes'])
            ->findOrFail($id);

        // Agregar cupos_disponibles
        $cuposReservados = $tour->detalleReservas()
            ->whereHas('reserva', function($query) {
                $query->where('estado', '!=', Reserva::CANCELADA);
            })
            ->sum('cupos_reservados');

        $cuposDisponibles = max(0, $tour->cupo_max - $cuposReservados);

        $tour->cupos_disponibles = $cuposDisponibles;

        return response()->json($tour);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Tour $tour)
    {
        // Primero validación básica para obtener transporte_id válido
        $request->validate([
            'transporte_id' => 'required|exists:transportes,id',
        ]);

        // Obtener la capacidad del transporte
        $transporte = Transporte::find($request->transporte_id);
        $capacidadMaxima = $transporte ? $transporte->capacidad : 30;

        $validated = $request->validate([
            'nombre' => ['required', 'string', 'max:200', 'regex:/^[A-ZÁÉÍÓÚÑ0-9\s]+$/u'],
            'incluye' => 'nullable|string',
            'no_incluye' => 'nullable|string',
            'cupo_min' => 'required|integer|min:1|max:' . $capacidadMaxima,
            'cupo_max' => 'required|integer|min:1|max:' . $capacidadMaxima,
            'punto_salida' => 'required|string|max:200',
            'fecha_salida' => 'required|date',
            'fecha_regreso' => 'required|date',
            'precio' => 'required|numeric|min:0.01|max:9999.99',
            'categoria' => 'required|in:NACIONAL,INTERNACIONAL',
            'transporte_id' => 'required|exists:transportes,id',
            'imagenes' => 'nullable|array',
            'imagenes.*' => 'image|max:2048',
            'removed_images' => 'nullable|array',
        ], [
            'cupo_min.max' => 'El cupo mínimo no puede ser mayor que la capacidad del transporte (' . $capacidadMaxima . ').',
            'cupo_max.max' => 'El cupo máximo no puede ser mayor que la capacidad del transporte (' . $capacidadMaxima . ').',
            'nombre.regex' => 'El nombre del tour solo puede contener letras, números, espacios y tildes. No se permiten caracteres especiales.',
        ]);

        // Aplicar validaciones de formato a los campos específicos
        $validated['nombre'] = $this->validateAndFormatNombre($validated['nombre']);
        $validated['punto_salida'] = $this->validateAndFormatPuntoSalida($validated['punto_salida']);

        // Validar límite total de imágenes (existentes + nuevas - eliminadas)
        $imagenesExistentes = $tour->imagenes()->count();
        $imagenesAEliminar = $request->has('removed_images') ? count($request->input('removed_images')) : 0;
        $imagenesNuevas = $request->hasFile('imagenes') ? count($request->file('imagenes')) : 0;
        $totalImagenesFinales = $imagenesExistentes - $imagenesAEliminar + $imagenesNuevas;

        if ($totalImagenesFinales > 5) {
            return response()->json([
                'message' => 'Error de validación',
                'errors' => [
                    'imagenes' => ['El total de imágenes no puede exceder 5. Actualmente tienes ' . $imagenesExistentes . ' imágenes, intentas agregar ' . $imagenesNuevas . ' y eliminar ' . $imagenesAEliminar . '.']
                ]
            ], 422);
        }

        // Preparar datos para actualizar el tour
        $tourData = $validated;
        unset($tourData['imagenes']); // Remover imagenes del array principal
        unset($tourData['removed_images']); // Remover removed_images del array principal

        // Actualizar tour
        $tour->update($tourData);

        // Guardar imágenes nuevas
        if ($request->hasFile('imagenes')) {
            foreach ($request->file('imagenes') as $imagen) {
                if ($imagen instanceof \Illuminate\Http\UploadedFile && $imagen->isValid()) {
                    // Usar Storage::disk('public') que es persistente en Render
                    $path = $imagen->store('tours', 'public');
                    $nombreArchivo = basename($path);

                    if (empty($path)) {
                        Log::error('ERROR: No se pudo actualizar la imagen');
                        continue;
                    }

                    $tour->imagenes()->create([
                        'nombre' => $nombreArchivo,
                        'imageable_type' => Tour::class,
                        'imageable_id' => $tour->id
                    ]);
                }
            }
        }

        // Eliminar imágenes seleccionadas
        if ($request->has('removed_images')) {
            foreach ($request->input('removed_images') as $imageName) {
                $imagen = $tour->imagenes()->where('nombre', $imageName)->first();
                if ($imagen) {
                    // Eliminar usando Storage Laravel
                    Storage::disk('public')->delete('tours/' . $imagen->nombre);
                    $imagen->forceDelete();
                }
            }
        }

        return response()->json([
            'message' => 'Tour actualizado exitosamente',
            'tour' => $tour->load(['imagenes', 'transporte']),
        ]);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy($id)
    {
        $tour = Tour::findOrFail($id);
        $tour->loadMissing(['imagenes', 'transporte']);

        // Eliminar imágenes físicas y registros usando Storage Laravel
        foreach ($tour->imagenes as $imagen) {
            Storage::disk('public')->delete('tours/' . $imagen->nombre);
            $imagen->forceDelete();
        }

        $tour->delete();

        return response()->json([
            'message' => 'Tour eliminado exitosamente',
        ]);
    }

    /**
     * Cambiar el estado de un tour
     */
    public function cambiarEstado(Request $request, $id)
    {
        $tour = Tour::findOrFail($id);

        $validated = $request->validate([
            'estado' => 'required|in:' . implode(',', Tour::ESTADOS),
            'fecha_salida' => 'required_if:estado,' . Tour::REPROGRAMADA . '|nullable|date|after:now',
            'fecha_regreso' => 'required_if:estado,REPROGRAMADO|nullable|date|after:fecha_salida',
            'motivo_reprogramacion' => 'required_if:estado,REPROGRAMADO|nullable|string|max:255',
            'motivo_cancelacion' => 'required_if:estado,CANCELADA|nullable|string|max:500',
            'cancelar_reservas' => 'nullable|boolean',
            'observaciones' => 'nullable|string|max:500'
        ]);

        try {
            // Si es reprogramación, actualizar las fechas también
            if ($validated['estado'] === Tour::REPROGRAMADA) {
                // Validar cupo mínimo antes de reprogramar el tour
                $personasConfirmadas = $tour->detalleReservas()
                    ->whereHas('reserva', function($query) {
                        $query->where('estado', 'CONFIRMADA');
                    })
                    ->get()
                    ->sum(function($detalle) {
                        return ($detalle->reserva->mayores_edad ?? 0) + ($detalle->reserva->menores_edad ?? 0);
                    });

                if ($personasConfirmadas < $tour->cupo_min) {
                    return response()->json([
                        'success' => false,
                        'message' => "No se puede reprogramar el tour. Se requiere un mínimo de {$tour->cupo_min} personas confirmadas, actualmente hay {$personasConfirmadas} confirmadas."
                    ], 422);
                }

                // Guardar fechas anteriores para el log y emails
                $fechaAnteriorSalida = $tour->fecha_salida;
                $fechaAnteriorRegreso = $tour->fecha_regreso;

                // Parsear las fechas del frontend como hora local de El Salvador
                // El frontend ahora envía fechas en formato local "Y-m-d H:i:s"
                $fechaSalidaLocal = Carbon::createFromFormat('Y-m-d H:i:s', $validated['fecha_salida'], config('app.timezone'));
                $fechaRegresoLocal = Carbon::createFromFormat('Y-m-d H:i:s', $validated['fecha_regreso'], config('app.timezone'));

                $tour->update([
                    'estado' => $validated['estado'],
                    'fecha_salida' => $fechaSalidaLocal,
                    'fecha_regreso' => $fechaRegresoLocal
                ]);

                // Recargar el tour para ver qué se guardó realmente
                $tour->refresh();

                // Actualizar todas las reservas asociadas al tour
                $reservasActualizadas = $this->actualizarReservasPorTour(
                    $tour,
                    $fechaSalidaLocal,
                    $validated['motivo_reprogramacion'],
                    $validated['observaciones'] ?? '',
                    $fechaAnteriorSalida,
                    $fechaAnteriorRegreso
                );

                $mensaje = "Tour reprogramado exitosamente. {$reservasActualizadas} reserva(s) actualizada(s) automáticamente";
            } elseif ($validated['estado'] === Tour::EN_CURSO) {
                // Validar cupo mínimo antes de iniciar el tour
                $personasConfirmadas = $tour->detalleReservas()
                    ->whereHas('reserva', function($query) {
                        $query->where('estado', 'CONFIRMADA');
                    })
                    ->get()
                    ->sum(function($detalle) {
                        return ($detalle->reserva->mayores_edad ?? 0) + ($detalle->reserva->menores_edad ?? 0);
                    });

                if ($personasConfirmadas < $tour->cupo_min) {
                    return response()->json([
                        'success' => false,
                        'message' => "No se puede iniciar el tour. Se requiere un mínimo de {$tour->cupo_min} personas confirmadas, actualmente hay {$personasConfirmadas} confirmadas."
                    ], 422);
                }

                // Cambiar estado del tour
                $tour->update([
                    'estado' => $validated['estado']
                ]);

                // Actualizar todas las reservas asociadas a EN_CURSO y enviar emails
                $reservasActualizadas = $this->iniciarReservasPorTour($tour, $validated['observaciones'] ?? '');

                $mensaje = "Tour iniciado exitosamente. {$reservasActualizadas} reserva(s) actualizada(s) y email(s) enviado(s) automáticamente";
            } elseif ($validated['estado'] === Tour::FINALIZADO) {
                // Solo validar cupo mínimo si el tour no está ya en curso
                if ($tour->estado !== Tour::EN_CURSO) {
                    $personasConfirmadas = $tour->detalleReservas()
                        ->whereHas('reserva', function($query) {
                            $query->where('estado', 'CONFIRMADA');
                        })
                        ->get()
                        ->sum(function($detalle) {
                            return ($detalle->reserva->mayores_edad ?? 0) + ($detalle->reserva->menores_edad ?? 0);
                        });

                    if ($personasConfirmadas < $tour->cupo_min) {
                        return response()->json([
                            'success' => false,
                            'message' => "No se puede finalizar el tour. Se requiere un mínimo de {$tour->cupo_min} personas confirmadas, actualmente hay {$personasConfirmadas} confirmadas."
                        ], 422);
                    }
                }

                // Cambiar estado del tour
                $tour->update([
                    'estado' => $validated['estado']
                ]);

                // Actualizar todas las reservas asociadas a FINALIZADA y enviar emails
                $reservasActualizadas = $this->finalizarReservasPorTour($tour, $validated['observaciones'] ?? '');

                $mensaje = "Tour finalizado exitosamente. {$reservasActualizadas} reserva(s) actualizada(s) y email(s) enviado(s) automáticamente";
            } elseif ($validated['estado'] === Tour::CANCELADA) {
                // Validar que no se pueda cancelar un tour en curso
                if ($tour->estado === Tour::EN_CURSO) {
                    return response()->json([
                        'success' => false,
                        'message' => 'No se puede cancelar un tour que ya está en curso.'
                    ], 422);
                }

                // Validar que el tour tenga reservas activas para poder cancelar
                $reservasActivas = Reserva::whereHas('detallesTours', function($query) use ($tour) {
                    $query->where('tour_id', $tour->id);
                })
                ->whereIn('estado', [Reserva::PENDIENTE, Reserva::CONFIRMADA, Reserva::EN_CURSO, Reserva::REPROGRAMADA])
                ->count();

                if ($reservasActivas === 0) {
                    return response()->json([
                        'success' => false,
                        'message' => 'No se puede cancelar un tour que no tiene reservas activas. Un tour sin reservas ya está disponible para nuevas reservaciones.'
                    ], 422);
                }

                // Cancelar todas las reservas asociadas y enviar emails PRIMERO
                $reservasCanceladas = $this->cancelarReservasPorTour($tour, $validated['motivo_cancelacion'] ?? 'Tour cancelado');

                // Después de eliminar las reservas, el tour debe volver a DISPONIBLE
                // ya que no tiene reservas y todos los cupos están libres
                $tour->update([
                    'estado' => Tour::DISPONIBLE,
                    'cupos_disponibles' => $tour->cupo_max
                ]);

                $mensaje = "Tour cancelado exitosamente. {$reservasCanceladas} reserva(s) eliminada(s) permanentemente y email(s) enviado(s). El tour volvió al estado DISPONIBLE";
            } else {
                // Solo cambiar el estado
                $tour->update([
                    'estado' => $validated['estado']
                ]);

                $mensaje = 'Estado del tour actualizado exitosamente';
            }

            // Recargar el tour con sus relaciones
            $tour = $tour->fresh(['transporte', 'imagenes']);

            // Agregar cupos_disponibles
            $cuposReservados = $tour->detalleReservas()
                ->whereHas('reserva', function($query) {
                    $query->where('estado', '!=', Reserva::CANCELADA);
                })
                ->sum('cupos_reservados');

            $cuposDisponibles = max(0, $tour->cupo_max - $cuposReservados);
            $tour->cupos_disponibles = $cuposDisponibles;

            // Preparar respuesta con datos específicos según el estado
            $responseData = [
                'message' => $mensaje,
                'tour' => $tour,
            ];

            // Agregar datos específicos según el estado cambiado
            if ($validated['estado'] === Tour::REPROGRAMADA && isset($reservasActualizadas)) {
                $responseData['reservas_reprogramadas'] = $reservasActualizadas;
            } elseif ($validated['estado'] === Tour::FINALIZADO && isset($reservasActualizadas)) {
                $responseData['reservas_finalizadas'] = $reservasActualizadas;
            } elseif ($validated['estado'] === Tour::CANCELADA && isset($reservasCanceladas)) {
                $responseData['reservas_canceladas'] = $reservasCanceladas;
            }

            return response()->json($responseData);

        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Error al cambiar el estado del tour',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Finalizar un tour y todas sus reservas asociadas
     */
    public function finalizarTour(Request $request, $id)
    {
        $tour = Tour::findOrFail($id);

        $validated = $request->validate([
            'observaciones' => 'nullable|string|max:500'
        ]);

        try {
            // Cambiar el estado del tour a FINALIZADO
            $tour->update([
                'estado' => Tour::FINALIZADO
            ]);

            // Actualizar todas las reservas asociadas al tour
            $reservasActualizadas = $this->finalizarReservasPorTour($tour, $validated['observaciones'] ?? '');

            // Recargar el tour con sus relaciones
            $tour = $tour->fresh(['transporte', 'imagenes']);

            // Agregar cupos_disponibles
            $cuposReservados = $tour->detalleReservas()
                ->whereHas('reserva', function($query) {
                    $query->where('estado', '!=', Reserva::CANCELADA);
                })
                ->sum('cupos_reservados');

            $cuposDisponibles = max(0, $tour->cupo_max - $cuposReservados);
            $tour->cupos_disponibles = $cuposDisponibles;

            return response()->json([
                'message' => "Tour finalizado exitosamente. {$reservasActualizadas} reserva(s) finalizada(s) automáticamente",
                'tour' => $tour,
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Error al finalizar el tour',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Mostrar tours nacionales para la vista de clientes
     */
    public function toursNacionales(Request $request)
    {
        // Obtener tours nacionales directamente
        $tours = Tour::with(['transporte', 'imagenes'])
            ->where('fecha_salida', '>=', now())
            ->where('categoria', 'NACIONAL')
            ->orderBy('fecha_salida', 'asc')
            ->get();

        // Siempre devolver vista Inertia para rutas web
        return inertia('VistasClientes/ToursNacionales', [
            'tours' => $tours
        ]);
    }

    /**
     * Mostrar tours internacionales para la vista de clientes
     */
    public function toursInternacionales(Request $request)
    {
        // Obtener tours internacionales directamente
        $tours = Tour::with(['transporte', 'imagenes'])
            ->where('fecha_salida', '>=', now())
            ->where('categoria', 'INTERNACIONAL')
            ->orderBy('fecha_salida', 'asc')
            ->get();

        // Siempre devolver vista Inertia para rutas web
        return inertia('VistasClientes/ToursInternacionales', [
            'tours' => $tours
        ]);
    }

    /**
     * Mostrar vista detallada de un tour nacional
     */
    public function mostrarTourNacional($id)
    {
        $tour = Tour::with(['transporte', 'imagenes'])
            ->where('id', $id)
            ->where('categoria', 'NACIONAL')
            ->firstOrFail();

        return inertia('VistasClientes/DetalleTour', [
            'tour' => $tour,
            'tipo' => 'nacional'
        ]);
    }

    /**
     * Mostrar vista detallada de un tour internacional
     */
    public function mostrarTourInternacional($id)
    {
        $tour = Tour::with(['transporte', 'imagenes'])
            ->where('id', $id)
            ->where('categoria', 'INTERNACIONAL')
            ->firstOrFail();

        return inertia('VistasClientes/DetalleTour', [
            'tour' => $tour,
            'tipo' => 'internacional'
        ]);
    }

    /**
     * Actualizar todas las reservas asociadas a un tour cuando se reprograma
     */
    private function actualizarReservasPorTour(
        Tour $tour,
        $nuevaFechaSalida,
        $motivo,
        $observaciones,
        $fechaAnteriorSalida,
        $fechaAnteriorRegreso
    ): int {
        // Obtener todas las reservas activas (no canceladas/rechazadas) asociadas al tour
        $reservas = Reserva::whereHas('detallesTours', function($query) use ($tour) {
            $query->where('tour_id', $tour->id);
        })
        ->whereIn('estado', [Reserva::PENDIENTE, Reserva::CONFIRMADA, Reserva::REPROGRAMADA])
        ->with(['cliente.user', 'detallesTours.tour'])
        ->get();

        $contadorActualizadas = 0;

        foreach ($reservas as $reserva) {
            try {
                // Actualizar la reserva a estado REPROGRAMADA y nueva fecha
                $reserva->update([
                    'estado' => Reserva::REPROGRAMADA,
                    'fecha' => $nuevaFechaSalida
                ]);

                $contadorActualizadas++;

                // Enviar email de notificación al cliente
                $this->enviarEmailReprogramacion(
                    $reserva,
                    $tour,
                    $nuevaFechaSalida,
                    $motivo,
                    $observaciones,
                    $fechaAnteriorSalida,
                    $fechaAnteriorRegreso
                );

            } catch (\Exception $e) {
                // Error silencioso al actualizar reserva
            }
        }

        return $contadorActualizadas;
    }

    /**
     * Enviar email de notificación de reprogramación al cliente
     */
    private function enviarEmailReprogramacion(
        Reserva $reserva,
        Tour $tour,
        $nuevaFechaSalida,
        $motivo,
        $observaciones,
        $fechaAnteriorSalida,
        $fechaAnteriorRegreso
    ): void {
        try {
            // Preparar datos para el email
            $reservationData = [
                'entidad_nombre' => $tour->nombre,
                'fecha_reserva' => $reserva->fecha,
                'fecha_salida_anterior' => $fechaAnteriorSalida,
                'fecha_salida_nueva' => $nuevaFechaSalida,
                'tipo' => 'Tour',
                'mayores_edad' => $reserva->mayores_edad,
                'menores_edad' => $reserva->menores_edad,
                'total' => $reserva->total
            ];

            $clientData = [
                'name' => $reserva->cliente->user->name ?? $reserva->cliente->nombres ?? 'Estimado cliente',
                'email' => $reserva->cliente->user->email ?? $reserva->cliente->correo ?? null
            ];

            // Enviar email si hay dirección de correo
            if ($clientData['email']) {
                Mail::to($clientData['email'])
                    ->send(new ReservationRescheduledMail(
                        $reservationData,
                        $clientData,
                        $motivo,
                        $nuevaFechaSalida,
                        $observaciones
                    ));
            }

        } catch (\Exception $e) {
            // Error silencioso al enviar email
        }
    }

    /**
     * Iniciar todas las reservas asociadas a un tour cuando se inicia
     */
    private function iniciarReservasPorTour(Tour $tour, $observaciones): int
    {
        // Obtener todas las reservas activas asociadas al tour
        $reservas = Reserva::whereHas('detallesTours', function($query) use ($tour) {
            $query->where('tour_id', $tour->id);
        })
        ->whereIn('estado', [Reserva::PENDIENTE, Reserva::CONFIRMADA, Reserva::REPROGRAMADA])
        ->with(['cliente.user', 'detallesTours.tour'])
        ->get();

        $contadorActualizadas = 0;

        foreach ($reservas as $reserva) {
            try {
                // Actualizar la reserva a estado EN_CURSO
                $reserva->update([
                    'estado' => Reserva::EN_CURSO
                ]);

                $contadorActualizadas++;

                // Enviar email de notificación al cliente
                $this->enviarEmailInicioTour($reserva, $tour, $observaciones);

            } catch (\Exception $e) {
                // Error silencioso al actualizar reserva
            }
        }

        return $contadorActualizadas;
    }

    /**
     * Finalizar todas las reservas asociadas a un tour cuando se finaliza
     */
    private function finalizarReservasPorTour(Tour $tour, $observaciones): int
    {
        // Obtener todas las reservas activas asociadas al tour
        $reservas = Reserva::whereHas('detallesTours', function($query) use ($tour) {
            $query->where('tour_id', $tour->id);
        })
        ->whereIn('estado', [Reserva::PENDIENTE, Reserva::CONFIRMADA, Reserva::EN_CURSO, Reserva::REPROGRAMADA])
        ->with(['cliente.user', 'detallesTours.tour'])
        ->get();

        $contadorActualizadas = 0;

        foreach ($reservas as $reserva) {
            try {
                // Actualizar la reserva a estado FINALIZADA
                $reserva->update([
                    'estado' => Reserva::FINALIZADA
                ]);

                $contadorActualizadas++;

                // Enviar email de notificación al cliente
                $this->enviarEmailFinalizacion($reserva, $tour, $observaciones);

            } catch (\Exception $e) {
                // Error silencioso al actualizar reserva
            }
        }

        return $contadorActualizadas;
    }

    /**
     * Cancelar todas las reservas asociadas a un tour cuando se cancela
     */
    private function cancelarReservasPorTour(Tour $tour, $motivo): int
    {
        // Obtener todas las reservas activas asociadas al tour
        $reservas = Reserva::whereHas('detallesTours', function($query) use ($tour) {
            $query->where('tour_id', $tour->id);
        })
        ->whereIn('estado', [Reserva::PENDIENTE, Reserva::CONFIRMADA, Reserva::EN_CURSO, Reserva::REPROGRAMADA])
        ->with(['cliente.user', 'detallesTours.tour'])
        ->get();

        $contadorCanceladas = 0;

        foreach ($reservas as $reserva) {
            try {
                // Enviar email de notificación al cliente ANTES de eliminar
                $this->enviarEmailCancelacion($reserva, $tour, $motivo);

                // ELIMINAR la reserva completamente en lugar de solo cambiar estado
                $reservaId = $reserva->id;

                // Eliminar manualmente los detalles primero (por si CASCADE no funciona)
                DetalleReservaTour::where('reserva_id', $reserva->id)->delete();

                // Luego eliminar la reserva
                $reserva->delete();

                $contadorCanceladas++;

                Log::info('Reserva eliminada por cancelación de tour', [
                    'reserva_id' => $reservaId,
                    'tour_id' => $tour->id,
                    'motivo' => $motivo
                ]);

            } catch (\Exception $e) {
                Log::error('Error al cancelar/eliminar reserva por tour', [
                    'reserva_id' => $reserva->id ?? 'unknown',
                    'tour_id' => $tour->id,
                    'error' => $e->getMessage()
                ]);
            }
        }

        return $contadorCanceladas;
    }

    /**
     * Enviar email de notificación de inicio de tour al cliente
     */
    private function enviarEmailInicioTour(Reserva $reserva, Tour $tour, $observaciones): void
    {
        try {
            // Preparar datos para el email
            $reservationData = [
                'entidad_nombre' => $tour->nombre,
                'fecha_reserva' => $reserva->fecha,
                'fecha_salida' => $tour->fecha_salida,
                'tipo' => 'Tour',
                'mayores_edad' => $reserva->mayores_edad,
                'menores_edad' => $reserva->menores_edad,
                'total' => $reserva->total,
                'observaciones' => $observaciones
            ];

            $clientData = [
                'name' => $reserva->cliente->user->name ?? $reserva->cliente->nombres ?? 'Estimado cliente',
                'email' => $reserva->cliente->user->email ?? $reserva->cliente->correo ?? null
            ];

            // Enviar email si hay dirección de correo
            if ($clientData['email']) {
                Mail::to($clientData['email'])
                    ->send(new ReservationInProgressMail(
                        $reservationData,
                        $clientData,
                        $observaciones
                    ));
            }

        } catch (\Exception $e) {
            // Error silencioso al enviar email
        }
    }

    /**
     * Enviar email de notificación de finalización al cliente
     */
    private function enviarEmailFinalizacion(Reserva $reserva, Tour $tour, $observaciones): void
    {
        try {
            // Preparar datos para el email
            $reservationData = [
                'entidad_nombre' => $tour->nombre,
                'fecha_reserva' => $reserva->fecha,
                'tipo' => 'Tour',
                'mayores_edad' => $reserva->mayores_edad,
                'menores_edad' => $reserva->menores_edad,
                'total' => $reserva->total,
                'observaciones' => $observaciones
            ];

            $clientData = [
                'name' => $reserva->cliente->user->name ?? $reserva->cliente->nombres ?? 'Estimado cliente',
                'email' => $reserva->cliente->user->email ?? $reserva->cliente->correo ?? null
            ];

            // Enviar email si hay dirección de correo
            if ($clientData['email']) {
                Mail::to($clientData['email'])
                    ->send(new ReservationCompletedMail(
                        $reservationData,
                        $clientData
                    ));
            }

        } catch (\Exception $e) {
            // Error silencioso al enviar email
        }
    }

    /**
     * Enviar email de notificación de cancelación al cliente
     */
    private function enviarEmailCancelacion(Reserva $reserva, Tour $tour, $motivo): void
    {
        try {
            // Preparar datos para el email
            $reservationData = [
                'entidad_nombre' => $tour->nombre,
                'fecha_reserva' => $reserva->fecha,
                'fecha_salida' => $tour->fecha_salida,
                'tipo' => 'Tour',
                'mayores_edad' => $reserva->mayores_edad,
                'menores_edad' => $reserva->menores_edad,
                'total' => $reserva->total,
                'motivo' => $motivo
            ];

            $clientData = [
                'name' => $reserva->cliente->user->name ?? $reserva->cliente->nombres ?? 'Estimado cliente',
                'email' => $reserva->cliente->user->email ?? $reserva->cliente->correo ?? null
            ];

            // Enviar email si hay dirección de correo
            if ($clientData['email']) {
                Mail::to($clientData['email'])
                    ->send(new ReservationRejectedMail(
                        $reservationData,
                        $clientData,
                        $motivo
                    ));
            }

        } catch (\Exception $e) {
            // Error silencioso al enviar email
        }
    }
}
