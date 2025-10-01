<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class UsersTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {

        // $users = [
        //     [
        //         'name' => 'Admin User',
        //         'phone' => '1234567890',
        //         'username' => 'adminuser',
        //         'email' => 'admin@example.com',
        //         'user_type' => 'admin',
        //         'address' => '123 Admin St',
        //         'password' => Hash::make('password'),
        //     ],
        //     [
        //         'name' => 'Teacher User',
        //         'phone' => '2345678901',
        //         'username' => 'teacheruser',
        //         'email' => 'teacher@example.com',
        //         'user_type' => 'teacher',
        //         'address' => '123 Teacher St',
        //         'password' => Hash::make('password'),
        //     ],
        //     [
        //         'name' => 'Staff User',
        //         'phone' => '3456789012',
        //         'username' => 'staffuser',
        //         'email' => 'staff@example.com',
        //         'user_type' => 'staff',
        //         'address' => '123 Staff St',
        //         'password' => Hash::make('password'),
        //     ],
        //     [
        //         'name' => 'Student User',
        //         'phone' => '4567890123',
        //         'username' => 'studentuser',
        //         'email' => 'student@example.com',
        //         'user_type' => 'student',
        //         'address' => '123 Student St',
        //         'password' => Hash::make('password'),
        //     ],
        //     [
        //         'name' => 'Parent User',
        //         'phone' => '5678901234',
        //         'username' => 'parentuser',
        //         'email' => 'parent@example.com',
        //         'user_type' => 'parent',
        //         'address' => '123 Parent St',
        //         'password' => Hash::make('password'),
        //     ],
        //     [
        //         'name' => 'Driver User',
        //         'phone' => '6789012345',
        //         'username' => 'driveruser',
        //         'email' => 'driver@example.com',
        //         'user_type' => 'driver',
        //         'address' => '123 Driver St',
        //         'password' => Hash::make('password'),
        //     ],
        // ];

        $users = [
            [
                // 'name' => 'tasnim',
                // 'phone' => '2222122122',
                // 'username' => 'tasnim',
                // 'email' => 'tasnim@example.com',
                // 'user_type' => 'doctor',
                // 'address' => '123 Admin St',
                // 'password' => Hash::make('123@@123'),



                // 'name' => 'alhareth',
                // 'phone' => '22313232',
                // 'username' => 'alhareth',
                // 'email' => 'alhareth@example.com',
                // 'user_type' => 'financial',
                // 'address' => '123 Admin St',
                // 'password' => Hash::make('321@@321'),


                // 'name' => 'lujain',
                // 'phone' => '12231231',
                // 'username' => 'lujain',
                // 'email' => 'lujain@example.com',
                // 'user_type' => 'admin',
                // 'address' => '123 Admin St',
                // 'password' => Hash::make('543@@543'),


                // 'name' => 'sajeda',
                // 'phone' => '1223222231',
                // 'username' => 'sajeda',
                // 'email' => 'sajeda@example.com',
                // 'user_type' => 'admin',
                // 'address' => '123 Admin St',
                // 'password' => Hash::make('545@@545'),


                // 'name' => 'hamam',
                // 'phone' => '1533222231',
                // 'username' => 'hamam',
                // 'email' => 'hamam@example.com',
                // 'user_type' => 'admin',
                // 'address' => '123 Admin St',
                // 'password' => Hash::make('hamam353'),
                // 'name' => 'prin',
                // 'phone' => '1532231',
                // 'username' => 'prin',
                // 'email' => 'prin@example.com',
                // 'user_type' => 'prin',
                // 'address' => '123 Admin St',
                // 'password' => Hash::make('cis_cis_cis'),
                // 'name' => 'hr_narges',
                // 'phone' => '211213',
                // 'username' => 'hr_narges',
                // 'email' => 'hr_narges@example.com',
                // 'user_type' => 'hr',
                // 'address' => '123 Admin St',
                // 'password' => Hash::make('c_hr_ly_20'),

                // 'name' => 'maha_student_m',
                // 'phone' => '21121323233',
                // 'username' => 'student_m',
                // 'email' => 'student_m@example.com',
                // 'user_type' => 'student_m',
                // 'address' => '123 Admin St',
                // 'password' => Hash::make('cis_2023_cds'),

                'name' => 'aliali',
                'phone' => '0924562933',
                'username' => 'aliali',
                'email' => 'ali@exampale.com',
                'user_type' => 'moder',
                'address' => '123 Admin St',
                'password' => Hash::make('123456789'),
            ]
        ];

        // Insert user data into the users table
        DB::table('users')->insert($users);
    }
}
