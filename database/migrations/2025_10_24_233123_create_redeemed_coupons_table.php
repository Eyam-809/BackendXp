<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('redeemed_coupons', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade');
            $table->string('coupon_type'); // 'descuento', 'envio', 'producto', 'premium'
            $table->string('coupon_name');
            $table->text('description');
            $table->integer('points_spent');
            $table->string('coupon_code')->unique(); // Código único del cupón
            $table->boolean('is_used')->default(false);
            $table->timestamp('used_at')->nullable();
            $table->decimal('discount_amount', 10, 2)->nullable(); // Para cupones de descuento
            $table->integer('discount_percentage')->nullable(); // Para cupones de porcentaje
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('redeemed_coupons');
    }
};
