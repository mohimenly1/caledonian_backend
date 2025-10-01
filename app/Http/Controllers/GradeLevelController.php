<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\GradeLevel;
use App\ApiResponse;
use Illuminate\Validation\Rule;

class GradeLevelController extends Controller
{
    /**
     * عرض جميع المستويات الدراسية مع عدد الفصول المرتبطة بها.
     */
    public function index()
    {
        $gradeLevels = GradeLevel::withCount('classes')->orderBy('id')->get();
        return response()->json($gradeLevels);
    }

    /**
     * تخزين مستوى دراسي جديد.
     */
    public function store(Request $request)
    {
        $validatedData = $request->validate([
            'name' => 'required|string|max:255|unique:grade_levels,name',
            'description' => 'nullable|string',
        ]);

        $gradeLevel = GradeLevel::create($validatedData);
        return response()->json($gradeLevel->loadCount('classes'), 201);
    }

    /**
     * تحديث مستوى دراسي موجود.
     */
    public function update(Request $request, GradeLevel $gradeLevel)
    {
        $validatedData = $request->validate([
            'name' => ['required', 'string', 'max:255', Rule::unique('grade_levels')->ignore($gradeLevel->id)],
            'description' => 'nullable|string',
        ]);

        $gradeLevel->update($validatedData);
        return response()->json($gradeLevel->loadCount('classes'));
    }

    /**
     * حذف مستوى دراسي.
     */
    public function destroy(GradeLevel $gradeLevel)
    {
        // منع الحذف إذا كان المستوى الدراسي يحتوي على فصول
        if ($gradeLevel->classes()->count() > 0) {
            return response()->json(['message' => 'لا يمكن حذف هذا المستوى لأنه يحتوي على فصول دراسية مرتبطة به.'], 422);
        }

        $gradeLevel->delete();
        return response()->json(['message' => 'تم حذف المستوى الدراسي بنجاح.']);
    }
}