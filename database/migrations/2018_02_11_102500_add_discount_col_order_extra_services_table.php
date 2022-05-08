<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddDiscountColOrderExtraServicesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('order_extra_services', function (Blueprint $table) {
            //
            $table->float('discount',8,2)->after('price')->defalt(0);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('order_extra_services', function (Blueprint $table) {
            //
            $table->dropColumn(['discount']);
        });
    }
}
