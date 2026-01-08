<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\StudyYear;
use App\Models\GradeLevel;
use App\Models\Subject;
use App\Models\ClassRoom; // <-- ⭐ إضافة
use App\Models\CourseOffering; // <-- ⭐ إضافة
use App\Models\Student; // <-- ⭐ إضافة
use App\Models\TeacherCourseAssignment; // <-- ⭐ إضافة
use App\Models\User; // <-- ⭐ إضافة
use Illuminate\Support\Facades\Log;

class EduraAcademicDataController extends Controller
{
    /**
     * جلب جميع السنوات الدراسية النشطة مع الفصول الدراسية (Terms) التابعة لها.
     */
    public function getStudyYears()
    {
        $studyYears = StudyYear::where('is_active', true)
                        ->with('terms:id,name,study_year_id,start_date,end_date')
                        ->select('id', 'name', 'start_date', 'end_date')
                        ->get();


        return response()->json($studyYears);
    }

    /**
     * جلب جميع المستويات الدراسية (مثل Y1, Y2).
     */
    public function getGradeLevels()
    {
        $gradeLevels = GradeLevel::select('id', 'name', 'description')
                        ->get();


        return response()->json($gradeLevels);
    }

    /**
     * جلب جميع المواد الدراسية المعرفة في النظام.
     */
    public function getSubjects()
    {
        $subjects = Subject::select('id', 'name', 'code')
                        ->get();


        return response()->json($subjects);
    }

    // --- ⭐⭐ دالة جديدة لجلب الفصول والشعب ⭐⭐ ---
    /**
     * جلب الفصول والشعب بناءً على السنة الدراسية والمستوى.
     */
    public function getClassesAndSections(Request $request)
    {
        $request->validate([
            'study_year_id' => 'required|integer|exists:study_years,id',
            'grade_level_id' => 'nullable|integer|exists:grade_levels,id',
        ]);

        Log::info('[SchoolApp] getClassesAndSections request received.', [
            'study_year_id' => $request->input('study_year_id'),
            'grade_level_id' => $request->input('grade_level_id'),
            'full_query' => $request->query(),
        ]);

        Log::info('[SchoolApp] getClassesAndSections request received.', [
            'study_year_id' => $request->input('study_year_id'),
            'grade_level_id' => $request->input('grade_level_id'),
            'full_query' => $request->query(),
        ]);

        $query = ClassRoom::query()
                    ->where('study_year_id', '=', $request->study_year_id)
                    ->where('study_year_id', '=', $request->study_year_id)
                    ->with('sections:id,name,class_id'); // جلب الشعب التابعة

        if ($request->filled('grade_level_id')) {
            $query->where('grade_level_id', '=', $request->grade_level_id);
            $query->where('grade_level_id', '=', $request->grade_level_id);
        }

        $classes = $query->select('id', 'name', 'grade_level_id')->get();

        Log::info('[SchoolApp] getClassesAndSections returning response.', [
            'study_year_id' => $request->input('study_year_id'),
            'grade_level_id' => $request->input('grade_level_id'),
            'class_count' => $classes->count(),
            'class_ids' => $classes->pluck('id'),
        ]);

        Log::info('[SchoolApp] getClassesAndSections returning response.', [
            'study_year_id' => $request->input('study_year_id'),
            'grade_level_id' => $request->input('grade_level_id'),
            'class_count' => $classes->count(),
            'class_ids' => $classes->pluck('id'),
        ]);

        return response()->json($classes);
    }

    /**
     * إرجاع كتالوج كامل للفصول والشعب مع إمكانية التصفية الاختيارية
     */
    public function getClassesCatalog(Request $request)
    {
        $query = ClassRoom::query()
            ->with('sections:id,name,class_id')
            ->select(['id', 'name', 'grade_level_id', 'study_year_id'])
            ->orderBy('study_year_id', 'asc')
            ->orderBy('name', 'asc');

        if ($request->filled('study_year_id')) {
            $query->where('study_year_id', '=', $request->integer('study_year_id'));
        }

        if ($request->filled('grade_level_id')) {
            $query->where('grade_level_id', '=', $request->integer('grade_level_id'));
        }

        $classes = $query->get()->map(function (ClassRoom $class) {
            return [
                'id' => $class->id,
                'name' => $class->name,
                'grade_level_id' => $class->grade_level_id,
                'study_year_id' => $class->study_year_id,
                'sections' => $class->sections->map(fn ($section) => [
                    'id' => $section->id,
                    'name' => $section->name,
                ])->values(),
            ];
        });

        return response()->json([
            'study_years' => StudyYear::select(['id', 'name'])->orderBy('name', 'asc')->get(),
            'grade_levels' => GradeLevel::select(['id', 'name'])->orderBy('name', 'asc')->get(),
            'classes' => $classes,
        ]);
    }

