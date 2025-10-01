<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\GradingPolicy;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;


class GradingPolicyController extends Controller
{
    /**
     * عرض جميع سياسات التقييم مع مكوناتها.
     */
    public function index(Request $request)
    {
        $query = GradingPolicy::with(['components.assessmentType:id,name', 'studyYear:id,name', 'gradeLevel:id,name', 'subject:id,name', 'courseOffering.subject'])
            ->orderBy('created_at', 'desc');
            
        return response()->json($query->paginate($request->input('per_page', 15)));
    }

    /**
     * إنشاء سياسة تقييم جديدة مع مكوناتها.
     */
    public function store(Request $request)
    {
        $validatedData = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'study_year_id' => 'required|exists:study_years,id',
            'grade_level_id' => 'nullable|exists:grade_levels,id',
            'subject_id' => 'nullable|exists:subjects,id',
            'course_offering_id' => 'nullable|exists:course_offerings,id',
            'is_default_for_school' => 'sometimes|boolean',
            'components' => 'required|array|min:1',
            'components.*.assessment_type_id' => 'required|distinct|exists:assessment_types,id',
            'components.*.weight_percentage' => 'required|numeric|min:0|max:100',
        ]);
        
        $totalWeight = collect($validatedData['components'])->sum('weight_percentage');
        if (abs($totalWeight - 100.00) > 0.01) {
            return response()->json(['message' => 'The sum of weight percentages must be exactly 100.'], 422);
        }

        $policy = null;
        DB::transaction(function () use ($validatedData, &$policy) {
            // --- التعديل الرئيسي هنا: التعامل مع الحقول الاختيارية بشكل آمن ---
            $policy = GradingPolicy::create([
                'name' => $validatedData['name'],
                'description' => $validatedData['description'] ?? null,
                'study_year_id' => $validatedData['study_year_id'],
                'grade_level_id' => $validatedData['grade_level_id'] ?? null,
                'subject_id' => $validatedData['subject_id'] ?? null,
                'course_offering_id' => $validatedData['course_offering_id'] ?? null,
                'is_default_for_school' => $validatedData['is_default_for_school'] ?? false,
            ]);

            $policy->components()->createMany($validatedData['components']);
        });

        return response()->json([
            'message' => 'Grading policy created successfully.',
            'policy' => $policy->load('components.assessmentType')
        ], 201);
    }

    /**
     * عرض تفاصيل سياسة تقييم محددة.
     */
    public function show(GradingPolicy $gradingPolicy)
    {
        return response()->json($gradingPolicy->load('components.assessmentType', 'studyYear', 'gradeLevel', 'subject', 'courseOffering'));
    }

    /**
     * تحديث سياسة تقييم موجودة.
     */
    public function update(Request $request, GradingPolicy $gradingPolicy)
    {
        $validatedData = $request->validate([
            'name' => ['sometimes','required','string','max:255', Rule::unique('grading_policies')->ignore($gradingPolicy->id)],
            'description' => 'nullable|string',
            'study_year_id' => 'sometimes|required|exists:study_years,id',
            'grade_level_id' => 'nullable|exists:grade_levels,id',
            'subject_id' => 'nullable|exists:subjects,id',
            'course_offering_id' => 'nullable|exists:course_offerings,id',
            'is_default_for_school' => 'sometimes|boolean',
            'components' => 'sometimes|required|array|min:1',
            'components.*.assessment_type_id' => 'required|distinct|exists:assessment_types,id',
            'components.*.weight_percentage' => 'required|numeric|min:0|max:100',
        ]);
        
        if (isset($validatedData['components'])) {
            $totalWeight = collect($validatedData['components'])->sum('weight_percentage');
            if (abs($totalWeight - 100.00) > 0.01) {
                return response()->json(['message' => 'The sum of weight percentages must be exactly 100.'], 422);
            }
        }
        
        DB::transaction(function () use ($validatedData, $gradingPolicy) {
            $gradingPolicy->update($validatedData);

            if (isset($validatedData['components'])) {
                // --- التعديل الرئيسي هنا: استخدام forceDelete لحذف السجلات نهائيًا ---
                $gradingPolicy->components()->forceDelete();
                $gradingPolicy->components()->createMany($validatedData['components']);
            }
        });

        return response()->json([
            'message' => 'Grading policy updated successfully.',
            'policy' => $gradingPolicy->fresh()->load('components.assessmentType')
        ]);
    }

    /**
     * حذف سياسة تقييم.
     */
    public function destroy(GradingPolicy $gradingPolicy)
    {
        $gradingPolicy->delete();
        return response()->json(['message' => 'Grading policy deleted successfully.']);
    }
}