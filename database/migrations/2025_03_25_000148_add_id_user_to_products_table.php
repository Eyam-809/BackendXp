<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
   /**
     * Ejecuta la migración.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('products', function (Blueprint $table) {
            // Agregar la columna id_user
            $table->unsignedBigInteger('id_user')->nullable();

            // Definir la clave foránea hacia la tabla users
            $table->foreign('id_user')->references('id')->on('users')->onDelete('cascade');
        });
    }

    /**
     * Revertir la migración.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('products', function (Blueprint $table) {
            // Eliminar la clave foránea y la columna id_user
            $table->dropForeign(['id_user']);
            $table->dropColumn('id_user');
        });
    }
};