    /**
     * جلب فهرس كامل للفصول والشعب مع إمكانية الفلترة اختيارياً
     */
    public function getAllClassesCatalog(Request $request)
    {
        $query = ClassRoom::with('sections:id,name,class_id')
            ->select(['id', 'name', 'grade_level_id', 'study_year_id'])
            ->orderByDesc('study_year_id')
            ->orderBy('name');

        if ($request->filled('study_year_id')) {
            $query->where('study_year_id', '=', $request->study_year_id);
        }

        if ($request->filled('grade_level_id')) {
            $query->where('grade_level_id', '=', $request->grade_level_id);
        }

        $classes = $query->get()->map(function (ClassRoom $class) {
            return [
                'id' => $class->id,
                'name' => $class->name,
                'grade_level_id' => $class->grade_level_id,
                'study_year_id' => $class->study_year_id,
                'sections' => $class->sections->map(fn ($section) => [
                    'id' => $section->id,
                    'name' => $section->name,
                ])->values(),
            ];
        })->values();

        return response()->json([
            'study_years' => StudyYear::orderByDesc('id')->get(['id', 'name']),
            'grade_levels' => GradeLevel::orderBy('id')->get(['id', 'name']),
            'classes' => $classes,
        ]);
    }

    /**
     * إرجاع كتالوج كامل للفصول والشعب مع إمكانية التصفية الاختيارية
     */
    public function getClassesCatalog(Request $request)
    {
        $query = ClassRoom::query()
            ->with('sections:id,name,class_id')
            ->select(['id', 'name', 'grade_level_id', 'study_year_id'])
            ->orderBy('study_year_id', 'asc')
            ->orderBy('name', 'asc');

        if ($request->filled('study_year_id')) {
            $query->where('study_year_id', '=', $request->integer('study_year_id'));
        }

        if ($request->filled('grade_level_id')) {
            $query->where('grade_level_id', '=', $request->integer('grade_level_id'));
        }

        $classes = $query->get()->map(function (ClassRoom $class) {
            return [
                'id' => $class->id,
                'name' => $class->name,
                'grade_level_id' => $class->grade_level_id,
                'study_year_id' => $class->study_year_id,
                'sections' => $class->sections->map(fn ($section) => [
                    'id' => $section->id,
                    'name' => $section->name,
                ])->values(),
            ];
        });

        return response()->json([
            'study_years' => StudyYear::select(['id', 'name'])->orderBy('name', 'asc')->get(),
            'grade_levels' => GradeLevel::select(['id', 'name'])->orderBy('name', 'asc')->get(),
            'classes' => $classes,
        ]);
    }

    /**
     * جلب فهرس كامل للفصول والشعب مع إمكانية الفلترة اختيارياً
     */
    public function getAllClassesCatalog(Request $request)
    {
        $query = ClassRoom::with('sections:id,name,class_id')
            ->select(['id', 'name', 'grade_level_id', 'study_year_id'])
            ->orderByDesc('study_year_id')
            ->orderBy('name');

        if ($request->filled('study_year_id')) {
            $query->where('study_year_id', '=', $request->study_year_id);
        }

        if ($request->filled('grade_level_id')) {
            $query->where('grade_level_id', '=', $request->grade_level_id);
        }

        $classes = $query->get()->map(function (ClassRoom $class) {
            return [
                'id' => $class->id,
                'name' => $class->name,
                'grade_level_id' => $class->grade_level_id,
                'study_year_id' => $class->study_year_id,
                'sections' => $class->sections->map(fn ($section) => [
                    'id' => $section->id,
                    'name' => $section->name,
                ])->values(),
            ];
        })->values();

        return response()->json([
            'study_years' => StudyYear::orderByDesc('id')->get(['id', 'name']),
            'grade_levels' => GradeLevel::orderBy('id')->get(['id', 'name']),
            'classes' => $classes,
        ]);
    }

