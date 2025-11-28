<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Stripe\Stripe;
use Stripe\Charge;
use Stripe\BalanceTransaction;
use App\Models\Compra;
use App\Models\PaymentTransaction;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;

class StripeController extends Controller
{
    public function charge(Request $request)
    {
        $request->validate([
            'token'     => 'required',
            'amount'    => 'required|numeric',
            'compra_id' => 'required|integer|exists:compras,id'
        ]);

        $compra = Compra::find($request->compra_id);

        // validar estado y monto
        if (!$compra || $compra->estado !== 'pendiente') {
            return response()->json([
                'success' => false,
                'message' => 'Compra no válida o no pendiente'
            ], 400);
        }

        if (floatval($request->amount) != floatval($compra->total)) {
            return response()->json([
                'success' => false,
                'message' => 'El monto no coincide con la compra'
            ], 400);
        }

        Stripe::setApiKey(env('STRIPE_SECRET'));

        DB::beginTransaction();
        try {

            // Crear el cargo
            $charge = Charge::create([
                "amount"      => intval($request->amount * 100),
                "currency"    => "mxn",
                "source"      => $request->token,
                "description" => "XPMarket compra #{$compra->id}",
                "receipt_email" => $request->email,
            ]);

           $balance = BalanceTransaction::retrieve($charge->balance_transaction);

            $amount = $balance->amount / 100;       // Total
            $fee    = $balance->fee / 100;          // Comisión de Stripe
            $net    = $balance->net / 100;          // Neto recibido
            $method = $charge->payment_method_details->type ?? null;

            // Guardar transacción
            PaymentTransaction::create([
                'transaction_id' => $charge->id,
                'compra_id'      => $compra->id,
                'provider'       => 'stripe',
                'payment_method' => $method,
                'amount'         => $amount,
                'fee'            => $fee,
                'net_amount'     => $net,
                'currency'       => 'mxn',
                'status'         => $charge->status,
                'raw_response'   => json_encode($charge->toArray(), JSON_UNESCAPED_SLASHES)
            ]);

            // Marcar compra como pagada
            $compra->estado = 'pagado';
            $compra->fecha_pago = $compra->fecha_pago ?? now();
            $compra->save();

            DB::commit();

            return response()->json([
                "success" => true,
                "data" => $charge
            ]);

        } catch (\Exception $e) {

            DB::rollBack();
            Log::error('Stripe charge error: ' . $e->getMessage(), [
                'compra_id' => $compra->id ?? null
            ]);

            return response()->json([
                "success" => false,
                "message" => $e->getMessage()
            ], 400);
        }
    }
}
