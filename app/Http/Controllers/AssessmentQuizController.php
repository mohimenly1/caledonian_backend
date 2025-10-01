<?php

namespace App\Http\Controllers;

use App\Models\Assessment;
use App\Models\Student;
use App\Models\StudentAssessmentScore;
use App\Models\StudentQuizAnswer;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule; // ✨ لا تنسَ إضافة هذا السطر
use App\Models\CourseOffering;
use App\Models\QuizQuestion;
use Illuminate\Support\Facades\DB;

class AssessmentQuizController extends Controller
{
    public function submitStudentAnswers(Request $request, Assessment $assessment)
    {
        $validated = $request->validate([
            'student_id' => 'required|exists:students,id',
            'answers' => 'required|array',
            'answers.*.question_id' => 'required|exists:quiz_questions,id',
            'answers.*.chosen_option_id' => 'nullable|exists:quiz_question_options,id',
            'answers.*.answer_text' => 'nullable|string',
        ]);

        $studentId = $validated['student_id'];
        
        $existingSubmission = StudentAssessmentScore::where('assessment_id', $assessment->id)
            ->where('student_id', $studentId)
            ->first();

        if ($existingSubmission) {
            return response()->json(['message' => 'You have already submitted this assessment.'], 409); // 409 Conflict
        }

        $totalScore = 0;

        DB::transaction(function () use ($assessment, $studentId, $validated, &$totalScore) {
            // 1. إنشاء سجل التسليم الرئيسي للطالب
            $submission = StudentAssessmentScore::create([
                'assessment_id' => $assessment->id,
                'student_id' => $studentId,
                'status' => 'Submitted',
                'submission_timestamp' => now(),
                'score_obtained' => 0, // قيمة أولية
            ]);

            // ✨ 2. تجميع الإجابات حسب كل سؤال (مهم جداً للتعامل مع الاختيارات المتعددة) ✨
            $answersByQuestion = collect($validated['answers'])->groupBy('question_id');
            $questions = QuizQuestion::with('options')->find($answersByQuestion->keys())->keyBy('id');

            // 3. المرور على كل سؤال قام الطالب بالإجابة عليه
            foreach ($answersByQuestion as $questionId => $studentAnswers) {
                $question = $questions->get($questionId);
                if (!$question) continue;

                $pointsAwarded = 0;
                $isCorrect = false;
                $questionType = $question->question_type;

                // --- ✨ 4. منطق التصحيح التلقائي المكتمل ✨ ---

                // الحالة الأولى: اختيار من متعدد (إجابة واحدة) أو صح/خطأ
                if (($questionType === 'multiple_choice_single_answer' || $questionType === 'true_false')) {
                    $studentOptionId = $studentAnswers->first()['chosen_option_id'] ?? null;
                    $correctOption = $question->options->firstWhere('is_correct_answer', true);
                    if ($correctOption && $correctOption->id == $studentOptionId) {
                        $isCorrect = true;
                        $pointsAwarded = $question->points;
                    }
                }
                // الحالة الثانية: اختيار من متعدد (عدة إجابات)
                elseif ($questionType === 'multiple_choice_multiple_answers') {
                    $correctOptionIds = $question->options->where('is_correct_answer', true)->pluck('id')->sort()->values();
                    $studentOptionIds = $studentAnswers->pluck('chosen_option_id')->sort()->values();
                    
                    // يجب أن تكون إجابات الطالب مطابقة تماماً للإجابات الصحيحة
                    if ($correctOptionIds->count() > 0 && $correctOptionIds->all() == $studentOptionIds->all()) {
                        $isCorrect = true;
                        $pointsAwarded = $question->points;
                    }
                }
                // الحالة الثالثة: الأسئلة المقالية (لا تصحح تلقائياً)
                else {
                    $isCorrect = null; // تحتاج تصحيح يدوي
                }

                // 5. حفظ إجابات الطالب في قاعدة البيانات
                foreach($studentAnswers as $answerData) {
                     StudentQuizAnswer::create([
                        'student_assessment_score_id' => $submission->id,
                        'quiz_question_id' => $questionId,
                        'quiz_question_option_id' => $answerData['chosen_option_id'] ?? null,
                        'answer_text' => $answerData['answer_text'] ?? null,
                        'is_marked_correct' => $isCorrect,
                        'points_awarded' => 0, // يتم منح الدرجات المجمعة لاحقاً
                    ]);
                }
                
                $totalScore += $pointsAwarded;
            }

            // 6. تحديث الدرجة الإجمالية المحتسبة تلقائياً
            $submission->score_obtained = $totalScore;
            $submission->save();
        });

        return response()->json([
            'message' => 'Your answers have been submitted successfully.',
            'final_score' => $totalScore
        ], 201);
    }
    public function store(Request $request, Assessment $assessment)
    {
        $validatedData = $request->validate([
            'questions' => 'required|array',
            'questions.*.question_text' => 'required|string',
            'questions.*.points' => 'required|numeric|min:0',
            'questions.*.question_type' => ['required', 'string', Rule::in(['multiple_choice_single_answer', 'multiple_choice_multiple_answers', 'true_false', 'short_answer', 'essay'])],
            'questions.*.options' => 'sometimes|array',
            'questions.*.options.*.option_text' => 'required_with:questions.*.options|string',
            'questions.*.options.*.is_correct_answer' => 'required_with:questions.*.options|boolean',
            // ✨ إضافة حقل جديد للتحقق (اختياري)
            'questions.*.true_false_answer' => 'sometimes|boolean',
        ]);
    
        foreach ($validatedData['questions'] as $questionData) {
            $question = $assessment->quizQuestions()->create([
                'question_text' => $questionData['question_text'],
                'points' => $questionData['points'],
                'question_type' => $questionData['question_type'],
            ]);
    
            if (in_array($questionData['question_type'], ['multiple_choice_single_answer', 'multiple_choice_multiple_answers']) && !empty($questionData['options'])) {
                foreach ($questionData['options'] as $optionData) {
                    $question->options()->create([
                        'option_text' => $optionData['option_text'],
                        'is_correct_answer' => $optionData['is_correct_answer'],
                    ]);
                }
            } 
            // ✨ --- بداية التعديل الرئيسي هنا: استخدام الإجابة الصحيحة من الواجهة --- ✨
            elseif ($questionData['question_type'] === 'true_false') {
                // نأخذ القيمة القادمة من الواجهة، وإذا لم تكن موجودة نفترض أنها 'true'
                $correctAnswer = $questionData['true_false_answer'] ?? true;
                
                // إنشاء خيار "صح"
                $question->options()->create([
                    'option_text' => 'صح',
                    'is_correct_answer' => $correctAnswer === true,
                ]);
                // إنشاء خيار "خطأ"
                $question->options()->create([
                    'option_text' => 'خطأ',
                    'is_correct_answer' => $correctAnswer === false,
                ]);
            }
            // --- نهاية التعديل الرئيسي ---
        }
    
        return response()->json(['message' => 'Quiz questions saved successfully.'], 201);
    }

