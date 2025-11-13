<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Compra;
use App\Models\DetalleCompra;
use App\Models\Product;
use App\Models\Pedido; // <-- agregado
use Illuminate\Support\Facades\DB;

class CompraController extends Controller
{
    /**
     * Mostrar todas las compras (puedes usarlo para un panel admin).
     */
    public function index()
    {
        $compras = Compra::with('detalles')->orderByDesc('created_at')->get();
        return response()->json($compras);
    }

    /**
     * Guardar una nueva compra con su detalle.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'user_id' => 'required|exists:users,id',
            'metodo_pago' => 'nullable|string|max:50',
            'direccion_envio' => 'nullable|string|max:255',
            'telefono_contacto' => 'nullable|string|max:20',
            'productos' => 'required|array',
            'productos.*.producto_id' => 'required|exists:products,id',
            'productos.*.cantidad' => 'required|integer|min:1',
            'productos.*.precio_unitario' => 'required|numeric|min:0',
        ]);

        try {
            DB::beginTransaction();

            // Crear compra principal
            $compra = Compra::create([
                'user_id' => $validated['user_id'],
                'metodo_pago' => $validated['metodo_pago'] ?? null,
                'direccion_envio' => $validated['direccion_envio'] ?? null,
                'telefono_contacto' => $validated['telefono_contacto'] ?? null,
                'estado' => 'pendiente',
                'total' => collect($validated['productos'])->sum(function ($p) {
                    return $p['precio_unitario'] * $p['cantidad'];
                }),
                'fecha_pago' => now(),
            ]);

            // Agregar detalle
            foreach ($validated['productos'] as $p) {
                DetalleCompra::create([
                    'compra_id' => $compra->id,
                    'producto_id' => $p['producto_id'],
                    'cantidad' => $p['cantidad'],
                    'precio_unitario' => $p['precio_unitario'],
                    'subtotal' => $p['precio_unitario'] * $p['cantidad'],
                ]);
            }

            foreach ($validated['productos'] as $p){
                $pedido = Pedido::create([
                'user_id' => $validated['user_id'],
                'compra_id' => $compra->id,
                'estado' => 'en proceso de empaquetado',
                'total' => $compra->total,
                'direccion_envio' => $compra->direccion_envio,
                'telefono_contacto' => $compra->telefono_contacto,
                'producto_id' => $p['producto_id'],
                'fecha_pedido' => now(),
            ]);
            }

            DB::commit();

            return response()->json([
                'message' => 'Compra registrada correctamente.',
                'compra' => $compra->load('detalles'),
                'pedido' => $pedido
            ], 201);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['error' => 'Error al guardar la compra: ' . $e->getMessage()], 500);
        }
    }

    /**
     * Mostrar una compra específica.
     */
    public function show($id)
    {
        $compra = Compra::with('detalles.producto')->findOrFail($id);
        return response()->json($compra);
    }

    /**
     * Actualizar estado o datos de una compra.
     */
    public function update(Request $request, $id)
    {
        $compra = Compra::findOrFail($id);

        $validated = $request->validate([
            'estado' => 'nullable|in:pendiente,pagado,enviado,completado,cancelado',
            'metodo_pago' => 'nullable|string|max:50',
            'fecha_pago' => 'nullable|date',
        ]);

        if (isset($validated['estado']) && $validated['estado'] === 'pagado' && !$compra->fecha_pago) {
            $validated['fecha_pago'] = now();
        }

        $compra->update($validated);

        return response()->json(['message' => 'Compra actualizada correctamente', 'compra' => $compra]);
    }

    /**
     * Eliminar compra (opcional).
     */
    public function destroy($id)
    {
        $compra = Compra::findOrFail($id);
        $compra->delete();

        return response()->json(['message' => 'Compra eliminada correctamente']);
    }

    /**
     * Obtener compras de un usuario con nombre de productos, fecha y total.
     */
   public function getByUser($userId)
{
    $compras = Compra::with('detalles.producto')
        ->where('user_id', $userId)
        ->orderByDesc('created_at')
        ->get();

    $result = $compras->flatMap(function ($compra) {
        return $compra->detalles->map(function ($detalle) use ($compra) {
            return [
                'id' => $detalle->producto->id ?? null,
                'nombre' => $detalle->producto->name ?? $detalle->producto->title ?? 'Producto eliminado',
                'created_at' => $compra->created_at ? $compra->created_at->toDateTimeString() : null,
                'estado' => $compra->estado ?? 'pendiente',
                'precio' => $detalle->precio_unitario ?? 0,
            ];
        });
    });

    return response()->json($result);
}

}