    public function getSubjectsForClass(Request $request)
    {
        // ✅ جعل study_year_id اختياري - إذا لم يتم إرساله، نجلبه من الفصل
        // ✅ جعل class_id اختياري إذا تم إرسال grade_level_id
        $validated = $request->validate([
            'study_year_id' => 'nullable|integer|exists:study_years,id',
            'class_id' => 'nullable|integer|exists:classes,id',
            'section_id' => 'nullable|integer|exists:sections,id',
            'grade_level_id' => 'nullable|integer|exists:grade_levels,id', // ✅ إضافة grade_level_id
        ]);

        // ✅ إذا تم إرسال grade_level_id بدلاً من class_id، نستخدم المنطق الجديد
        if ($request->filled('grade_level_id') && !$request->filled('class_id')) {
            return $this->getSubjectsForGradeLevel($request);
        }

        // ✅ إذا لم يتم إرسال class_id أو grade_level_id، نرجع خطأ
        if (!$request->filled('class_id') && !$request->filled('grade_level_id')) {
            return response()->json([
                'success' => false,
                'message' => 'يجب إرسال class_id أو grade_level_id',
                'data' => []
            ], 400);
        }

        // إذا لم يتم إرسال study_year_id، جلب السنة الدراسية من الفصل
        if (empty($validated['study_year_id'])) {
            $class = ClassRoom::find($validated['class_id']);
            if ($class && $class->study_year_id) {
                $validated['study_year_id'] = $class->study_year_id;
            } else {
                // إذا لم تكن هناك سنة دراسية، نستخدم السنة النشطة
                $activeStudyYear = StudyYear::where('is_active', true)->first();
                if ($activeStudyYear) {
                    $validated['study_year_id'] = $activeStudyYear->id;
                } else {
                    return response()->json([
                        'success' => false,
                        'message' => 'لا توجد سنة دراسية نشطة',
                        'data' => []
                    ], 400);
                }
            }
        }

        // جلب subject_ids الفريدة من course_offerings
        // التي تطابق السنة والفصل
        $subjectIdsQuery = CourseOffering::where('study_year_id', $validated['study_year_id'])
                            ->where('class_id', $validated['class_id']);

        // إذا تم تحديد شعبة، يتم فلترة المواد الخاصة بهذه الشعبة
        // إذا لم يتم تحديد شعبة، يتم جلب المواد المرتبطة بالفصل (section_id = null)
        if ($request->filled('section_id')) {
            $sectionId = $validated['section_id'];
            $subjectIdsQuery->where(function ($query) use ($sectionId) {
                $query->whereNull('section_id')
                      ->orWhere('section_id', $sectionId);
            });
        }

        $subjectIds = $subjectIdsQuery->distinct()->pluck('subject_id');

        // جلب تفاصيل هذه المواد
        $subjects = Subject::whereIn('id', $subjectIds)
                    ->select('id', 'name', 'code')
                    ->get();

        // ✅ إرجاع بنفس التنسيق المتوقع من Edura system
        return response()->json([
            'success' => true,
            'data' => $subjects
        ]);
    }

