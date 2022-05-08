<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreatePaymentTypesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        //
        Schema::create('payment_types', function (Blueprint $table) {
            //
            $table->increments('id');
            $table->string('name');
            $table->integer('status')
                ->default(0)
                ->comment('0 : PAYMENT_TYPE_INACTIVE , 1 : PAYMENT_TYPE _ACTIVE');
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
        Schema::dropIfExists('payment_types');
    }
}
