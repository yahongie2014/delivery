<?php

use Illuminate\Database\Seeder;
use Illuminate\Database\Eloquent\Model;

class CountriesTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        //
        DB::table('countries')->insert([
            'name' => "السعودية",
            'currency_name' => "ريال سعودي",
            'currency_symbol' => "SAR",
            'code' => "+966",
            'flag' => "none",
            'phone' => "8",
            'status' => '1',
            'time_zone' => 'Asia/Riyadh'
        ]);

        DB::table('countries')->insert([
            'name' => "مصر",
            'currency_name' => "جنية مصري",
            'currency_symbol' => "EGP",
            'code' => "+20",
            'flag' => "none",
            'phone' => "10",
            'status' => '1',
            'time_zone' => 'Africa/Cairo'
        ]);

        DB::table('country_language')->insert([
            'country_id' => 1,
            'language_id' => 1,
            'name' => "السعودية"
        ]);

        DB::table('country_language')->insert([
            'country_id' => 1,
            'language_id' => 2,
            'name' => "KSA"
        ]);

        DB::table('country_language')->insert([
            'country_id' => 2,
            'language_id' => 1,
            'name' => "مصر"
        ]);

        DB::table('country_language')->insert([
            'country_id' => 2,
            'language_id' => 2,
            'name' => "EGYPT"
        ]);


    }
}
