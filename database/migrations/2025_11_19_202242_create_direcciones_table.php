<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{/**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('direcciones', function (Blueprint $table) {
            $table->id();

            $table->unsignedBigInteger('user_id'); // Relación con usuarios
            $table->string('tipo'); // Ej: casa, trabajo, otro
            $table->string('nombre_direccion')->nullable();
            $table->string('calle');
            $table->string('numero')->nullable();
            $table->string('apartamento_oficina')->nullable();
            $table->string('ciudad');
            $table->string('estado');
            $table->string('codigo_postal');
            $table->string('pais');
            $table->string('telefono')->nullable();
            $table->text('instrucciones')->nullable();

            $table->timestamps();

            // Foreign key
            $table->foreign('user_id')
                ->references('id')->on('users')
                ->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('direcciones');
    }
};
