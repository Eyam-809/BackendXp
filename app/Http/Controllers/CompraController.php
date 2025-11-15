<?php

namespace App\Http\Controllers;

use App\Models\Compra;
use App\Models\DetalleCompra;
use App\Models\UserPoints;
use App\Models\PointsHistory;
use Illuminate\Http\Request;
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
        $request->validate([
            'user_id' => 'required|exists:users,id',
            'total' => 'required|numeric|min:0',
            'items' => 'required|array',
            'direccion_id' => 'required|exists:direcciones,id'
        ]);

        DB::beginTransaction();
        try {
            // Crear la compra
            $compra = Compra::create([
                'user_id' => $request->user_id,
                'total' => $request->total,
                'estado' => 'pendiente',
                'direccion_id' => $request->direccion_id
            ]);

            // Crear detalles de la compra
            foreach ($request->items as $item) {
                DetalleCompra::create([
                    'compra_id' => $compra->id,
                    'producto_id' => $item['producto_id'],
                    'cantidad' => $item['cantidad'],
                    'precio_unitario' => $item['precio_unitario'],
                    'subtotal' => $item['cantidad'] * $item['precio_unitario']
                ]);
            }

            // ========== NUEVO: REGISTRAR PUNTOS ==========
            // Calcular puntos: 10 puntos por cada $100 gastados
            $pointsToAdd = (int)($request->total / 100) * 10;

            if ($pointsToAdd > 0) {
                $this->addPointsFromPurchase(
                    $request->user_id,
                    $pointsToAdd,
                    $request->total,
                    "Compra #" . $compra->id
                );
            }
            // =========================================

            DB::commit();

            return response()->json([
                'message' => 'Compra creada exitosamente',
                'compra' => $compra,
                'points_added' => $pointsToAdd ?? 0
            ], 201);

        } catch (\Exception $e) {
            DB::rollback();
            return response()->json(['error' => 'Error al crear compra: ' . $e->getMessage()], 500);
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

/**
     * Método privado para agregar puntos desde una compra
     */
    private function addPointsFromPurchase($userId, $pointsToAdd, $amount, $description)
    {
        try {
            // Obtener o crear registro de puntos del usuario
            $userPoints = UserPoints::where('user_id', $userId)->first();
            
            if (!$userPoints) {
                $userPoints = UserPoints::create([
                    'user_id' => $userId,
                    'points_earned' => 0,
                    'points_spent' => 0,
                    'current_points' => 0,
                    'level' => 'Bronce',
                    'total_earned' => 0,
                    'monthly_goal' => 1000,
                    'monthly_progress' => 0
                ]);
            }

            // Guardar nivel anterior para comparar
            $oldLevel = $userPoints->level;

            // Actualizar puntos
            $userPoints->points_earned += $pointsToAdd;
            $userPoints->current_points += $pointsToAdd;
            $userPoints->total_earned += $pointsToAdd;
            $userPoints->monthly_progress += $pointsToAdd;
            
            // Verificar si subió de nivel
            $newLevel = $userPoints->calculateLevel();
            if ($newLevel !== $oldLevel) {
                $userPoints->level = $newLevel;
            }
            
            $userPoints->save();

            // Crear registro en historial
            PointsHistory::create([
                'user_id' => $userId,
                'points' => $pointsToAdd,
                'type' => 'earned',
                'description' => $description,
                'source' => 'purchase',
                'amount' => $amount
            ]);

            return true;

        } catch (\Exception $e) {
            \Log::error("Error al agregar puntos: " . $e->getMessage());
            return false;
        }
    }
}