    /**
     * ✅ جلب المواد بناءً على المستوى الدراسي (grade_level_id)
     * يجلب جميع المواد المخصصة للفصول في هذا المستوى الدراسي من course_offerings
     */
    public function getSubjectsForGradeLevel(Request $request)
    {
        $validated = $request->validate([
            'grade_level_id' => 'required|integer|exists:grade_levels,id',
            'study_year_id' => 'nullable|integer|exists:study_years,id',
        ]);

        try {
            // ✅ جلب السنة الدراسية النشطة إذا لم يتم تحديدها
            $studyYearId = $validated['study_year_id'] ?? null;
            if (!$studyYearId) {
                $activeStudyYear = StudyYear::where('is_active', true)->first();
                if ($activeStudyYear) {
                    $studyYearId = $activeStudyYear->id;
                } else {
                    return response()->json([
                        'success' => false,
                        'message' => 'لا توجد سنة دراسية نشطة',
                        'data' => []
                    ], 400);
                }
            }

            // ✅ جلب جميع الفصول في هذا المستوى الدراسي
            $classIds = ClassRoom::where('grade_level_id', $validated['grade_level_id'])
                ->where('study_year_id', $studyYearId)
                ->pluck('id');

            if ($classIds->isEmpty()) {
                Log::warning('[EduraAcademicDataController@getSubjectsForGradeLevel] No classes found', [
                    'grade_level_id' => $validated['grade_level_id'],
                    'study_year_id' => $studyYearId,
                ]);
                return response()->json([
                    'success' => true,
                    'data' => []
                ]);
            }

            // ✅ جلب جميع المواد من course_offerings لهذه الفصول
            $subjectIds = CourseOffering::whereIn('class_id', $classIds)
                ->where('study_year_id', $studyYearId)
                ->distinct()
                ->pluck('subject_id');

            if ($subjectIds->isEmpty()) {
                Log::warning('[EduraAcademicDataController@getSubjectsForGradeLevel] No subjects found', [
                    'grade_level_id' => $validated['grade_level_id'],
                    'study_year_id' => $studyYearId,
                    'class_ids' => $classIds->toArray(),
                ]);
                return response()->json([
                    'success' => true,
                    'data' => []
                ]);
            }

            // ✅ جلب تفاصيل هذه المواد
            $subjects = Subject::whereIn('id', $subjectIds)
                ->select('id', 'name', 'code')
                ->orderBy('name')
                ->get();

            Log::info('[EduraAcademicDataController@getSubjectsForGradeLevel] Subjects fetched', [
                'grade_level_id' => $validated['grade_level_id'],
                'study_year_id' => $studyYearId,
                'class_count' => $classIds->count(),
                'subject_count' => $subjects->count(),
            ]);

            return response()->json([
                'success' => true,
                'data' => $subjects
            ]);
        } catch (\Exception $e) {
            Log::error('[EduraAcademicDataController@getSubjectsForGradeLevel] Error', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'فشل جلب المواد',
                'error' => $e->getMessage(),
                'data' => []
            ], 500);
        }
    }

    public function getStudentsForClass(Request $request)
    {
         $validated = $request->validate([
            'study_year_id' => 'required|integer|exists:study_years,id',
            'class_id' => 'required|integer|exists:classes,id',
            'section_id' => 'nullable|integer|exists:sections,id',
        ]);

        $query = Student::where('study_year_id', $validated['study_year_id'])
                        ->where('class_id', $validated['class_id'])
                         // جلب الطلاب بالأسماء فقط للجدول
                        ->select('id', 'name', 'arabic_name', 'class_id', 'section_id')
                        ->orderBy('name'); // ترتيب أبجدي

        if ($request->filled('section_id')) {
             $query->where('section_id', $validated['section_id']);
        } else {
            // إذا لم يتم تحديد شعبة، قد ترغب في جلب الطلاب غير المرتبطين بشعبة
             $query->where(function($q) {
                $q->whereNull('section_id')->orWhere('section_id', 0);
             });
        }


        // استخدام paginate لجلب الطلاب على دفعات
        $students = $query->paginate($request->input('per_page', 50))->withQueryString();

        return response()->json($students);
    }

    /**
     * جلب المواد المسندة لمعلم في فصل معين
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function getTeacherSubjectsForClass(Request $request)
    {
        $validated = $request->validate([
            'teacher_id' => 'required|integer|exists:users,id',
            'class_id' => 'required|integer|exists:classes,id',
            'section_id' => 'nullable|integer|exists:sections,id',
        ]);

        try {
            // جلب الإسنادات الخاصة بالمعلم والفصل المحدد
            $assignmentsQuery = TeacherCourseAssignment::where('teacher_id', $validated['teacher_id'])
                ->whereHas('courseOffering', function($q) use ($validated) {
                    $q->where('class_id', $validated['class_id']);

                    // إذا تم تحديد شعبة، جلب المواد للشعبة أو المواد العامة (section_id = null)
                    if (isset($validated['section_id'])) {
                        $q->where(function($query) use ($validated) {
                            $query->whereNull('section_id')
                                  ->orWhere('section_id', $validated['section_id']);
                        });
                    }
                })
                ->with([
                    'courseOffering.subject:id,name,code',
                    'courseOffering:id,subject_id,class_id,section_id',
                    'section:id,name'
                ]);

            $assignments = $assignmentsQuery->get();

            // تنسيق البيانات
            $subjects = $assignments->map(function($assignment) {
                return [
                    'id' => $assignment->courseOffering->subject->id,
                    'name' => $assignment->courseOffering->subject->name,
                    'code' => $assignment->courseOffering->subject->code,
                    'section_id' => $assignment->section_id,
                    'section_name' => $assignment->section?->name,
                    'course_offering_id' => $assignment->course_offering_id,
                ];
            })->unique('id')->values(); // إزالة التكرارات

            return response()->json([
                'success' => true,
                'data' => $subjects,
            ]);
        } catch (\Exception $e) {
            Log::error('[EduraAcademicDataController@getTeacherSubjectsForClass] Error: ' . $e->getMessage(), [
                'trace' => $e->getTraceAsString(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch teacher subjects',
                'error' => $e->getMessage()
            ], 500);
        }
    }
    // --- نهاية الدالة الجديدة ---

    /**
     * ✅ جلب أسماء المواد والمعلمين مباشرة من قاعدة البيانات بناءً على IDs
     * للاستخدام من Edura system - بدون auth:sanctum
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function getNamesByIds(Request $request)
    {
        try {
            Log::info('[EduraAcademicDataController@getNamesByIds] 🔍 Request received', [
                'subject_ids' => $request->input('subject_ids'),
                'teacher_ids' => $request->input('teacher_ids'),
            ]);

            $validated = $request->validate([
                'subject_ids' => 'nullable|array',
                'subject_ids.*' => 'integer',
                'teacher_ids' => 'nullable|array',
                'teacher_ids.*' => 'integer',
            ]);

            $result = [
                'success' => true,
                'subjects' => [],
                'teachers' => [],
            ];

            // ✅ جلب أسماء المواد مباشرة من قاعدة البيانات
            if (!empty($validated['subject_ids'])) {
                $subjects = Subject::whereIn('id', $validated['subject_ids'])
                    ->select('id', 'name', 'code')
                    ->get();

                foreach ($subjects as $subject) {
                    $result['subjects'][$subject->id] = [
                        'id' => $subject->id,
                        'name' => $subject->name,
                        'code' => $subject->code,
                    ];
                }

                Log::debug('[EduraAcademicDataController@getNamesByIds] 📚 Subjects fetched', [
                    'count' => count($result['subjects']),
                    'subjects' => $result['subjects'],
                ]);
            }

            // ✅ جلب أسماء المعلمين مباشرة من قاعدة البيانات
            if (!empty($validated['teacher_ids'])) {
                $teachers = User::whereIn('id', $validated['teacher_ids'])
                    ->where('user_type', 'teacher')
                    ->select('id', 'name', 'email')
                    ->get();

                foreach ($teachers as $teacher) {
                    $result['teachers'][$teacher->id] = [
                        'id' => $teacher->id,
                        'name' => $teacher->name,
                        'email' => $teacher->email,
                    ];
                }

                Log::debug('[EduraAcademicDataController@getNamesByIds] 👥 Teachers fetched', [
                    'count' => count($result['teachers']),
                    'teachers' => $result['teachers'],
                ]);
            }

            Log::info('[EduraAcademicDataController@getNamesByIds] ✅ Success', [
                'subjects_count' => count($result['subjects']),
                'teachers_count' => count($result['teachers']),
            ]);

            // ✅ إرجاع الاستجابة مباشرة بدون أي تأخير
            return response()->json($result, 200, [
                'Content-Type' => 'application/json',
                'Cache-Control' => 'no-cache, no-store, must-revalidate',
                'Pragma' => 'no-cache',
                'Expires' => '0',
            ], JSON_UNESCAPED_UNICODE);
        } catch (\Exception $e) {
            Log::error('[EduraAcademicDataController@getNamesByIds] ❌ Error', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch names',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}

