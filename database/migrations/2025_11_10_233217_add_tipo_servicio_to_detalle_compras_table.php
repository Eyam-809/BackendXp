<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up()
{
    Schema::table('detalle_compras', function (Blueprint $table) {
        $table->string('tipo_servicio')->nullable()->after('subtotal');
    });
}

public function down()
{
    Schema::table('detalle_compras', function (Blueprint $table) {
        $table->dropColumn('tipo_servicio');
    });
}

};
