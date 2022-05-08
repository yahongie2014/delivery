<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateServiceTypesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('service_types', function (Blueprint $table) {
            //
            $table->increments('id');
            $table->string('name');
            $table->integer('status')
                ->default(0)
                ->comment('0 : SERVICE_TYPE_INACTIVE , 1 : SERVICE_TYPE_ACTIVE');
            $table->integer('type')
                ->default(1)
                ->comment('1 : MAIN_SERVICE , 2 : EXTRA_SERVICE');
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
        Schema::dropIfExists('service_types');
    }
}
