<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\Student;
use App\Models\Assessment;
use Illuminate\Http\Request;

class StudentAcademicController extends Controller
{
    /**
     * Get all visible assessments and scores for a specific student.
     * GET /api/students/{student}/assessments
     */
    public function getStudentAssessments(Student $student)
    {
        // Find all assessments for the student's class and section that are visible
        $assessments = Assessment::where('is_visible_to_students', true)
            ->whereHas('courseOffering', function ($query) use ($student) {
                $query->where('class_id', $student->class_id)
                      ->where('section_id', $student->section_id);
            })
            ->with(['courseOffering.subject:id,name', 'studentScores' => function ($query) use ($student) {
                // Eager load only the score for the specific student
                $query->where('student_id', $student->id);
            }])
            ->get();

        // Format the data for the app
        $formattedAssessments = $assessments->map(function ($assessment) {
            $score = $assessment->studentScores->first();
            return [
                'id' => $assessment->id,
                'title' => $assessment->title,
                'subject_name' => optional($assessment->courseOffering->subject)->name,
                'max_score' => $assessment->max_score,
                'score_obtained' => $score ? $score->score_obtained : null,
                'is_graded' => $score && $score->score_obtained !== null,
            ];
        });

        return response()->json([
            'success' => true,
            'assessments' => $formattedAssessments,
        ]);
    }
}
