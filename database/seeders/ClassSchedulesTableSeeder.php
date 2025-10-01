<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class ClassSchedulesTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        DB::table('class_schedules')->insert([
            ['class_id' => 1, 'subject_id' => 1, 'teacher_id' => 1, 'day_of_week' => 'Monday', 'start_time' => '08:00:00', 'end_time' => '09:00:00'],
            ['class_id' => 1, 'subject_id' => 2, 'teacher_id' => 2, 'day_of_week' => 'Monday', 'start_time' => '09:00:00', 'end_time' => '10:00:00'],
            ['class_id' => 2, 'subject_id' => 3, 'teacher_id' => 3, 'day_of_week' => 'Tuesday', 'start_time' => '10:00:00', 'end_time' => '11:00:00'],
            ['class_id' => 2, 'subject_id' => 4, 'teacher_id' => 4, 'day_of_week' => 'Wednesday', 'start_time' => '11:00:00', 'end_time' => '12:00:00'],
            ['class_id' => 3, 'subject_id' => 5, 'teacher_id' => 5, 'day_of_week' => 'Thursday', 'start_time' => '12:00:00', 'end_time' => '13:00:00'],
        ]);
    }
}
