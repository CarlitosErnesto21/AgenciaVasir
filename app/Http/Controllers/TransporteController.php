<?php

namespace App\Http\Controllers;

use App\Models\Transporte;
use Illuminate\Http\Request;

class TransporteController extends Controller
{
    public function index()
    {
        $transportes = Transporte::all();
        return response()->json($transportes);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'numero_placa' => [
                'required',
                'string',
                'max:10',
                'unique:transportes,numero_placa',
                'regex:/^(P|M|C|A|R|D|V|G|AB|MB|CD|CC|CR|MI|FA|PE|OF|OI)[ -]?[0-9A-F]{6}$/i'
            ],
            'nombre' => 'required|string|max:50',
            'capacidad' => 'required|integer|min:1',
            'marca' => 'required|string|max:30',
            'estado' => 'required|in:DISPONIBLE,NO_DISPONIBLE',
        ], [
            // Solo mensajes que no se validan del lado cliente
            'numero_placa.unique' => 'Esta placa ya está registrada en el sistema.',
            'numero_placa.regex' => 'El formato de la placa no es válido. Debe iniciar con un prefijo válido seguido de 6 caracteres alfanuméricos.',
        ]);

        $transporte = Transporte::create($validated);

        return response()->json([
            'message' => 'Transporte creado exitosamente',
            'transporte' => $transporte,
        ]);
    }

    public function show(Transporte $transporte)
    {
        return response()->json($transporte);
    }

    public function update(Request $request, Transporte $transporte)
    {
        $validated = $request->validate([
            'numero_placa' => [
                'required',
                'string',
                'max:10',
                'unique:transportes,numero_placa,' . $transporte->id,
                'regex:/^(P|M|C|A|R|D|V|G|AB|MB|CD|CC|CR|MI|FA|PE|OF|OI)[ -]?[0-9A-F]{6}$/i'
            ],
            'nombre' => 'required|string|max:50',
            'capacidad' => 'required|integer|min:1',
            'marca' => 'required|string|max:30',
            'estado' => 'required|in:DISPONIBLE,NO_DISPONIBLE',
        ], [
            // Solo mensajes que no se validan del lado cliente
            'numero_placa.unique' => 'Esta placa ya está registrada en el sistema.',
            'numero_placa.regex' => 'El formato de la placa no es válido. Debe iniciar con un prefijo válido seguido de 6 caracteres alfanuméricos.',
        ]);

        $transporte->update($validated);

        return response()->json([
            'message' => 'Transporte actualizado exitosamente',
            'transporte' => $transporte,
        ]);
    }

    public function destroy(Transporte $transporte)
    {
        $transporte->delete();

        return response()->json([
            'message' => 'Transporte eliminado exitosamente',
        ]);
    }
}