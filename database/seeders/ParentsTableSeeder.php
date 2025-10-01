<?php

namespace Database\Seeders;
use Illuminate\Support\Facades\DB;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class ParentsTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        DB::table('parents')->insert([
            [
                'user_id' => 5,
                'first_name' => 'John',
                'last_name' => 'Doe',
                'phone_number_one' => '123-456-7890',
                'phone_number_two' => '098-765-4321',
                'city' => 'New York',
                'address' => '123 Main St',
                'id_image' => 'id_images/john_doe_id.jpg',
                'passport_image' => 'passport_images/john_doe_passport.jpg',
                'images_info' => json_encode([
                    'profile_image' => 'images/john_doe_profile.jpg',
                    'family_image' => 'images/john_doe_family.jpg',
                ]),
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ]);
    }
}