    public function getGradingSheet(Assessment $assessment)
    {
        $students = Student::where('section_id', $assessment->section_id)->select('id', 'name','srn')->get();
    
        // جلب سجلات الدرجات الموجودة بالكامل
        $scoreRecords = StudentAssessmentScore::where('assessment_id', $assessment->id)
                                              ->get()
                                              ->keyBy('student_id');
    
        $gradingSheet = $students->map(function ($student) use ($scoreRecords, $assessment) {
            $scoreRecord = $scoreRecords->get($student->id);
            
            // إرجاع كائن الطالب مع سجل الدرجة الخاص به
            return [
                'id' => $student->id,
                'name' => $student->name,
                'max_score' => $assessment->max_score,
                'score_record' => $scoreRecord ?? [ // إذا لم يوجد سجل، قم بإنشاء سجل افتراضي
                    'student_id' => $student->id,
                    'assessment_id' => $assessment->id,
                    'score_obtained' => null,
                    'status' => 'Not Submitted',
                ]
            ];
        });
    
        return response()->json($gradingSheet);
    }

    /**
     * حفظ أو تحديث درجات الطلاب لتقييم معين.
     */
    public function storeScores(Request $request, Assessment $assessment)
    {
        $validated = $request->validate([
            'scores' => 'required|array',
            'scores.*.student_id' => 'required|exists:students,id',
            'scores.*.score_obtained' => ['nullable', 'numeric', 'min:0', 'max:' . $assessment->max_score],
            'scores.*.status' => ['required', Rule::in([
                'Pending Submission',
                'Submitted',
                'Late Submission',
                'Graded',
                'Missing',
                'Resubmission Requested',
                'Excused',
                'Not Submitted' // قيمة افتراضية من الواجهة
            ])],
        ]);
    
        foreach ($validated['scores'] as $studentScore) {
            // لا تقم بحفظ أي شيء إذا لم تتغير الحالة عن "لم يسلم" ولم يتم إدخال درجة
            if ($studentScore['status'] === 'Not Submitted' && !isset($studentScore['score_obtained'])) {
                continue;
            }
    
            StudentAssessmentScore::updateOrCreate(
                [
                    'assessment_id' => $assessment->id,
                    'student_id' => $studentScore['student_id'],
                ],
                [
                    'score_obtained' => $studentScore['score_obtained'],
                    'status' => $studentScore['status'],
                    'graded_by_teacher_id' => auth()->id(),
                    'grading_timestamp' => now(),
                ]
            );
        }
    
        return response()->json(['message' => 'Grades saved successfully.']);
    }

    public function getSubmissions(Assessment $assessment)
    {
        $students = Student::where('section_id', $assessment->section_id)->select('id', 'name')->get();

        // جلب التسليمات الموجودة (سجلات student_assessment_scores)
        $submissions = StudentAssessmentScore::where('assessment_id', $assessment->id)
            ->with('student:id,name')
            ->get()
            ->keyBy('student_id');

        $submissionList = $students->map(function ($student) use ($submissions, $assessment) {
            return [
                'student' => $student,
                'submission' => $submissions->get($student->id) ?? [
                    'assessment_id' => $assessment->id,
                    'student_id' => $student->id,
                    'status' => 'Not Submitted',
                    'score_obtained' => null
                ],
            ];
        });

        return response()->json($submissionList);
    }
    
