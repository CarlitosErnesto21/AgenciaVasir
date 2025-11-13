<?php

namespace App\Http\Controllers;

use App\Models\PaqueteVisa;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Auth;
use Exception;

class PaqueteVisaController extends Controller
{
    // Listar
    public function index()
    {
        $paquetes = PaqueteVisa::with(['imagenes', 'user'])
            ->orderBy('created_at', 'desc')
            ->get();

        return response()->json(['success' => true, 'data' => $paquetes], 200);
    }

    // Mostrar
    public function show($id)
    {
        $paquete = PaqueteVisa::with(['imagenes', 'user'])->findOrFail($id);
        return response()->json(['success' => true, 'data' => $paquete], 200);
    }

    // Crear (imagen opcional, máximo 1)
    public function store(Request $request)
    {
        // Limpiar y transformar el nombre antes de la validación
        if ($request->has('nombre')) {
            $nombre = $request->input('nombre');
            $nombre = preg_replace('/[^A-Za-z0-9\s]/', '', $nombre); // Eliminar caracteres especiales
            $nombre = strtoupper($nombre); // Convertir a mayúsculas
            $nombre = preg_replace('/\s+/', ' ', $nombre); // Reemplazar múltiples espacios por uno solo
            $nombre = trim($nombre); // Quitar espacios al inicio y final
            $request->merge(['nombre' => $nombre]);
        }

        // Convertir descripción a mayúsculas
        if ($request->has('descripcion') && $request->input('descripcion')) {
            $descripcion = strtoupper(trim($request->input('descripcion')));
            $request->merge(['descripcion' => $descripcion]);
        }

        $validated = $request->validate([
            'nombre' => 'required|string|max:100|unique:paquetes_visas,nombre|regex:/^[A-Z0-9\s]+$/',
            'descripcion' => 'nullable|string|max:255',
            'precio' => 'required|numeric',
            'incluye' => 'nullable|string',
            'no_incluye' => 'nullable|string',
            'imagenes' => 'nullable',
            'imagenes.*' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048',
        ], [
            'nombre.unique' => 'Ya existe un paquete con este nombre.',
            'nombre.regex' => 'El nombre solo puede contener letras, números y espacios.',
        ]);

        try {
            DB::beginTransaction();

            $data = $validated;
            // asignar usuario autenticado como creador
            $data['user_id'] = Auth::id() ?: ($validated['user_id'] ?? 1);
            if (array_key_exists('imagenes', $data)) {
                unset($data['imagenes']);
            }

            $paquete = PaqueteVisa::create($data);

            if ($request->hasFile('imagenes')) {
                $imagenes = $request->file('imagenes');
                $imagenFile = is_array($imagenes) ? ($imagenes[0] ?? null) : $imagenes;
                if ($imagenFile) {
                    $path = $imagenFile->store('paquetesvisas', 'public');
                    $paquete->imagenes()->create(['nombre' => basename($path)]);
                }
            }

            DB::commit();
            $paquete->load(['imagenes', 'user']);
            return response()->json(['success' => true, 'data' => $paquete], 201);
        } catch (\Illuminate\Validation\ValidationException $e) {
            DB::rollBack();
            return response()->json(['message' => 'Validation failed', 'errors' => $e->errors()], 422);
        } catch (Exception $e) {
            DB::rollBack();
            Log::error('Error creando PaqueteVisa: ' . $e->getMessage());
            return response()->json(['success' => false, 'message' => 'Error al crear paquete'], 500);
        }
    }

