<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\PaymentCard;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class PaymentCardController extends Controller
{
    public function index(Request $request)
    {
        $user = $request->user();
        $cards = $user->paymentCards()->latest()->get()->map(function (PaymentCard $c) {
            return [
                'id' => $c->id,
                'type' => $c->type,
                'card_holder_name' => $c->card_holder_name,
                'card_last4' => $c->card_last4,
                'card_expiry' => $c->card_expiry,
                // not returning decrypted full number/cvv — only masked
                'masked' => '**** **** **** ' . $c->card_last4,
                'created_at' => $c->created_at,
            ];
        });

        return response()->json($cards);
    }

    public function store(Request $request)
    {
        $request->validate([
            'type' => 'required|in:debit,credit',
            'card_number' => 'required|string|min:12',
            'card_holder_name' => 'required|string',
            'card_expiry' => 'nullable|string',
            'cvv' => 'nullable|string',
            'provider' => 'nullable|string',
        ]);

        $user = $request->user();

        $cleanNumber = preg_replace('/\D+/', '', $request->input('card_number'));
        $last4 = substr($cleanNumber, -4);

        $card = PaymentCard::create([
            'user_id' => $user->id,
            'type' => $request->input('type'),
            'card_holder_name' => $request->input('card_holder_name'),
            'card_last4' => $last4,
            'card_expiry' => $request->input('card_expiry'),
            'card_number' => $cleanNumber, // will be encrypted by cast
            'cvv' => $request->input('cvv'),
            'provider' => $request->input('provider'),
        ]);

        return response()->json([
            'id' => $card->id,
            'type' => $card->type,
            'card_holder_name' => $card->card_holder_name,
            'card_last4' => $card->card_last4,
            'card_expiry' => $card->card_expiry,
            'masked' => '**** **** **** ' . $card->card_last4,
            'created_at' => $card->created_at,
        ], 201);
    }

    public function destroy(Request $request, $id)
    {
        $user = $request->user();
        $card = PaymentCard::where('user_id', $user->id)->where('id', $id)->first();

        if (! $card) {
            return response()->json(['message' => 'No encontrado'], 404);
        }

        $card->delete();

        return response()->json(['message' => 'Eliminada']);
    }
}