    /**
     * جلب التفاصيل الكاملة لتسليم طالب واحد (الأسئلة والإجابات)
     */
/**
 * جلب التفاصيل الكاملة لتسليم طالب واحد (الأسئلة والإجابات والنتائج)
 */
public function getSubmissionDetails(StudentAssessmentScore $submission)
{
    // تحميل كل البيانات المطلوبة بكفاءة عالية في استعلامات قليلة
    $submission->load([
        // تحميل بيانات الطالب الأساسية
        'student:id,name', 
        
        // تحميل التقييم، مع أسئلته، ومع خيارات كل سؤال
        'assessment.quizQuestions.options', 
        
        // تحميل إجابات الطالب، مع الخيار الذي اختاره لكل إجابة
        'quizAnswers.chosenOption' 
    ]);

    return response()->json($submission);
}


    public function getDetailsForStudent(Assessment $assessment)
    {
        // نتأكد من أن التقييم هو اختبار إلكتروني
        if (!$assessment->is_online_quiz) {
            return response()->json(['message' => 'This assessment is not an online quiz.'], 404);
        }

        // نقوم بتحميل الأسئلة مع خياراتها
        // ونقوم بتحديد الأعمدة التي نريد إرسالها فقط من الخيارات (لإخفاء is_correct_answer)
        $assessment->load(['quizQuestions.options' => function ($query) {
            $query->select('id', 'quiz_question_id', 'option_text', 'order');
        }]);

        return response()->json($assessment);
    }
    /**
     * حفظ تصحيح إجابات الطالب والدرجة النهائية.
     */
    public function gradeSubmission(Request $request, StudentAssessmentScore $submission)
    {
        $validated = $request->validate([
            'answers' => 'sometimes|array',
            'answers.*.id' => 'required|exists:student_quiz_answers,id',
            'answers.*.points_awarded' => 'required|numeric|min:0',
            'final_score' => 'required|numeric|min:0|max:' . $submission->assessment->max_score,
        ]);

        // تحديث درجات كل سؤال مقالي/قصير
        if (isset($validated['answers'])) {
            foreach($validated['answers'] as $answerData) {
                StudentQuizAnswer::where('id', $answerData['id'])->update([
                    'points_awarded' => $answerData['points_awarded'],
                    'is_marked_correct' => true, // اعتبارها مصححة
                ]);
            }
        }

        // تحديث الدرجة النهائية للتسليم
        $submission->update([
            'score_obtained' => $validated['final_score'],
            'status' => 'Graded',
            'graded_by_teacher_id' => auth()->id(),
            'grading_timestamp' => now(),
        ]);

        return response()->json(['message' => 'Submission graded successfully.']);
    }

// في Controller المسؤول عن المسار /students/{student}/assessments

public function getAssessmentsForStudent(Student $student)
{
    // 1. العثور على جميع المقررات الدراسية المسجلة لشعبة الطالب الحالية
    $courseOfferingIds = CourseOffering::where('section_id', $student->section_id)
                                       ->pluck('id');

    // 2. جلب كل التقييمات المرئية للطلاب والمرتبطة بهذه المقررات
    $assessments = Assessment::with('courseOffering.subject:id,name')
                            ->whereIn('course_offering_id', $courseOfferingIds)
                            ->where('is_visible_to_students', true)
                            // ✨ --- التعديل الرئيسي هنا --- ✨
                            // قمنا بتغيير الترتيب ليصبح حسب تاريخ الإنشاء (الأحدث أولاً)
                            ->latest() // latest() by default sorts by 'created_at' desc
                            ->get();

    // 3. جلب جميع سجلات درجات هذا الطالب لهذه التقييمات في استعلام واحد فقط
    $assessmentIds = $assessments->pluck('id');
    $scoreRecords = StudentAssessmentScore::where('student_id', $student->id)
                                          ->whereIn('assessment_id', $assessmentIds)
                                          ->get()
                                          ->keyBy('assessment_id');

    // 4. دمج بيانات التقييمات مع بيانات الدرجات
    $assessmentsWithDetails = $assessments->map(function ($assessment) use ($scoreRecords) {
        $scoreRecord = $scoreRecords->get($assessment->id);

        return [
            'id' => $assessment->id,
            'title' => $assessment->title,
            'description' => $assessment->description,
            'subject_name' => $assessment->courseOffering->subject->name ?? 'N/A',
            'is_online_quiz' => $assessment->is_online_quiz,
            'max_score' => $assessment->max_score,
            'due_date_time' => $assessment->due_date_time,
            'submission_status' => $scoreRecord->status ?? 'Not Submitted',
            'score_obtained' => $scoreRecord->score_obtained ?? null,
            'submission_id' => $scoreRecord->id ?? null,
        ];
    });

    // 5. إرجاع النتيجة النهائية
    return response()->json(['assessments' => $assessmentsWithDetails]);
}
}