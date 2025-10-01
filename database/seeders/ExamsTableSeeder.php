<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class ExamsTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        DB::table('exams')->insert([
            ['subject_id' => 1, 'date' => '2024-07-01', 'start_time' => '09:00:00', 'end_time' => '12:00:00', 'max_marks' => 100],
            ['subject_id' => 2, 'date' => '2024-07-02', 'start_time' => '09:00:00', 'end_time' => '12:00:00', 'max_marks' => 100],
            ['subject_id' => 3, 'date' => '2024-07-03', 'start_time' => '09:00:00', 'end_time' => '12:00:00', 'max_marks' => 100],
            ['subject_id' => 4, 'date' => '2024-07-04', 'start_time' => '09:00:00', 'end_time' => '12:00:00', 'max_marks' => 100],
            ['subject_id' => 5, 'date' => '2024-07-05', 'start_time' => '09:00:00', 'end_time' => '12:00:00', 'max_marks' => 100],
        ]);
    }
}
