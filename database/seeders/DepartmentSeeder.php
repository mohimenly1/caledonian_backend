<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class DepartmentSeeder extends Seeder
{
    public function run()
    {
        DB::table('departments')->insert([
            ['name' => 'Administrative Department'],
            ['name' => 'Finance Department'],
            ['name' => 'Study and Examinations Department'],
            ['name' => 'Director\'s Department'],
            ['name' => 'Information Technology Department'],
            ['name' => 'Inquiry and Registration Department'],
        ]);
    }
}