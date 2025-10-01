<?php
namespace App\Http\Controllers;

use App\Models\CourseOffering;
use App\Models\Student;
use App\Models\StudentAssessmentScore;

class GradesController extends Controller
{
    public function getGradesForCourse(CourseOffering $courseOffering)
    {
        // 1. جلب كل التقييمات المتعلقة بهذا المقرر
        $assessments = $courseOffering->assessments()->get(['id', 'title', 'max_score']);
        
        // 2. جلب كل الطلاب في شعبة هذا المقرر
        $students = Student::where('section_id', $courseOffering->section_id)->get(['id', 'name']);

        // 3. جلب كل الدرجات المرصودة لهؤلاء الطلاب في هذه التقييمات
        $scores = StudentAssessmentScore::whereIn('student_id', $students->pluck('id'))
            ->whereIn('assessment_id', $assessments->pluck('id'))
            ->get(['student_id', 'assessment_id', 'score_obtained']);

        // 4. تنظيم الدرجات في هيكل سهل الاستخدام للواجهة
        $studentScores = $students->map(function ($student) use ($scores) {
            $student->scores = $scores->where('student_id', $student->id)
                                      ->keyBy('assessment_id')
                                      ->map(function ($score) {
                                          return $score->score_obtained;
                                      });
            return $student;
        });

        return response()->json([
            'assessments' => $assessments,
            'students' => $studentScores,
        ]);
    }
}