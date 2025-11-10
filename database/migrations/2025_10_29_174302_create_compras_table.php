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
    Schema::create('compras', function (Blueprint $table) {
        $table->id();
        $table->foreignId('user_id')->constrained('users')->onDelete('cascade');
        $table->decimal('total', 10, 2)->default(0);
        $table->string('metodo_pago', 50)->nullable();
        $table->enum('estado', ['pendiente', 'pagado', 'enviado', 'completado', 'cancelado'])->default('pendiente');
        $table->string('direccion_envio', 255)->nullable();
        $table->string('telefono_contacto', 20)->nullable();
        $table->text('nota')->nullable();
        $table->timestamp('fecha_pago')->nullable();
        $table->timestamps();
    });
}


    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('compras');
    }
};
