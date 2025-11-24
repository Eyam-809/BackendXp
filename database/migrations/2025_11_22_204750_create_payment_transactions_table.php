<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up()
{
    Schema::create('payment_transactions', function (Blueprint $table) {
        $table->id();
        $table->unsignedBigInteger('compra_id')->nullable();
        $table->string('provider')->default('stripe');      // stripe, paypal, efectivo
        $table->string('transaction_id')->nullable();       // charge_id de Stripe
        $table->string('status')->default('pending');       // pending, succeeded, failed
        $table->string('payment_method')->nullable();       // card, oxxo, etc.
        $table->decimal('amount', 10, 2);
        $table->string('currency')->default('mxn');
        $table->string('card_last4')->nullable();
        $table->string('card_brand')->nullable();
        $table->string('receipt_url')->nullable();
        $table->json('raw_request')->nullable();
        $table->json('raw_response')->nullable();
        $table->timestamps();

        $table->foreign('compra_id')->references('id')->on('compras')->onDelete('cascade');
    });
}


    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('payment_transactions');
    }
};
