<?php

namespace App\Http\Controllers;

use App\Models\Student;
use App\Models\Subject;
use App\Models\Grade;
use App\Models\User;
use App\Models\ClassSectionSubject;
use App\Models\Exam;
use App\Models\Term;
use App\Models\GradingScale;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class StudentSubjectController extends Controller
{

    
    public function getSubjectDetails($subjectId)
    {
        $student = Auth::user()->student;
    
        $subject = Subject::findOrFail($subjectId);
        
        // Verify student is enrolled in this subject
        $this->verifyStudentSubject($student, $subjectId);
        
        return response()->json([
            'success' => true,
            'subject' => $subject,
            'teachers' => $this->getSubjectTeacher($student->class_id, $subjectId),
            'class_average' => $this->getClassAverage($student->class_id, $subjectId),
        ]);
    }

    public function getSubjectGrades($subjectId)
    {
        $student = Auth::user()->student;
        $this->verifyStudentSubject($student, $subjectId);
        
        $grades = Grade::where('student_id', $student->id)
            ->where('subject_id', $subjectId)
            ->with(['term', 'exam'])
            ->get()
            ->groupBy('term_id');
            
        $terms = Term::whereIn('id', $grades->keys())->get();
        
        return response()->json([
            'success' => true,
            'terms' => $terms,
            'grades' => $grades,
            'grading_scales' => GradingScale::all(),
        ]);
    }

    public function getSubjectProgress($subjectId)
    {
        $student = Auth::user()->student;
        $this->verifyStudentSubject($student, $subjectId);
        
        $grades = Grade::where('student_id', $student->id)
            ->where('subject_id', $subjectId)
            ->with(['term', 'exam'])
            ->get();
            
        $classAverage = $this->getClassAverage($student->class_id, $subjectId);
        $studentAverage = $grades->avg('score');
        
        return response()->json([
            'success' => true,
            'student_average' => $studentAverage,
            'class_average' => $classAverage,
            'progress_percentage' => $this->calculateProgressPercentage($studentAverage, $classAverage),
            'grade_trend' => $this->getGradeTrend($grades),
        ]);
    }

    public function getSubjectEvaluations($subjectId)
    {
        $student = Auth::user()->student;
        $this->verifyStudentSubject($student, $subjectId);
        
        // Get both manual evaluations and grade-based evaluations
        $evaluations = Grade::where('student_id', $student->id)
            ->where('subject_id', $subjectId)
            ->whereNotNull('remarks') // Manual evaluations have remarks
            ->with(['term', 'exam', 'teacher'])
            ->get();
            
        return response()->json([
            'success' => true,
            'evaluations' => $evaluations,
        ]);
    }

    public function getSubjectExams($subjectId)
    {
        $student = Auth::user()->student;
        $this->verifyStudentSubject($student, $subjectId);
        
        $exams = Exam::where('class_id', $student->class_id)
            ->where('subject_id', $subjectId)
            ->with(['term', 'type', 'grades' => function($q) use ($student) {
                $q->where('student_id', $student->id);
            }])
            ->get();
            
        return response()->json([
            'success' => true,
            'exams' => $exams,
        ]);
    }

    // Helper methods
    private function verifyStudentSubject($student, $subjectId)
    {
        // Check if student is enrolled in this subject
        $enrolled = ClassSectionSubject::where('class_id', $student->class_id)
            ->when($student->section_id, function($q) use ($student) {
                $q->where('section_id', $student->section_id);
            })
            ->where('subject_id', $subjectId)
            ->exists();
            
        if (!$enrolled) {
            abort(403, 'You are not enrolled in this subject');
        }
    }

    private function getSubjectTeacher($classId, $subjectId)
    {
        $student = Auth::user()->student;
        
        return User::whereHas('teacherSubjects', function($query) use ($subjectId, $classId, $student) {
                $query->where('subject_id', $subjectId)
                      ->where('class_id', $classId)
                      ->where('section_id', $student->section_id);
            })
            ->with(['teacherSubjects' => function($query) use ($subjectId, $classId, $student) {
                $query->where('subject_id', $subjectId)
                      ->where('class_id', $classId)
                      ->where('section_id', $student->section_id);
            }])
            ->get()
            ->makeHidden(['password', 'remember_token']);
    }

    private function getClassAverage($classId, $subjectId)
    {
        return Grade::whereHas('student', function($q) use ($classId) {
                $q->where('class_id', $classId);
            })
            ->where('subject_id', $subjectId)
            ->avg('score');
    }

    private function calculateProgressPercentage($studentAvg, $classAvg)
    {
        if ($classAvg == 0) return 100; // Avoid division by zero
        
        return min(100, max(0, ($studentAvg / $classAvg) * 100));
    }

    private function getGradeTrend($grades)
    {
        // Simple implementation - returns 'up', 'down', or 'stable'
        if ($grades->count() < 2) return 'stable';
        
        $lastTwo = $grades->sortByDesc('created_at')->take(2);
        $first = $lastTwo->first()->score;
        $second = $lastTwo->last()->score;
        
        return $first > $second ? 'up' : ($first < $second ? 'down' : 'stable');
    }
}