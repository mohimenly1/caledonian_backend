<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\ClassRoom;
use App\Models\GradeLevel;
use App\Models\StudyYear;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class SchoolClassController extends Controller
{
    /**
     * عرض جميع الصفوف مع علاقاتها.
     */
    public function index(Request $request)
    {
        $query = ClassRoom::with([
            'gradeLevel:id,name', 
            'studyYear:id,name', 
            'sections:id,name,class_id' // --- التعديل الرئيسي هنا: جلب الأقسام ---
        ])->orderBy('created_at', 'desc');

        if ($request->filled('study_year_id')) {
            $query->where('study_year_id', $request->study_year_id);
        }
        
        // --- الإضافة هنا: دعم الفلترة حسب المستوى الدراسي ---
        if ($request->filled('grade_level_id')) {
            $query->where('grade_level_id', $request->grade_level_id);
        }

        $classes = $query->get();
        return response()->json($classes);
    }
    /**
     * تخزين صف دراسي جديد.
     */
    public function store(Request $request)
    {
        $validatedData = $request->validate([
            'name' => [
                'required',
                'string',
                'max:255',
                Rule::unique('classes')->where(function ($query) use ($request) {
                    return $query->where('study_year_id', $request->study_year_id)
                                 ->where('grade_level_id', $request->grade_level_id);
                }),
            ],
            'description' => 'nullable|string',
            'grade_level_id' => 'required|exists:grade_levels,id',
            'study_year_id' => 'required|exists:study_years,id',
            'is_active' => 'required|boolean',
        ]);

        $class = ClassRoom::create($validatedData);
        
        return response()->json($class->load('gradeLevel:id,name', 'studyYear:id,name'), 201);
    }

    /**
     * تحديث صف دراسي موجود.
     */
    public function update(Request $request, $id)
    {
        $class = ClassRoom::findOrFail($id);

        $validatedData = $request->validate([
            'name' => [
                'required', 'string', 'max:255',
                Rule::unique('classes')->where(fn ($query) => 
                    $query->where('study_year_id', $request->study_year_id)
                          ->where('grade_level_id', $request->grade_level_id)
                )->ignore($class->id),
            ],
            'description' => 'nullable|string',
            'grade_level_id' => 'required|exists:grade_levels,id',
            'study_year_id' => 'required|exists:study_years,id',
            'is_active' => 'required|boolean',
        ]);
        
        $class->update($validatedData);

        return response()->json($class->load('gradeLevel:id,name', 'studyYear:id,name'));
    }

    /**
     * حذف صف دراسي.
     */
    public function destroy(ClassRoom $class)
    {
        if ($class->students()->count() > 0) {
            return response()->json(['message' => 'Cannot delete class with students.'], 422);
        }

        $class->delete();
        return response()->json(['message' => 'Class deleted successfully']);
    }

    /**
     * --- النسخة المصححة ---
     * جلب البيانات اللازمة لفورم إنشاء/تعديل الصفوف.
     */
    public function getFormData()
    {
        return response()->json([
            // --- التعديل الرئيسي هنا: إزالة الشرط الغير موجود ---
            'grade_levels' => GradeLevel::get(['id', 'name']),
            'study_years' => StudyYear::where('is_active', true)->get(['id', 'name']),
        ]);
    }


    public function getSections(ClassRoom $class)
    {
        return response()->json([
            'data' => $class->sections()->get()
        ]);
    }
    public function getSectionsClass(ClassRoom $class)
    {
        // باستخدام Route Model Binding، يتم جلب الفصل تلقائيًا
        // ثم نرجع الشعب المرتبطة به مباشرةً
        return response()->json($class->sections);
    }

    
        public function getSectionsEmployee($id)
    {
        $classRoom = ClassRoom::with('sections')->findOrFail($id);
        return response()->json(['sections' => $classRoom->sections]);
    }

    public function getSectionsForAssgin(ClassRoom $class)
    {
        return response()->json($class->sections);
    }
}
