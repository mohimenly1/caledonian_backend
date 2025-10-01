<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class TeacherTypesSeeder extends Seeder
{
    public function run()
    {
        $subjects = [
            'Mathematics',
            'English',
            'Science',
            'History',
            'Geography',
            'Physical Education',
            'Art',
            'Music',
            'Foreign Languages',
            'Computer Science',
            'Economics',
            'Business Studies',
        ];

        foreach ($subjects as $subject) {
            DB::table('teachers_type')->insert([
                'type' => $subject,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }
}