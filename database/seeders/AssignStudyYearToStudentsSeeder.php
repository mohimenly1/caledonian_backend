<?php

namespace Database\Seeders;
use App\Models\StudyYear;
use App\Models\Student;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class AssignStudyYearToStudentsSeeder extends Seeder
{
    public function run()
    {
        // Add the current academic year
        $studyYear = StudyYear::firstOrCreate([
            'year_study' => '2024-2025',
        ]);

        // Assign the study year to all existing students
        Student::query()->update(['study_year_id' => $studyYear->id]);
    }
}
