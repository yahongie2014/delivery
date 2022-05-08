<?php

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $this->call(LanguageTableSeeder::class);
        $this->call(CountriesTableSeeder::class);
        $this->call(ServiceTypeTableSeeder::class);
        $this->call(PaymentTypeTableSeeder::class);
        $this->call(CategoryTableSeeder::class);
    }
}
