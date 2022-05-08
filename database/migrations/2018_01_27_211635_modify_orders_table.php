<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class ModifyOrdersTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('orders', function (Blueprint $table) {
            // columns to be added
            $table->dateTime('loading_at')->after('required_at')->nullable();
            $table->string('l_order_lat',100)->after('d_order_lat')->nullable();
            $table->string('l_order_long',100)->after('l_order_lat')->nullable();
            $table->float('main_service_type_cost')->after('main_service_type_id')->default(0);
            $table->float('extra_service_type_cost')->after('extra_service_type_id')->default(0);
            $table->float('payment_type_cost')->after('payment_type_id')->default(0);
            $table->float('total_cost')->after('payment_type_cost')->default(0);
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
        });
    }
}
