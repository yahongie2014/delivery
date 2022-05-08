<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddDiscountColsOrdersTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('orders', function (Blueprint $table) {
            //
            $table->float('main_service_type_discount',8,2)->after('main_service_type_cost')->default(0);
            $table->float('extra_services_type_discount',8,2)->after('extra_service_type_cost')->default(0);
            $table->float('payment_type_discount',8,2)->after('payment_type_cost')->default(0);
            $table->float('total_discount',8,2)->after('total_cost')->default(0);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('orders', function (Blueprint $table) {
            //
            $table->dropColumn(['main_service_type_discount' , 'extra_services_type_discount' , 'payment_type_discount' , 'total_discount']);
        });
    }
}
