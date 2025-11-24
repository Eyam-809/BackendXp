<?php

namespace App\Http\Controllers;

use App\Models\Compra;
use App\Models\DetalleCompra;
use App\Models\Product;
use App\Models\Pedido;
use App\Models\Plan;
use App\Models\PlanVigencia;
use Carbon\Carbon;
use App\Models\UserPoints;
use App\Models\PointsHistory;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Models\PaymentTransaction;

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
     * (Versión fusionada con manejo de inventario, pedidos y puntos de lealtad).
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'user_id' => 'required|exists:users,id',
            'metodo_pago' => 'nullable|string|max:50',
            'direccion_envio' => 'nullable|string|max:255',
            'telefono_contacto' => 'nullable|string|max:20',
            'estado' => 'nullable|in:pendiente,pagado,enviado,completado,cancelado',
            // En esta versión fusionada, se acepta 'productos' (HEAD) o 'items' (Joni)
            'productos' => 'required_without:items|array|min:1',
            'productos.*.producto_id' => 'required_without:items|exists:products,id',
            'productos.*.cantidad' => 'required_without:items|integer|min:1',
            'productos.*.precio_unitario' => 'required_without:items|numeric|min:0',
            'productos.*.tipo' => 'nullable|string', // opcional: 'venta'|'trueque' u otro

            // Se asume que si se usa 'items', la lógica de puntos (Joni) requiere 'total' y 'direccion_id'
            'items' => 'required_without:productos|array|min:1',
            'total' => 'required_without:productos|numeric|min:0',
            'direccion_id' => 'required_without:productos|exists:direcciones,id'
        ]);

        try {
            DB::beginTransaction();

            $productos = $validated['productos'] ?? $validated['items'];

            // 1. Calcular total (HEAD/Usuario)
            $total = collect($productos)->sum(function ($p) {
                return $p['precio_unitario'] * $p['cantidad'];
            });

            // 2. Crear compra principal (HEAD/Usuario + Dirección de Joni)
            $compra = Compra::create([
                'user_id' => $validated['user_id'],
                'metodo_pago' => $validated['metodo_pago'] ?? null,
                // Usamos la dirección de envío del request si existe, si no, intentamos usar 'direccion_id' de Joni
                'direccion_envio' => $validated['direccion_envio'] ?? null,
                'direccion_id' => $validated['direccion_id'] ?? null,
                'telefono_contacto' => $validated['telefono_contacto'] ?? null,
                'estado' => $validated['estado'] ?? 'pendiente',
                'total' => $total, // Usamos el total calculado
                'fecha_pago' => now(),
            ]);

            $lastPedido = null;
            $pointsToAdd = 0;

            PaymentTransaction::where('transaction_id', $request->stripe_charge_id)
                ->update(['compra_id' => $compra->id]);
            // 3. Procesar detalles, pedidos y actualizar inventario (HEAD/Usuario)
            foreach ($productos as $p) {
                // Detalle de Compra
                $tipoItem = $p['tipo'] ?? 'venta';
                $tipoServicio = $tipoItem === 'venta' ? 'producto' : 'servicio';

                DetalleCompra::create([
                    'compra_id' => $compra->id,
                    'producto_id' => $p['producto_id'],
                    'cantidad' => $p['cantidad'],
                    'precio_unitario' => $p['precio_unitario'],
                    'subtotal' => $p['precio_unitario'] * $p['cantidad'],
                    'tipo_servicio' => $tipoServicio,
                ]);

                // Crear Pedidos (HEAD/Usuario)
                $lastPedido = Pedido::create([
                    'user_id' => $validated['user_id'],
                    'compra_id' => $compra->id,
                    'estado' => 'en proceso de empaquetado',
                    'total' => $compra->total, // OJO: Esto guarda el total de la compra en cada pedido, verificar si es lo que se desea.
                    'direccion_envio' => $compra->direccion_envio,
                    'telefono_contacto' => $compra->telefono_contacto,
                    'producto_id' => $p['producto_id'],
                    'fecha_pedido' => now(),
                ]);

                // Actualizar inventario o estado del producto (HEAD/Usuario)
                $producto = Product::find($p['producto_id']);
                if ($producto) {
                    if (isset($producto->stock)) {
                        $producto->stock -= $p['cantidad'];
                        if ($producto->stock <= 0) {
                            $producto->stock = 0;
                            $producto->status_id = 4; // Desactivado
                        }
                    } else {
                        $producto->status_id = 4; // Desactivado (Para productos sin stock, como servicios/trueques)
                    }
                    $producto->save();
                }
            }

            // 4. Lógica de Puntos de Lealtad (Joni)
            // Calcular puntos: 10 puntos por cada $100 gastados
            $pointsToAdd = (int)($total / 100) * 10; // Usamos el total calculado

            if ($pointsToAdd > 0) {
                $this->addPointsFromPurchase(
                    $validated['user_id'],
                    $pointsToAdd,
                    $total,
                    "Compra #" . $compra->id
                );
            }

            DB::commit();

            return response()->json([
                'message' => 'Compra y pedido registrados correctamente.',
                'compra' => $compra->load('detalles'),
                'pedido_muestra' => $lastPedido,
                'points_added' => $pointsToAdd
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
                // Se agregó seguridad para verificar si el producto existe.
                $nombreProducto = 'Producto eliminado';
                $idProducto = null;
                if ($detalle->producto) {
                     $nombreProducto = $detalle->producto->name ?? $detalle->producto->title;
                     $idProducto = $detalle->producto->id;
                }

                return [
                    'id' => $idProducto,
                    'nombre' => $nombreProducto,
                    'created_at' => $compra->created_at ? $compra->created_at->toDateTimeString() : null,
                    'estado' => $compra->estado ?? 'pendiente',
                    'precio' => $detalle->precio_unitario ?? 0,
                    'tipo_servicio' => $detalle->tipo_servicio ?? 'producto',
                ];
            });
        });

        return response()->json($result);
    }

    /**
     * Crear compra por suscripción (almacena el id del plan en producto_id y tipo_servicio = 'suscripcion')
     * (Método del HEAD/Usuario)
     */
    public function storeSubscription(Request $request)
    {
        $validated = $request->validate([
            'user_id' => 'required|exists:users,id',
            'plan_id' => 'required|exists:plans,id',
            'metodo_pago' => 'nullable|string|max:50',
            'telefono_contacto' => 'nullable|string|max:20',
            'direccion_envio' => 'nullable|string|max:255',
            'duration_days' => 'nullable|integer|min:1',
            'payment_reference' => 'nullable|string',
        ]);

        try {
            DB::beginTransaction();

            $plan = Plan::findOrFail($validated['plan_id']);
            $userId = $validated['user_id'];
            $price = $plan->price ?? 0;
            $duration = $validated['duration_days'] ?? ($plan->duration_days ?? 30);

            // Crear compra principal
            $compra = Compra::create([
                'user_id' => $userId,
                'metodo_pago' => $validated['metodo_pago'] ?? null,
                'direccion_envio' => $validated['direccion_envio'] ?? null,
                'telefono_contacto' => $validated['telefono_contacto'] ?? null,
                'estado' => 'pendiente',
                'total' => $price,
                'fecha_pago' => now(),
            ]);

            // Agregar detalle: guardamos el plan id en producto_id
            $detalle = DetalleCompra::create([
                'compra_id' => $compra->id,
                'producto_id' => $plan->id,       // <-- aquí se guarda el id del plan
                'cantidad' => 1,
                'precio_unitario' => $price,
                'subtotal' => $price,
                'tipo_servicio' => 'suscripcion', // <-- tipo_servicio como suscripcion
            ]);

            // Crear o actualizar la vigencia del plan para el usuario
            $fecha_inicio = Carbon::now();
            $fecha_fin = $fecha_inicio->copy()->addDays($duration);

            $vigencia = PlanVigencia::updateOrCreate(
                ['user_id' => $userId],
                [
                    'plan_id' => $plan->id,
                    'fecha_inicio' => $fecha_inicio,
                    // Si ya tiene una vigencia, la nueva fecha de fin debe ser después de la vigencia actual si esta es futura,
                    // pero para simplificar, se toma la lógica de reemplazar o establecer nueva.
                    'fecha_fin' => $fecha_fin,
                    'payment_reference' => $validated['payment_reference'] ?? null,
                ]
            );

            // Actualizar el plan del usuario
            $user = \App\Models\User::find($userId);
            if ($user) {
                $user->plan_id = $plan->id;
                $user->save();
            }

            DB::commit();

            return response()->json([
                'message' => 'Compra de suscripción creada correctamente.',
                'compra' => $compra->load('detalles'),
                'detalle' => $detalle,
                'vigencia' => $vigencia,
            ], 201);
        } catch (\Throwable $e) {
            DB::rollBack();
            \Log::error('Error creando suscripción/compra', [
                'error' => $e->getMessage(),
                'request' => $request->all()
            ]);
            return response()->json(['error' => 'Error al crear compra de suscripción: ' . $e->getMessage()], 500);
        }
    }

    /**
     * Retorna el número de compras de un usuario.
     * (Método del HEAD/Usuario)
     */
    public function countByUser($userId)
    {
        $count = Compra::where('user_id', $userId)->count();

        return response()->json([
            'user_id' => (int) $userId,
            'compras_count' => $count,
        ]);
    }

    /**
     * Método privado para agregar puntos desde una compra
     * (Método de Joni)
     */
    private function addPointsFromPurchase($userId, $pointsToAdd, $amount, $description)
    {
        try {
            // Obtener o crear registro de puntos del usuario
            $userPoints = UserPoints::where('user_id', $userId)->first();

            if (!$userPoints) {
                // Asumo que 'calculateLevel' existe en UserPoints. Si no existe, este código fallará.
                // Se inicializa con los valores predeterminados y se guarda.
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

            // Se asume que este método existe en el modelo UserPoints
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