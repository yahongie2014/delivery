<?php

use Illuminate\Database\Seeder;

class LanguageTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        //
        DB::table('languages')->insert([
            'name' => "العربية",
            'symbol' => "ar",
            'direction' => "rtl",
            'status' => 1,
            'default' => 1,
        ]);

        DB::table('languages')->insert([
            'name' => "English",
            'symbol' => "en",
            'direction' => "ltr",
            'status' => 1,
            'default' => 0,
        ]);


    }
}
