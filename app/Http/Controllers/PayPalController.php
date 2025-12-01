<?php

namespace App\Http\Controllers;

use Srmklive\PayPal\Services\PayPal as PayPalClient;
use Illuminate\Http\Request;
use Exception;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;
use App\Models\Compra;
use App\Models\PaymentTransaction;

class PayPalController extends Controller
{
    public function createPayment(Request $request)
    {
        try {
            $request->validate([
                'amount'    => 'required|numeric|min:1',
                'currency'  => 'required|string',
                'description' => 'nullable|string',
                'compra_id' => 'nullable|integer|exists:compras,id',
            ]);

            $provider = new PayPalClient;
            $provider->setApiCredentials(config('paypal'));
            $provider->getAccessToken();

            // Incluir compra_id y modo popup en return/cancel urls
            $returnUrl = route('paypal.success', [
                'compra_id' => $request->compra_id,
                'mode'      => 'popup',
            ]);

            $cancelUrl = route('paypal.cancel', [
                'compra_id' => $request->compra_id,
                'mode'      => 'popup',
            ]);

            $order = $provider->createOrder([
                "intent" => "CAPTURE",
                "purchase_units" => [
                    [
                        "amount" => [
                            "currency_code" => strtoupper($request->currency),
                            "value" => number_format($request->amount, 2, '.', ''),
                        ],
                        "description" => $request->description ?? "Pago XPMarket",
                    ],
                ],
                "application_context" => [
                    "return_url" => $returnUrl,
                    "cancel_url" => $cancelUrl,
                ],
            ]);

            foreach ($order['links'] as $link) {
                if ($link['rel'] === 'approve') {
                    return response()->json([
                        'approval_url' => $link['href'],
                    ]);
                }
            }

            return response()->json(['error' => 'No se encontró URL de aprobación'], 500);
        } catch (\Illuminate\Validation\ValidationException $ve) {
            return response()->json(['errors' => $ve->errors()], 422);
        } catch (Exception $e) {
            Log::error('PayPal createPayment error: ' . $e->getMessage());
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    public function success(Request $request)
    {
        try {
            $provider = new PayPalClient;
            $provider->setApiCredentials(config('paypal'));
            $provider->getAccessToken();

            // PayPal devuelve 'token' en la query para capturePaymentOrder
            $token    = $request->query('token');
            $compraId = $request->query('compra_id');

            $response = $provider->capturePaymentOrder($token);

            // Extraer información de la captura
            $capture     = null;
            $status      = $response['status'] ?? null;
            $captureId   = null;
            $amountValue = null;
            $currency    = null;

            if (!empty($response['purchase_units'][0]['payments']['captures'][0])) {
                $c         = $response['purchase_units'][0]['payments']['captures'][0];
                $capture   = $c;
                $captureId = $c['id'] ?? null;
                $status    = $c['status'] ?? $status;
                $amountValue = $c['amount']['value'] ?? null;
                $currency    = $c['amount']['currency_code'] ?? null;
            }

            // Mapear status de PayPal a 'succeeded' para consistencia con Stripe
            if (is_string($status)) {
                $upper = strtoupper($status);
                if (in_array($upper, ['COMPLETED'])) {
                    $mappedStatus = 'succeeded';
                } else {
                    $mappedStatus = strtolower($upper);
                }
            } else {
                $mappedStatus = $status;
            }

            DB::beginTransaction();
            try {
                // Extraer comisiones y monto neto
                $fee = null;
                $net = null;

                if (isset($capture['seller_receivable_breakdown'])) {
                    $breakdown = $capture['seller_receivable_breakdown'];

                    // Comisión total cobrada por PayPal
                    $fee = $breakdown['paypal_fee']['value'] ?? null;

                    // Neto recibido después de comisiones
                    $net = $breakdown['net_amount']['value'] ?? null;
                }

                PaymentTransaction::create([
                    'transaction_id' => $captureId ?? ($response['id'] ?? null),
                    'compra_id'      => $compraId ?? null,
                    'provider'       => 'paypal',
                    'payment_method' => 'paypal',
                    'amount'         => $amountValue ?? null,
                    'fee'            => $fee,
                    'net_amount'     => $net,
                    'currency'       => $currency ?? null,
                    'status'         => $mappedStatus,
                    'raw_response'   => json_encode($response, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES),
                ]);

                // Si se proporcionó compra_id, validar monto y marcar como pagada solo si el pago fue exitoso
                if ($compraId) {
                    $compra = Compra::find($compraId);
                    if ($compra) {
                        if ($amountValue !== null && floatval($amountValue) != floatval($compra->total)) {
                            Log::warning("PayPal capture monto distinto a compra.total", [
                                'compra_id'       => $compraId,
                                'compra_total'    => $compra->total,
                                'captured_amount' => $amountValue,
                            ]);
                        } else {
                            if ($mappedStatus === 'succeeded') {
                                $compra->estado     = 'pagado';
                                $compra->fecha_pago = $compra->fecha_pago ?? now();
                                $compra->save();
                            } else {
                                Log::warning("PayPal capture status no exitoso, no se marca compra como pagada", [
                                    'compra_id'      => $compraId,
                                    'paypal_status'  => $status,
                                    'mapped_status'  => $mappedStatus,
                                ]);
                            }
                        }
                    }
                }

                DB::commit();
            } catch (\Throwable $e) {
                DB::rollBack();
                Log::error('Error guardando transacción PayPal: ' . $e->getMessage(), ['response' => $response]);
                return response()->json(['error' => 'Error al procesar transacción'], 500);
            }

            // Payload genérico
            $payload = [
                'success' => true,
                'capture' => $capture ?? $response,
            ];

            // URL del front (puedes cambiar a config('app.frontend_url'))
            $frontendUrl = 'http://localhost:3000';
            $mode        = $request->query('mode'); // 'popup' o null

            if ($request->wantsJson()) {
                return response()->json($payload + ['redirect' => $frontendUrl]);
            }

            if ($mode === 'popup') {
                $compraIdForJs = $compraId ?? null;

                // Respuesta especial para la ventana emergente
                return response(
                    '<!DOCTYPE html>
<html>
<head><meta charset="utf-8"><title>Pago completado</title></head>
<body>
    <script>
        try {
            if (window.opener) {
                window.opener.postMessage(
                    {
                        type: "PAYPAL_SUCCESS",
                        compra_id: ' . json_encode($compraIdForJs) . ',
                        provider: "paypal"
                    },
                    ' . json_encode($frontendUrl) . '
                );
            }
        } catch (e) {
            console.error("Error enviando mensaje al opener", e);
        }
        window.close();
    </script>
    <p>Procesando pago, puedes cerrar esta ventana...</p>
</body>
</html>'
                );
            }

            // Modo normal (sin popup): redirigir a la página principal del front
            return redirect()->to($frontendUrl);
        } catch (Exception $e) {
            Log::error('PayPal success error: ' . $e->getMessage());
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    public function cancel()
    {
        return "Pago cancelado por el usuario.";
    }
}
