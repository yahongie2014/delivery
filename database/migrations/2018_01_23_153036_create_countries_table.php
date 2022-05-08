<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateCountriesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        //
        Schema::create('countries', function (Blueprint $table) {
            //
            $table->increments('id');
            $table->string('name');
            $table->string('currency_name');
            $table->string('currency_symbol');
            $table->string('code');
            $table->string('flag');
            $table->integer('phone');
            $table->integer('status')
                ->default(0)
                ->comment('0 : COUNTRY_INACTIVE , 1 : COUNTRY_ACTIVE');
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
        Schema::dropIfExists('countries');
    }
}
