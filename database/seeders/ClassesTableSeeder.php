<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class ClassesTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        DB::table('classes')->insert([
            ['name' => 'Class 1', 'description' => 'First Grade'],
            ['name' => 'Class 2', 'description' => 'Second Grade'],
            ['name' => 'Class 3', 'description' => 'Third Grade'],
            ['name' => 'Class 4', 'description' => 'Fourth Grade'],
            ['name' => 'Class 5', 'description' => 'Fifth Grade'],
        ]);
    }
}
