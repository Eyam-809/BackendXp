<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Ejecutar la migración.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('users', function (Blueprint $table) {
            // Agregar los campos dirección y teléfono
            $table->string('direccion')->nullable();  // Opcional
            $table->string('telefono')->nullable();   // Opcional
        });
    }

    /**
     * Revertir la migración.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('users', function (Blueprint $table) {
            // Eliminar los campos dirección y teléfono
            $table->dropColumn(['direccion', 'telefono']);
        });
    }
};
