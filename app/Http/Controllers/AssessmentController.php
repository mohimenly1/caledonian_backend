<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\Assessment;
use App\Models\CourseOffering;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\AssessmentType;
use App\Models\StudyYear;
use App\Models\Term;
use App\Models\Student;
use App\Models\ParentInfo;
use App\Models\User;
use App\Models\CaledonianNotification;
use App\Notifications\NewAssessmentNotification;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\DB;

class AssessmentController extends Controller
{
    /**
     * عرض جميع التقييمات لمقرر دراسي معين.
     */
    public function index(Request $request)
    {
        // 1. التحقق من صحة الفلاتر (لا تغيير هنا)
        $request->validate([
            'teacher_id' => 'sometimes|nullable|exists:users,id',
            'study_year_id' => 'sometimes|nullable|exists:study_years,id',
            'class_id' => 'sometimes|nullable|exists:classes,id',
            'term_id' => 'sometimes|nullable|exists:terms,id',
        ]);
    
        // 2. بناء الاستعلام الأساسي مع تحميل العلاقات بكفاءة
        $query = Assessment::with([
            'assessmentType:id,name',
            'createdByTeacher:id,name',
            'courseOffering.subject:id,name',
            'courseOffering.schoolClass:id,name',
            'section:id,name',
            'term:id,name',
        ])
        // ✨ --- بداية الإصلاح --- ✨
        // سنقوم بحساب عدد الدرجات المرصودة فقط هنا
        ->withCount('studentScores');
        // --- نهاية الإصلاح --- ✨

        // 3. تطبيق فلتر المعلم (لا تغيير هنا)
        $user = Auth::user();
        if ($user->user_type === 'teacher') {
            $query->where('created_by_teacher_id', $user->id);
        } 
        elseif ($request->filled('teacher_id')) {
            $query->where('created_by_teacher_id', $request->teacher_id);
        }
    
        // 4. تطبيق الفلاتر الإضافية (لا تغيير هنا)
        if ($request->filled('term_id')) {
            $query->where('term_id', $request->term_id);
        }
    
        if ($request->filled('study_year_id') || $request->filled('class_id')) {
            $query->whereHas('courseOffering', function ($q) use ($request) {
                if ($request->filled('study_year_id')) {
                    $q->where('study_year_id', $request->study_year_id);
                }
                if ($request->filled('class_id')) {
                    $q->where('class_id', $request->class_id);
                }
            });
        }
    
        // 5. جلب التقييمات مع ترقيم الصفحات
        $assessments = $query->latest()->paginate($request->input('per_page', 15));
        
        // ✨ --- بداية الإصلاح الرئيسي --- ✨
        // 6. إضافة العدد الإجمالي للطلاب لكل تقييم على حدة (نفس منطق دالة المعلم)
        collect($assessments->items())->each(function ($assessment) {
            // لكل تقييم، نقوم بحساب عدد الطلاب في شعبته
            $studentCount = Student::where('section_id', $assessment->section_id)->count();
            // ثم نضيف هذا العدد إلى كائن التقييم
            $assessment->students_count = $studentCount;
        });
        // --- نهاية الإصلاح الرئيسي --- ✨

        return response()->json($assessments);
    }
    
