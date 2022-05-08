<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddColsOrdersTable extends Migration
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
            $table->string('d_order_lat' , 100)->after('comments')->nullable();
            $table->string('d_order_long' , 100)->after('d_order_lat')->nullable();
            $table->float('price',8 , 2 )->after('d_order_long')->nullable();
            $table->float('paid',8 , 2 )->after('price')->nullable()->default(0.00);
            $table->integer('category_id')->after('paid');
            $table->integer('main_service_type_id')->after('category_id');
            $table->integer('extra_service_type_id')->after('main_service_type_id')->nullable();
            $table->integer('payment_type_id')->after('extra_service_type_id');

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
            $table->dropColumn('d_order_lat');
            $table->dropColumn('d_order_long');
            $table->dropColumn('price');
            $table->dropColumn('paid');
            $table->dropColumn('main_service_type_id');
            $table->dropColumn('category_id');
            $table->dropColumn('extra_service_type_id');
            $table->dropColumn('payment_type_id');
        });
    }
}
