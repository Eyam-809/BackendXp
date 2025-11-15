<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Compra;
use App\Models\DetalleCompra;
use App\Models\Product;
use App\Models\Pedido; // <-- agregado
use App\Models\Plan;
use App\Models\PlanVigencia;
use Carbon\Carbon;
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
            'productos' => 'required|array|min:1',
            'productos.*.producto_id' => 'required|exists:products,id',
            'productos.*.cantidad' => 'required|integer|min:1',
            'productos.*.precio_unitario' => 'required|numeric|min:0',
            'productos.*.tipo' => 'nullable|string', // opcional: 'venta'|'trueque' u otro
        ]);

        try {
            DB::beginTransaction();

            // Calcular total desde los productos validados
            $total = collect($validated['productos'])->sum(function ($p) {
                return $p['precio_unitario'] * $p['cantidad'];
            });

            // Crear compra principal
            $compra = Compra::create([
                'user_id' => $validated['user_id'],
                'metodo_pago' => $validated['metodo_pago'] ?? null,
                'direccion_envio' => $validated['direccion_envio'] ?? null,
                'telefono_contacto' => $validated['telefono_contacto'] ?? null,
                'estado' => 'pendiente',
                'total' => $total,
                'fecha_pago' => now(),
            ]);

            // Agregar detalle (incluyendo tipo_servicio)
            foreach ($validated['productos'] as $p) {
                $tipoItem = $p['tipo'] ?? 'venta';
                $tipoServicio = $tipoItem === 'venta' ? 'producto' : 'servicio';

                DetalleCompra::create([
                    'compra_id' => $compra->id,
                    'producto_id' => $p['producto_id'],
                    'cantidad' => $p['cantidad'],
                    'precio_unitario' => $p['precio_unitario'],
                    'subtotal' => $p['precio_unitario'] * $p['cantidad'],
                    'tipo_servicio' => $tipoServicio, // <-- guardado aquí
                ]);
            }

            // Crear pedidos (uno por producto) — se mantiene igual
            $lastPedido = null;
            foreach ($validated['productos'] as $p) {
                $lastPedido = Pedido::create([
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

            // 🔥 NUEVO: Actualizar inventario o estado del producto
            $producto = Product::find($p['producto_id']);

            if ($producto) {
                if (isset($producto->stock)) {
                    $producto->stock -= $p['cantidad'];
                    if ($producto->stock <= 0) {
                        $producto->stock = 0;
                        $producto->status_id = 4; // Desactivado
                    }
                } else {
                    $producto->status_id = 4; // Desactivado
                }

                $producto->save();
            }

            DB::commit();

            return response()->json([
                'message' => 'Compra registrada correctamente.',
                'compra' => $compra->load('detalles'),
                'pedido' => $lastPedido
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

/**
 * Crear compra por suscripción (almacena el id del plan en producto_id y tipo_servicio = 'suscripcion')
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

        // Agregar detalle: guardamos el plan id en producto_id según lo solicitado
        $detalle = DetalleCompra::create([
            'compra_id' => $compra->id,
            'producto_id' => $plan->id,            // <-- aquí se guarda el id del plan
            'cantidad' => 1,
            'precio_unitario' => $price,
            'subtotal' => $price,
            'tipo_servicio' => 'suscripcion',     // <-- tipo_servicio como suscripcion
        ]);

        // Crear o actualizar la vigencia del plan para el usuario
        $fecha_inicio = Carbon::now();
        $fecha_fin = $fecha_inicio->copy()->addDays($duration);

        $vigencia = PlanVigencia::updateOrCreate(
            ['user_id' => $userId],
            [
                'plan_id' => $plan->id,
                'fecha_inicio' => $fecha_inicio,
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
     */
    public function countByUser($userId)
    {
        $count = Compra::where('user_id', $userId)->count();

        return response()->json([
            'user_id' => (int) $userId,
            'compras_count' => $count,
        ]);
    }


}
