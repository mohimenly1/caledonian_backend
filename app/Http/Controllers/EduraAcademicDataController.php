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

        $query = ClassRoom::query()
                    ->where('study_year_id', '=', $request->study_year_id)
                    ->with('sections:id,name,class_id'); // جلب الشعب التابعة

        if ($request->filled('grade_level_id')) {
            $query->where('grade_level_id', '=', $request->grade_level_id);
        }

        $classes = $query->select('id', 'name', 'grade_level_id')->get();

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

    public function getSubjectsForClass(Request $request)
    {
        $validated = $request->validate([
            'study_year_id' => 'required|integer|exists:study_years,id',
            'class_id' => 'required|integer|exists:classes,id',
            'section_id' => 'nullable|integer|exists:sections,id',
        ]);

        // جلب subject_ids الفريدة من course_offerings
        // التي تطابق السنة والفصل
        $subjectIdsQuery = CourseOffering::where('study_year_id', $validated['study_year_id'])
                            ->where('class_id', $validated['class_id']);

        // إذا تم تحديد شعبة، يتم فلترة المواد الخاصة بهذه الشعبة
        // إذا لم يتم تحديد شعبة، يتم جلب المواد المرتبطة بالفصل (section_id = null)
        if ($request->filled('section_id')) {
             $subjectIdsQuery->where('section_id', $validated['section_id']);
        } else {
             $subjectIdsQuery->whereNull('section_id');
        }

        $subjectIds = $subjectIdsQuery->distinct()->pluck('subject_id');

        // جلب تفاصيل هذه المواد
        $subjects = Subject::whereIn('id', $subjectIds)
                    ->select('id', 'name', 'code')
                    ->get();

        return response()->json($subjects);
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
    // --- نهاية الدالة الجديدة ---
}

