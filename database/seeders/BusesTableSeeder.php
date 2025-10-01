<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class BusesTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $bus = [
            'number' => 'BUS001',
            'model' => 'Model X',
            'capacity' => 50,
            'created_at' => now(),
            'updated_at' => now(),
        ];

        // Insert bus data into the buses table
        DB::table('buses')->insert($bus);
    }
}
