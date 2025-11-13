<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('payment_cards', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->string('type', 20); // 'debit' | 'credit'
            $table->string('card_holder_name');
            $table->string('card_last4', 4);
            $table->string('card_expiry')->nullable();
            $table->text('card_number')->nullable(); // encrypted
            $table->text('cvv')->nullable(); // encrypted
            $table->string('provider')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('payment_cards');
    }
};