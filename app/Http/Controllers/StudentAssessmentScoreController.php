<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\Assessment;
use App\Models\StudentAssessmentScore;
use App\Models\Student;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class StudentAssessmentScoreController extends Controller
{
    /**
     * جلب قائمة الطلاب والدرجات المرصودة لهم لتقييم معين.
     * GET /api/assessments/{assessment}/scores
     */
    public function index(Assessment $assessment)
    {
        // 1. جلب جميع الطلاب المسجلين في المقرر الدراسي الخاص بهذا التقييم.
        // نفترض أن الطلاب مسجلون في الأقسام (sections) أو الصفوف (classes)
        $courseOffering = $assessment->courseOffering;

        // جلب الطلاب بناءً على القسم أو الصف
        $studentQuery = Student::query();
        if ($courseOffering->section_id) {
            $studentQuery->where('section_id', $courseOffering->section_id);
        } else {
            $studentQuery->where('class_id', $courseOffering->class_id);
        }
        
        // التأكد من أن الطلاب في نفس السنة الدراسية
        $students = $studentQuery->where('study_year_id', $courseOffering->study_year_id)
                                ->with('user:id,name') // جلب البيانات الأساسية للطالب
                                ->orderBy('name')
                                ->get(['id', 'name', 'user_id']);

        // 2. جلب الدرجات المرصودة حاليًا لهذا التقييم
        $scores = StudentAssessmentScore::where('assessment_id', $assessment->id)
            ->get()
            ->keyBy('student_id'); // لتحويلها إلى مصفوفة يسهل البحث فيها

        // 3. دمج قائمة الطلاب مع الدرجات
        $results = $students->map(function ($student) use ($scores, $assessment) {
            $scoreData = $scores->get($student->id);
            return [
                'student_id' => $student->id,
                'student_name' => $student->name,
                'score_obtained' => $scoreData ? $scoreData->score_obtained : null,
                'status' => $scoreData ? $scoreData->status : 'Pending Submission',
                'teacher_remarks' => $scoreData ? $scoreData->teacher_remarks : null,
                'max_score' => $assessment->max_score, // إرسال الدرجة القصوى مع كل طالب للمساعدة في الواجهة
            ];
        });

        return response()->json($results);
    }

    /**
     * حفظ أو تحديث مجموعة من درجات الطلاب لتقييم معين دفعة واحدة.
     * POST /api/assessments/{assessment}/scores
     */
    public function store(Request $request, Assessment $assessment)
    {
        $validated = $request->validate([
            'scores' => 'required|array',
            'scores.*.student_id' => 'required|exists:students,id',
            'scores.*.score_obtained' => ['nullable', 'numeric', 'min:0', 'max:' . $assessment->max_score],
            'scores.*.teacher_remarks' => 'nullable|string',
            'scores.*.status' => 'sometimes|string',
        ]);

        $teacherId = Auth::id();

        DB::transaction(function () use ($validated, $assessment, $teacherId) {
            foreach ($validated['scores'] as $scoreData) {
                // استخدم updateOrCreate لتحديث السجل إذا كان موجودًا أو إنشائه إذا لم يكن كذلك
                StudentAssessmentScore::updateOrCreate(
                    [
                        'assessment_id' => $assessment->id,
                        'student_id' => $scoreData['student_id'],
                    ],
                    [
                        'score_obtained' => $scoreData['score_obtained'],
                        'teacher_remarks' => $scoreData['teacher_remarks'] ?? null,
                        'status' => $scoreData['score_obtained'] !== null ? 'Graded' : ($scoreData['status'] ?? 'Pending Submission'),
                        'graded_by_teacher_id' => $teacherId,
                        'grading_timestamp' => now(),
                    ]
                );
            }
        });

        return response()->json(['message' => 'Scores have been saved successfully.']);
    }
}
