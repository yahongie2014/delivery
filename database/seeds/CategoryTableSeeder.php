<?php

use Illuminate\Database\Seeder;

class CategoryTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        //
        DB::table('categories')->insert([
            'name' => "اكل",
            'status' => '1',
        ]);

        DB::table('categories')->insert([
            'name' => "ملابس",
            'status' => '1',
        ]);

        DB::table('category_language')->insert([
            'category_id' => 1,
            'language_id' => 1,
            'name' => "اكل"
        ]);

        DB::table('category_language')->insert([
            'category_id' => 1,
            'language_id' => 2,
            'name' => "Food"
        ]);

        DB::table('category_language')->insert([
            'category_id' => 2,
            'language_id' => 1,
            'name' => "ملابس"
        ]);

        DB::table('category_language')->insert([
            'category_id' => 2,
            'language_id' => 2,
            'name' => "Clothes"
        ]);

    }
}
