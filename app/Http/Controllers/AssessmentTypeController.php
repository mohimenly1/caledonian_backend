<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\AssessmentType;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class AssessmentTypeController extends Controller
{
    /**
     * عرض جميع أنواع التقييمات المتاحة.
     * GET /api/assessment-types
     */
    public function index()
    {
        // سيعمل هذا الكود الآن لجلب البيانات لواجهة الإدارة والواجهات الأخرى
        $types = AssessmentType::orderBy('name')->get();
        return response()->json($types);
    }

    /**
     * إنشاء نوع تقييم جديد.
     * POST /api/assessment-types
     */
    public function store(Request $request)
    {
        $validatedData = $request->validate([
            'name' => 'required|string|max:255|unique:assessment_types,name',
            'description' => 'nullable|string',
            'default_max_score' => 'nullable|integer|min:0',
            'is_summative' => 'sometimes|boolean',
            'requires_submission_file' => 'sometimes|boolean',
        ]);

        $assessmentType = AssessmentType::create($validatedData);

        return response()->json([
            'message' => 'Assessment type created successfully.',
            'assessment_type' => $assessmentType
        ], 201);
    }

    /**
     * عرض تفاصيل نوع تقييم محدد.
     * GET /api/assessment-types/{assessment_type}
     */
    public function show(AssessmentType $assessmentType)
    {
        return response()->json($assessmentType);
    }

    /**
     * تحديث نوع تقييم موجود.
     * PUT /api/assessment-types/{assessment_type}
     */
    public function update(Request $request, AssessmentType $assessmentType)
    {
        $validatedData = $request->validate([
            'name' => ['sometimes', 'required', 'string', 'max:255', Rule::unique('assessment_types', 'name')->ignore($assessmentType->id)],
            'description' => 'nullable|string',
            'default_max_score' => 'nullable|integer|min:0',
            'is_summative' => 'sometimes|boolean',
            'requires_submission_file' => 'sometimes|boolean',
        ]);

        $assessmentType->update($validatedData);

        return response()->json([
            'message' => 'Assessment type updated successfully.',
            'assessment_type' => $assessmentType
        ]);
    }

    /**
     * حذف نوع تقييم.
     * DELETE /api/assessment-types/{assessment_type}
     */
    public function destroy(AssessmentType $assessmentType)
    {
        // قبل الحذف، تحقق مما إذا كان هذا النوع مستخدمًا في أي تقييم
        if ($assessmentType->assessments()->exists()) {
            return response()->json([
                'message' => 'Cannot delete this assessment type because it is already in use by one or more assessments.'
            ], 409); // 409 Conflict
        }

        $assessmentType->delete();

        return response()->json(['message' => 'Assessment type deleted successfully.']);
    }
}