<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
   public function up()
{
    Schema::table('payment_transactions', function (Blueprint $table) {
        $table->decimal('fee', 10, 2)->nullable()->after('amount');
        $table->decimal('net_amount', 10, 2)->nullable()->after('fee');
    });
}

public function down()
{
    Schema::table('payment_transactions', function (Blueprint $table) {
        $table->dropColumn(['fee', 'net_amount']);
    });
}

};
