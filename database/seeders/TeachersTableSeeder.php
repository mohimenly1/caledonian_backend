<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class TeachersTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        DB::table('teachers')->insert([
            ['user_id' => 1, 'subject_id' => 1, 'qualifications' => 'M.Sc Mathematics', 'years_of_experience' => '5'],
            ['user_id' => 2, 'subject_id' => 2, 'qualifications' => 'B.Sc Science', 'years_of_experience' => '3'],
            ['user_id' => 3, 'subject_id' => 3, 'qualifications' => 'M.A History', 'years_of_experience' => '4'],
            ['user_id' => 4, 'subject_id' => 4, 'qualifications' => 'B.A Geography', 'years_of_experience' => '2'],
            ['user_id' => 5, 'subject_id' => 5, 'qualifications' => 'M.A English', 'years_of_experience' => '6'],
        ]);
    }
}
