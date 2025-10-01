<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class SubjectsTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        DB::table('subjects')->insert([
            ['name' => 'Mathematics', 'code' => 'MATH101', 'description' => 'Basic Mathematics'],
            ['name' => 'Science', 'code' => 'SCI101', 'description' => 'Basic Science'],
            ['name' => 'History', 'code' => 'HIST101', 'description' => 'World History'],
            ['name' => 'Geography', 'code' => 'GEO101', 'description' => 'Geography Fundamentals'],
            ['name' => 'English', 'code' => 'ENG101', 'description' => 'English Literature'],
        ]);
    }
}
