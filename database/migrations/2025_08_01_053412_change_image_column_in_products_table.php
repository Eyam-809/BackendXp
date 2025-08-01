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
        Schema::table('products', function (Blueprint $table) {
            $table->longText('image')->nullable()->change();

        });
    }

    public function down()
    {
        Schema::table('products', function (Blueprint $table) {
            $table->string('image', 255)->change(); // vuelve a varchar(255) si haces rollback
        });
    }
};
