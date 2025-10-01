<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class EmployeesTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run()
    {
        DB::table('employees')->insert([
            [
                'user_id' => 1,
                'department_id' => 1,
                'teacher_type_id' => 1,
                'national_number' => str(10),
                'phone_number' => '1234567890',
                'phone_number_two' => '0987654321',
                'address' => '123 Main Street',
                'photos' => json_encode(['photo1.jpg', 'photo2.jpg']),
                'years_of_experience' => 5,
                'is_teacher' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            // Add more initial employees as needed
        ]);
    }
}
