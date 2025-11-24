<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;

class PaymentCardTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function user_can_register_card_and_it_is_persisted_in_database()
    {
        // Crear usuario y autenticarnos (Sanctum)
        $user = User::factory()->create();
        Sanctum::actingAs($user, ['*']);

        // Payload de ejemplo
        $payload = [
            'type' => 'debit',
            'card_number' => '4242 4242 4242 4242',
            'card_holder_name' => 'JOHN DOE',
            'card_expiry' => '12/25',
            'cvv' => '123',
        ];

        // Llamada a la API para crear tarjeta
        $response = $this->postJson('/api/cards', $payload);

        // Debe devolver 201 y datos con last4
        $response->assertStatus(201)
                 ->assertJsonFragment([
                     'card_last4' => '4242',
                     'type' => 'debit',
                 ]);

        // Comprobar que quedó en la BD
        $this->assertDatabaseHas('payment_cards', [
            'user_id' => $user->id,
            'card_last4' => '4242',
            'type' => 'debit',
        ]);

        // Comprobar que la ruta GET /api/cards devuelve la tarjeta
        $this->getJson('/api/cards')
             ->assertStatus(200)
             ->assertJsonCount(1);
    }
}