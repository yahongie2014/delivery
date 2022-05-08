<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddStatusColDeliverersTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('deliverers', function (Blueprint $table) {
            //
            $table->integer('status')
                ->after('license_id')
                ->default(0)
                ->comment('0 : DELIVERY_INACTIVE , 1 : DELIVERY_ACTIVE');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('deliverers', function (Blueprint $table) {
            //
            $table->dropColumn('status');
        });
    }
}
