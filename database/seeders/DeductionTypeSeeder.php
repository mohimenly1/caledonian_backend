<?php

namespace Database\Seeders;

use App\Models\DeductionType;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DeductionTypeSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        DeductionType::insert([
            ['name' => 'Absence'],
            ['name' => 'Fine'],
            ['name' => 'Late Arrival'],
            ['name' => 'Other']
        ]);
    }
}
