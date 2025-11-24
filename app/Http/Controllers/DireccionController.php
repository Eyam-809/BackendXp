<?php

namespace App\Http\Controllers;

use App\Models\Direccion;
use Illuminate\Http\Request;

class DireccionController extends Controller
{
    public function index()
    {
        return auth()->user()->direcciones;
    }

    public function store(Request $request)
    {
        $request->validate([
            'tipo' => 'required|string',
            'nombre_direccion' => 'required|string',
            'calle' => 'required|string',
            'numero' => 'required|string',
            'ciudad' => 'required|string',
            'estado' => 'required|string',
            'codigo_postal' => 'required|string',
            'pais' => 'required|string',
            'telefono' => 'required|string',
        ]);

        $direccion = Direccion::create([
            ...$request->all(),
            'user_id' => auth()->id()
        ]);

        return response()->json($direccion, 201);
    }

    public function update(Request $request, $id)
    {
        $direccion = Direccion::where('user_id', auth()->id())->findOrFail($id);

        $direccion->update($request->all());

        return response()->json($direccion);
    }

    public function destroy($id)
    {
        $direccion = Direccion::where('user_id', auth()->id())->findOrFail($id);

        $direccion->delete();

        return response()->json(['message' => 'Dirección eliminada']);
    }

    public function getbyUser($id)
    {
        $direccion = Direccion::where('user_id', $id)->get();

        if (!$direccion) {
            return response()->json(['message' => 'Dirección no encontrada'], 404);
        }

        return response()->json($direccion);
    }

}