    /**
     * إنشاء تقييم جديد لمقرر دراسي.
     */
    public function store(Request $request)
    {
        $validatedData = $request->validate([
            'course_offering_id' => 'required|exists:course_offerings,id',
            'section_id' => 'required|exists:sections,id',
            'title' => 'required|string|max:255',
            'assessment_type_id' => 'required|exists:assessment_types,id',
            'term_id' => 'required|exists:terms,id',
            'max_score' => 'required|numeric|min:0',
            'description' => 'nullable|string',
            'publish_date_time' => 'nullable|date',
            'due_date_time' => 'nullable|date',
            'grading_due_date_time' => 'nullable|date',
            'is_online_quiz' => 'sometimes|boolean',
            'is_visible_to_students' => 'sometimes|boolean',
            'are_grades_published' => 'sometimes|boolean',
            'created_by_teacher_id' => 'sometimes|nullable|exists:users,id', // أصبح اختيارياً
        ]);
    
            // ✨ إذا لم يتم إرسال هوية المعلم، استخدم هوية المستخدم الحالي ✨
    if (empty($validatedData['created_by_teacher_id'])) {
        $validatedData['created_by_teacher_id'] = Auth::id();
    }
        // 1. إنشاء التقييم الأساسي
        $assessment = Assessment::create($validatedData);
    
        // --- ✨ بداية منطق الإشعارات المحدث والكامل ✨ ---
    
        // 2. أرسل الإشعار فقط إذا كان التقييم مرئياً للطلاب
        if ($assessment->is_visible_to_students) {
            // تحميل العلاقات اللازمة لبناء رسالة الإشعار بكفاءة
            $assessment->load('courseOffering.subject');
    
            // 3. جلب الطلاب مع أولياء أمورهم وحسابات المستخدمين الخاصة بهم في استعلام واحد
            $students = Student::with('parent.user')
                               ->where('section_id', $assessment->section_id)
                               ->get();
    
            // 4. المرور على كل طالب وإرسال إشعار مخصص لولي أمره
            foreach ($students as $student) {
                // تأكد من وجود ولي أمر وحساب مستخدم مرتبط به
                if ($student->parent && $student->parent->user) {
                    $user = $student->parent->user;
                    $deliveryMethod = $assessment->is_online_quiz ? 'إلكترونياً عبر التطبيق' : 'في المدرسة';
    
                    // بناء العنوان والنص للإشعار (مع تخصيص اسم الطالب)
                    $title = "تقييم جديد: {$assessment->title}";
                    $body = "تم نشر تقييم جديد لابنك/ابنتك {$student->name} في مادة {$assessment->courseOffering->subject->name}. طريقة التسليم: {$deliveryMethod}.";
    
                    // 5. حفظ الإشعار في قاعدة البيانات (ليظهر في قائمة الإشعارات داخل التطبيق)
                    CaledonianNotification::create([
                        'user_id' => $user->id,
                        'title'   => $title,
                        'body'    => $body,
                        'data'    => json_encode([
                            'type' => 'new_assessment', 
                            'assessment_id' => $assessment->id,
                            'student_id' => $student->id // حفظ student_id هنا
                        ]),
                    ]);
    
                    // 6. إرسال الإشعار اللحظي (FCM) مع student_id
                    if ($user->fcm_token) {
                        $user->notify(new NewAssessmentNotification($assessment, $student->id));
                    }
                }
            }
        }
        // --- نهاية منطق الإشعارات ---
    
        return response()->json([
            'message' => 'Assessment created successfully.',
            'assessment' => $assessment->load('assessmentType:id,name')
        ], 201);
    }

    /**
     * عرض تفاصيل تقييم محدد.
     */
    public function show(Assessment $assessment)
    {
        return response()->json($assessment->load('assessmentType', 'courseOffering.subject'));
    }

    /**
     * تحديث بيانات تقييم موجود.
     */
    public function update(Request $request, Assessment $assessment)
    {
        // --- التعديل الرئيسي هنا: إضافة الحقول الجديدة لقواعد التحقق ---
        $validatedData = $request->validate([
            'title' => 'sometimes|required|string|max:255',
            'assessment_type_id' => 'sometimes|required|exists:assessment_types,id',
            'term_id' => 'sometimes|required|exists:terms,id',
            'description' => 'nullable|string',
            'max_score' => 'sometimes|required|numeric|min:0',
            'publish_date_time' => 'nullable|date',
            'due_date_time' => 'nullable|date|after_or_equal:publish_date_time',
            'grading_due_date_time' => 'nullable|date|after_or_equal:due_date_time',
            'is_online_quiz' => 'sometimes|boolean',
            'is_visible_to_students' => 'sometimes|boolean',
            'are_grades_published' => 'sometimes|boolean',
        ]);

        $assessment->update($validatedData);

        return response()->json([
            'message' => 'Assessment updated successfully.',
            'assessment' => $assessment->load('assessmentType:id,name')
        ]);
    }

    /**
     * حذف تقييم.
     */
    public function destroy(Assessment $assessment)
    {
        if ($assessment->studentScores()->exists()) {
            return response()->json(['message' => 'Cannot delete assessment because it has graded scores.'], 409);
        }
        $assessment->delete();
        return response()->json(['message' => 'Assessment deleted successfully.']);
    }

    public function getAssessmentFormData()
    {
        $teacherUser = Auth::user();
        $activeStudyYear = StudyYear::where('is_active', true)->firstOrFail();

        // Get all unique assignments for the teacher in the active year
        $assignments = $teacherUser->teacherCourseAssignments()
            ->whereHas('courseOffering', function ($query) use ($activeStudyYear) {
                $query->where('study_year_id', $activeStudyYear->id);
            })
            ->with([
                'courseOffering.subject:id,name',
                'courseOffering.schoolClass:id,name',
                'section:id,name'
            ])
            ->get();

        // Get terms for the active study year
        $terms = Term::where('study_year_id', $activeStudyYear->id)->get(['id', 'name']);
        
        // Get all assessment types
        $assessmentTypes = AssessmentType::all(['id', 'name']);

        return response()->json([
            'assignments' => $assignments,
            'terms' => $terms,
            'assessment_types' => $assessmentTypes,
        ]);
    }
}
