<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Stripe\Stripe;
use Stripe\Charge;
use App\Models\Compra;
use App\Models\PaymentTransaction;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;

class StripeController extends Controller
{
    public function charge(Request $request)
    {
        $request->validate([
            'token'    => 'required',
            'amount'   => 'required|numeric',
            'compra_id'=> 'required|integer|exists:compras,id' // ahora obligatorio
        ]);

        $compra = Compra::find($request->compra_id);

        // validar estado y monto
        if (!$compra || $compra->estado !== 'pendiente') {
            return response()->json(['success' => false, 'message' => 'Compra no válida o no pendiente'], 400);
        }
        if (floatval($request->amount) != floatval($compra->total)) {
            return response()->json(['success' => false, 'message' => 'El monto no coincide con la compra'], 400);
        }

        Stripe::setApiKey(env('STRIPE_SECRET'));

        DB::beginTransaction();
        try {
            $charge = Charge::create([
                "amount" => intval($request->amount * 100),
                "currency" => "mxn",
                "source" => $request->token,
                "description" => "XPMarket compra #{$compra->id}",
                "receipt_email" => $request->email,
            ]);

            // guardar transacción (si el modelo existe)
            try {
                PaymentTransaction::create([
                    'transaction_id' => $charge->id ?? ($charge['id'] ?? null),
                    'compra_id' => $compra->id,
                    'amount' => $request->amount,
                    'currency' => 'mxn',
                    'status' => $charge->status ?? 'succeeded',
                    'response' => json_encode($charge)
                ]);
            } catch (\Throwable $e) {
                \Log::warning('No se pudo guardar PaymentTransaction: ' . $e->getMessage());
            }

            // marcar compra como pagada
            $compra->estado = 'pagado';
            $compra->fecha_pago = $compra->fecha_pago ?? now();
            $compra->save();

            DB::commit();

            return response()->json(["success" => true, "data" => $charge]);

        } catch (\Exception $e) {
            DB::rollBack();
            \Log::error('Stripe charge error: ' . $e->getMessage(), ['compra_id' => $compra->id ?? null]);
            return response()->json(["success" => false, "message" => $e->getMessage()], 400);
        }
    }
}
