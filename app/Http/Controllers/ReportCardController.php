<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\ClassRoom;
use App\Models\Student;
use App\Models\Term;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use App\Models\GradingScale;
use App\Models\GradingPolicy;

class ReportCardController extends Controller
{
    /**
     * تجميع كل البيانات اللازمة لعرضها في الواجهة الأمامية (قبل الطباعة).
     * POST /api/report-cards/generate-data
     */
    // public function generateData(Request $request)
    // {
    //     $validated = $request->validate([
    //         'class_id' => 'required|exists:classes,id',
    //         'term_id' => 'required|exists:terms,id',
    //     ]);
        
    //     $reportData = $this->getReportData($validated['class_id'], $validated['term_id']);
        
    //     if ($reportData->isEmpty()) {
    //         return response()->json(['message' => 'No students found for this class.'], 404);
    //     }

    //     return response()->json($reportData);
    // }
    

    public function generateData(Request $request)
    {
        $validated = $request->validate([
            'class_id' => 'required|exists:classes,id',
            'term_id' => 'required|exists:terms,id',
        ]);
        
        $reportData = $this->getReportData($validated['class_id'], $validated['term_id']);
        
        if ($reportData->isEmpty()) {
            return response()->json(['message' => 'No students found for this class.'], 404);
        }

        $class = ClassRoom::find($validated['class_id']);
        $studyYearId = $class->study_year_id;

        // جلب البيانات الإضافية
        $gradingScales = GradingScale::where('study_year_id', $studyYearId)->orderBy('rank_order', 'asc')->get();
        $gradingPolicy = GradingPolicy::where('study_year_id', $studyYearId)
                          
                                      ->with('components.assessmentType:id,name')
                                      ->first();

        return response()->json([
            'students_reports' => $reportData,
            'grading_scales' => $gradingScales,
            'grading_policy' => $gradingPolicy,
        ]);
    }
    /**
     * دالة مساعدة لتجميع البيانات من قاعدة البيانات.
     */
    private function getReportData($classId, $termId): Collection
    {
        $class = ClassRoom::find($classId);
        $term = Term::find($termId);
        $students = Student::where('class_id', $class->id)
                           ->where('study_year_id', $class->study_year_id)
                           ->orderBy('name')->get();
        
        return $students->map(function ($student) use ($term) {
            
            $finalGrades = $student->termSubjectGrades()
                ->where('term_id', $term->id)
                ->with(['courseOffering.subject:id,name', 'gradingScaleEntry:id,grade_label'])
                ->get();
            
            $reportComments = $student->reportComments()
                ->where('term_id', $term->id)
                ->with('commentedByUser:id,name')
                ->get();
            
            $evaluations = $student->evaluations()
                ->where('term_id', $term->id)
                ->with('evaluationItem.evaluationArea')
                ->get()
                ->groupBy('evaluationItem.evaluationArea.name');

            $attendanceQuery = $student->student_attendance_records()->whereBetween('created_at', [$term->start_date, $term->end_date]);
            $attendanceSummary = [
                'absent_days' => (clone $attendanceQuery)->where('record_state', 'absent')->count(),
                'late_days' => (clone $attendanceQuery)->where('record_state', 'late')->count(),
            ];

            return [
                'student_info' => [
                    'id' => $student->id, 'name' => $student->name, 'class_name' => $student->class->name,
                ],
                'term_info' => [
                    'name' => $term->name, 'study_year' => $term->studyYear->name,
                ],
                'grades' => $finalGrades->toArray(),
                'comments' => $reportComments->toArray(),
                'evaluations' => $evaluations->toArray(),
                'attendance' => $attendanceSummary,
            ];
        });
    }
}
