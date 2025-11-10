<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class DetalleCompraController extends Controller
{
     // Listar todos los detalles
    public function index()
    {
        $detalles = DetalleCompra::with(['compra', 'producto'])->get();
        return response()->json($detalles);
    }

    // Mostrar un detalle específico
    public function show($id)
    {
        $detalle = DetalleCompra::with(['compra', 'producto'])->find($id);

        if (!$detalle) {
            return response()->json(['message' => 'Detalle no encontrado'], 404);
        }

        return response()->json($detalle);
    }

    // Crear un nuevo detalle
    public function store(Request $request)
    {
        $request->validate([
            'compra_id' => 'required|exists:compras,id',
            'producto_id' => 'required|exists:products,id',
            'cantidad' => 'required|integer|min:1',
            'precio_unitario' => 'required|numeric|min:0',
            'subtotal' => 'required|numeric|min:0',
        ]);

        $detalle = DetalleCompra::create($request->all());
        return response()->json($detalle, 201);
    }

    // Actualizar un detalle existente
    public function update(Request $request, $id)
    {
        $detalle = DetalleCompra::find($id);

        if (!$detalle) {
            return response()->json(['message' => 'Detalle no encontrado'], 404);
        }

        $request->validate([
            'cantidad' => 'integer|min:1',
            'precio_unitario' => 'numeric|min:0',
            'subtotal' => 'numeric|min:0',
        ]);

        $detalle->update($request->all());
        return response()->json($detalle);
    }

    // Eliminar un detalle
    public function destroy($id)
    {
        $detalle = DetalleCompra::find($id);

        if (!$detalle) {
            return response()->json(['message' => 'Detalle no encontrado'], 404);
        }

        $detalle->delete();
        return response()->json(['message' => 'Detalle eliminado']);
    }
}
