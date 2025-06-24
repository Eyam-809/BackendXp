<?php 

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class RecrearColumnaPlanIdEnUsers extends Migration
{/*
    public function up()
    {
        Schema::table('users', function (Blueprint $table) {
            // Eliminamos la columna si existe
            if (Schema::hasColumn('users', 'plan_id')) {
                $table->dropForeign(['plan_id']); // elimina la foreign key
                $table->dropColumn('plan_id');     // elimina la columna
            }

            // Creamos la columna correctamente y la relación
            $table->unsignedBigInteger('plan_id')->nullable();
            $table->foreign('plan_id')->references('id')->on('planes')->onDelete('set null');
        });
    }

    public function down()
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropForeign(['plan_id']);
            $table->dropColumn('plan_id');
        });
    }*/
}

