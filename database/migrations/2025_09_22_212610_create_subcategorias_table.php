<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateSubcategoriasTable extends Migration
{
    public function up()
    {
        Schema::create('subcategorias', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('categoria_id');
            $table->string('nombre');
            $table->timestamps();

            $table->foreign('categoria_id')
                  ->references('id')->on('categorias')
                  ->onDelete('cascade');
        });
    }

    public function down()
    {
        Schema::dropIfExists('subcategorias');
    }
}
