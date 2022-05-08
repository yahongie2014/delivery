<?php

use Illuminate\Database\Seeder;

class PaymentTypeTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        // add payment types
        DB::table('payment_types')->insert([
            'name' => "مدفوع مسبقًا",
            'status' => "1",
        ]);

        DB::table('payment_types')->insert([
            'name' => "دفع عن الاستلام",
            'status' => "1",
        ]);


        // add payment type localization

        DB::table('payment_type_language')->insert([
            'payment_type_id' => 1,
            'language_id' => 1,
            'name' => "مدفوع مسبقًا"
        ]);

        DB::table('payment_type_language')->insert([
            'payment_type_id' => 1,
            'language_id' => 2,
            'name' => "Prepaid"
        ]);

        DB::table('payment_type_language')->insert([
            'payment_type_id' => 2,
            'language_id' => 1,
            'name' => "دفع عن الاستلام"
        ]);

        DB::table('payment_type_language')->insert([
            'payment_type_id' => 2,
            'language_id' => 2,
            'name' => "On site payment"
        ]);

        // add payment type pricing

        DB::table('payment_types_prices')->insert([
            'payment_type_id' => 1,
            'country_id' => 1,
            'price' => "0.00"
        ]);

        DB::table('payment_types_prices')->insert([
            'payment_type_id' => 1,
            'country_id' => 2,
            'price' => "1.00"
        ]);

        DB::table('payment_types_prices')->insert([
            'payment_type_id' => 2,
            'country_id' => 1,
            'price' => "2.00"
        ]);

        DB::table('payment_types_prices')->insert([
            'payment_type_id' => 2,
            'country_id' => 2,
            'price' => "3.00"
        ]);
    }
}
