<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CteateOrdersTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        //
        Schema::create('orders', function (Blueprint $table) {
            $table->increments('id');
            $table->integer('provider_id');
            $table->integer('delivery_id')->nullable();
            $table->dateTime('assigned_at')->nullable();
            $table->dateTime('delivered_at')->nullable();
            $table->dateTime('required_at')->nullable();
            $table->string('client_name',255);
            $table->string('client_phone' , 255);
            $table->text('client_address');
            $table->string('order_lat' , 100)->nullable();
            $table->string('order_long' , 100)->nullable();
            $table->integer('status')->default(0);
            $table->text('details');
            $table->string('comments' , 100)->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        //
        Schema::dropIfExists('orders');
    }
}
