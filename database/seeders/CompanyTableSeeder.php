<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class CompanyTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        DB::table('companies')->insert([
            'name' => 'Example Company',
            'address' => '123 Example Street',
            'phone_number' => '1234567890',
            'email' => 'info@example.com',
            'instagram' => 'example_instagram',
            'facebook' => 'example_facebook',
            'twitter' => 'example_twitter',
            'user_id' => 1,
            'image_url'=>'company/default.jpg',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }
}
