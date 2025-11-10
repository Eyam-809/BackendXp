<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('products', function (Blueprint $table) {
            // Si quieres permitir productos sin categoría de momento:
            $table->foreignId('categoria_id')
                  ->nullable()
                  ->constrained('categorias')
                  ->cascadeOnUpdate()
                  ->nullOnDelete();
            
            // ------------- OPCIÓN ESTRICTA -------------
            // Si NO quieres que sea opcional, quita ->nullable() y usa:
            // $table->foreignId('categoria_id')
            //       ->constrained('categorias')
            //       ->cascadeOnUpdate()
            //       ->restrictOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->dropForeign(['categoria_id']);
            $table->dropColumn('categoria_id');
        });
    }
};
