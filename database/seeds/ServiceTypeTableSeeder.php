<?php

use Illuminate\Database\Seeder;
use Illuminate\Database\Eloquent\Model;

class ServiceTypeTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        //
        DB::table('service_types')->insert([
            'name' => "توصيل فوري",
            'status' => "1",
        ]);

        DB::table('service_types')->insert([
            'name' => "توصيل اقتصادي",
            'status' => "1",
        ]);

        DB::table('service_types')->insert([
            'name' => "شحن",
            'status' => "1",
        ]);

        DB::table('service_types')->insert([
            'name' => "تأمين",
            'status' => "1",
            'type' => 2
        ]);

        DB::table('service_types')->insert([
            'name' => "تغليف",
            'status' => "1",
            'type' => 2
        ]);
        // Seeding Service Types Translation
        DB::table('service_type_language')->insert([
            'service_type_id' => 1,
            'language_id' => 1,
            'name' => "توصيل فوري"
        ]);

        DB::table('service_type_language')->insert([
            'service_type_id' => 1,
            'language_id' => 2,
            'name' => "Instant Delivery"
        ]);

        DB::table('service_type_language')->insert([
            'service_type_id' => 2,
            'language_id' => 1,
            'name' => "توصيل اقتصادي"
        ]);

        DB::table('service_type_language')->insert([
            'service_type_id' => 2,
            'language_id' => 2,
            'name' => "Economic Delivery"
        ]);

        DB::table('service_type_language')->insert([
            'service_type_id' => 3,
            'language_id' => 1,
            'name' => "شحن"
        ]);

        DB::table('service_type_language')->insert([
            'service_type_id' => 3,
            'language_id' => 2,
            'name' => "Fright"
        ]);

        DB::table('service_type_language')->insert([
            'service_type_id' => 4,
            'language_id' => 1,
            'name' => "تأمين"
        ]);

        DB::table('service_type_language')->insert([
            'service_type_id' => 4,
            'language_id' => 2,
            'name' => "Secure"
        ]);

        DB::table('service_type_language')->insert([
            'service_type_id' => 5,
            'language_id' => 1,
            'name' => "تغليف"
        ]);

        DB::table('service_type_language')->insert([
            'service_type_id' => 5,
            'language_id' => 2,
            'name' => "Gifting"
        ]);

        // Seeding Service types prices
        DB::table('services_types_price')->insert([
            'service_type_id' => 1,
            'country_id' => 1,
            'price' => "8.00"
        ]);

        DB::table('services_types_price')->insert([
            'service_type_id' => 1,
            'country_id' => 2,
            'price' => "15.00"
        ]);

        DB::table('services_types_price')->insert([
            'service_type_id' => 2,
            'country_id' => 1,
            'price' => "9.00"
        ]);

        DB::table('services_types_price')->insert([
            'service_type_id' => 2,
            'country_id' => 2,
            'price' => "16.00"
        ]);

        DB::table('services_types_price')->insert([
            'service_type_id' => 3,
            'country_id' => 1,
            'price' => "9.00"
        ]);

        DB::table('services_types_price')->insert([
            'service_type_id' => 3,
            'country_id' => 2,
            'price' => "17.00"
        ]);

        DB::table('services_types_price')->insert([
            'service_type_id' => 3,
            'country_id' => 1,
            'price' => "2.00"
        ]);

        DB::table('services_types_price')->insert([
            'service_type_id' => 3,
            'country_id' => 2,
            'price' => "10.00"
        ]);

        DB::table('services_types_price')->insert([
            'service_type_id' => 4,
            'country_id' => 1,
            'price' => "3.00"
        ]);

        DB::table('services_types_price')->insert([
            'service_type_id' => 4,
            'country_id' => 2,
            'price' => "7.00"
        ]);

        DB::table('services_types_price')->insert([
            'service_type_id' => 5,
            'country_id' => 1,
            'price' => "3.00"
        ]);

        DB::table('services_types_price')->insert([
            'service_type_id' => 5,
            'country_id' => 2,
            'price' => "7.00"
        ]);

    }
}
