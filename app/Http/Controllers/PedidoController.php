<?php

namespace App\Http\Controllers;
use App\Models\Pedido;

use Illuminate\Http\Request;

class PedidoController extends Controller
{
    // Listar todos los pedidos
    public function index()
    {
        $pedidos = Pedido::with('compra', 'user')->orderByDesc('fecha_pedido')->get();
        return response()->json($pedidos);
    }

    // Mostrar un pedido específico
    public function show($id)
    {
        $pedido = Pedido::with('compra.detalles.producto', 'user')->findOrFail($id);
        return response()->json($pedido);
    }

    // Actualizar estado del pedido
    public function updateEstado(Request $request, $id)
    {
        $pedido = Pedido::findOrFail($id);

        $validated = $request->validate([
            'estado' => 'required|in:"en proceso de empaquetado", "en camino", "entregado"'
        ]);

        $pedido->estado = $validated['estado'];
        $pedido->fecha_actualizacion_estado = now();
        $pedido->save();

        return response()->json(['message' => 'Estado del pedido actualizado', 'pedido' => $pedido]);
    }

    // Eliminar pedido (opcional)
    public function destroy($id)
    {
        $pedido = Pedido::findOrFail($id);
        $pedido->delete();
        return response()->json(['message' => 'Pedido eliminado']);
    }

    // Listar pedidos de un usuario
    public function getByUser($userId)
{
    $pedidos = Pedido::with('producto')
        ->where('user_id', $userId)
        ->orderByDesc('fecha_pedido')
        ->get();

    return response()->json($pedidos);
}

}
