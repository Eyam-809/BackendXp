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
        Schema::create('pedidos', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade');
            $table->enum('estado', ['en proceso de empaquetado', 'en camino', 'entregado'])->default('en proceso de empaquetado');
            $table->string('direccion_envio', 255)->nullable();
            $table->string('telefono_contacto', 20)->nullable();
            $table->decimal('total', 10, 2)->default(0);
            $table->foreignId('compra_id')->constrained('compras')->onDelete('cascade');
            $table->foreignId('producto_id')->constrained('products')->onDelete('cascade');
            $table->timestamp('fecha_pedido')->useCurrent();
            $table->timestamp('fecha_actualizacion_estado')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('pedidos');
    }
};
