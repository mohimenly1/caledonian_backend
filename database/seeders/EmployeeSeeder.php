<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Employee;
use App\Models\User;

class EmployeeSeeder extends Seeder
{
    public function run()
    {
        // Assuming you have users seeded
        $user1 = User::find(1);
        $user2 = User::find(2);

        // Teacher Employee
        Employee::create([
            'pin' =>1225,
            'name' => 'ooo',
            'department_id' => 1,
            'teacher_type_id' => 1,
            'national_number' => '123456789',
            'phone_number' => '1234567890',
            'phone_number_two' => '0987654321',
            'address' => 'Teacher Address',
            'years_of_experience' => 5,
        ]);

        // Regular Employee
        Employee::create([
            'pin' =>1245,
            'name' => 'wwww',
            'department_id' => 2,
            'teacher_type_id' => null,
            'national_number' => '987654321',
            'phone_number' => '1234567890',
            'phone_number_two' => '0987654321',
            'address' => 'Employee Address',
            'years_of_experience' => 10,
        ]);
    }
}