    // Actualizar (imagen opcional, máximo 1 en total)
    public function update(Request $request, $id)
    {
        $paquete = PaqueteVisa::findOrFail($id);

        // Limpiar y transformar el nombre antes de la validación
        if ($request->has('nombre')) {
            $nombre = $request->input('nombre');
            $nombre = preg_replace('/[^A-Za-z0-9\s]/', '', $nombre); // Eliminar caracteres especiales
            $nombre = strtoupper($nombre); // Convertir a mayúsculas
            $nombre = preg_replace('/\s+/', ' ', $nombre); // Reemplazar múltiples espacios por uno solo
            $nombre = trim($nombre); // Quitar espacios al inicio y final
            $request->merge(['nombre' => $nombre]);
        }

        // Convertir descripción a mayúsculas
        if ($request->has('descripcion') && $request->input('descripcion')) {
            $descripcion = strtoupper(trim($request->input('descripcion')));
            $request->merge(['descripcion' => $descripcion]);
        }

        $validated = $request->validate([
            'nombre' => 'required|string|max:100|unique:paquetes_visas,nombre,' . $id . '|regex:/^[A-Z0-9\s]+$/',
            'descripcion' => 'nullable|string|max:255',
            'precio' => 'required|numeric',
            'incluye' => 'nullable|string',
            'no_incluye' => 'nullable|string',
            'imagenes' => 'nullable',
            'imagenes.*' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048',
            'removed_images' => 'nullable|array'
        ], [
            'nombre.unique' => 'Ya existe un paquete con este nombre.',
            'nombre.regex' => 'El nombre solo puede contener letras, números y espacios.',
        ]);

        // Validación de conteo final
        $imagenesExistentes = $paquete->imagenes()->count();
        $imagenesAEliminar = $request->has('removed_images') ? count($request->input('removed_images')) : 0;

        // Manejar tanto archivo único como array
        $imagenesNuevas = 0;
        if ($request->hasFile('imagenes')) {
            $imagenes = $request->file('imagenes');
            $imagenesNuevas = is_array($imagenes) ? count($imagenes) : 1;
        }

        // Si hay nueva imagen y ya existe una, se reemplaza (elimina la existente automáticamente)
        $totalFinal = $imagenesExistentes - $imagenesAEliminar + $imagenesNuevas;

        // Permitir reemplazo: si hay 1 existente y 1 nueva (sin eliminaciones manuales)
        if ($imagenesExistentes == 1 && $imagenesNuevas == 1 && $imagenesAEliminar == 0) {
            $totalFinal = 1; // Se reemplaza, resultado final = 1
        }

        if ($totalFinal > 1) {
            return response()->json(['message' => 'Error de validación', 'errors' => ['imagenes' => ['El total de imágenes no puede exceder 1.']]], 422);
        }

        try {
            DB::beginTransaction();

            $data = $validated;
            // asignar usuario autenticado como quien actualiza
            $data['user_id'] = Auth::id() ?: ($paquete->user_id ?? 1);
            if (array_key_exists('imagenes', $data)) unset($data['imagenes']);
            if (array_key_exists('removed_images', $data)) unset($data['removed_images']);

            $paquete->update($data);

            // Eliminar solicitadas
            if ($request->has('removed_images')) {
                foreach ($request->input('removed_images') as $removed) {
                    $imagen = $paquete->imagenes()->where('nombre', $removed)->first();
                    if ($imagen) {
                        $filePath = 'paquetesvisas/' . $imagen->nombre;
                        
                        // Verificar si el archivo existe antes de eliminarlo
                        if (Storage::disk('public')->exists($filePath)) {
                            Storage::disk('public')->delete($filePath);
                        }
                        
                        $imagen->delete();
                    }
                }
            }

            // Agregar nueva (si viene) — reemplaza cualquier existente para garantizar 1
            if ($request->hasFile('imagenes')) {
                $imagenes = $request->file('imagenes');
                $imagenFile = is_array($imagenes) ? ($imagenes[0] ?? null) : $imagenes;
                if ($imagenFile) {
                    foreach ($paquete->imagenes as $old) {
                        Storage::disk('public')->delete('paquetesvisas/' . $old->nombre);
                        $old->delete();
                    }

                    $path = $imagenFile->store('paquetesvisas', 'public');
                    $paquete->imagenes()->create(['nombre' => basename($path)]);
                }
            }

            DB::commit();
            $paquete->load(['imagenes', 'user']);
            return response()->json(['success' => true, 'data' => $paquete], 200);
        } catch (\Illuminate\Validation\ValidationException $e) {
            DB::rollBack();
            return response()->json(['message' => 'Validation failed', 'errors' => $e->errors()], 422);
        } catch (Exception $e) {
            DB::rollBack();
            Log::error('Error actualizando PaqueteVisa: ' . $e->getMessage());
            return response()->json(['success' => false, 'message' => 'Error al actualizar paquete'], 500);
        }
    }

    // Eliminar paquete y sus imágenes
    public function destroy($id)
    {
        $paquete = PaqueteVisa::findOrFail($id);
        try {
            DB::beginTransaction();
            foreach ($paquete->imagenes as $imagen) {
                Storage::disk('public')->delete('paquetesvisas/' . $imagen->nombre);
                $imagen->delete();
            }
            $paquete->delete();
            DB::commit();
            return response()->json(['success' => true, 'message' => 'Eliminado correctamente'], 200);
        } catch (Exception $e) {
            DB::rollBack();
            Log::error('Error eliminando PaqueteVisa: ' . $e->getMessage());
            return response()->json(['success' => false, 'message' => 'Error al eliminar paquete'], 500);
        }
    }
}
