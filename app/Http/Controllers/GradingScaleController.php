<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\GradingScale;
use App\Models\StudyYear;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class GradingScaleController extends Controller
{
    /**
     * عرض مقياس التقييم لسنة دراسية محددة.
     */
    public function index(Request $request)
    {
        $request->validate(['study_year_id' => 'required|exists:study_years,id']);
        
        $scales = GradingScale::where('study_year_id', $request->study_year_id)
                                ->orderBy('rank_order', 'desc')
                                ->get();

        return response()->json($scales);
    }

    /**
     * إنشاء أو تحديث مقياس تقييم كامل لسنة دراسية.
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'study_year_id' => 'required|exists:study_years,id',
            'scales' => 'required|array|min:1',
            'scales.*.grade_label' => 'required|string|max:255',
            'scales.*.min_percentage' => 'required|numeric|min:0|max:100',
            'scales.*.max_percentage' => 'required|numeric|min:0|max:100|gte:scales.*.min_percentage',
            'scales.*.description' => 'nullable|string',
            'scales.*.rank_order' => 'required|integer',
        ]);
        
        if ($validator->fails()) {
            return response()->json(['message' => 'Validation failed', 'errors' => $validator->errors()], 422);
        }

        $validated = $validator->validated();
        $studyYearId = $validated['study_year_id'];
        $scalesData = collect($validated['scales'])->sortByDesc('min_percentage')->values();

        // --- التعديل الرئيسي هنا: استخدام bccomp للمقارنة الدقيقة ---
        // التحقق من عدم وجود فجوات أو تداخلات
        for ($i = 0; $i < $scalesData->count() - 1; $i++) {
            $min_high = (string) $scalesData[$i]['min_percentage'];
            $max_low = (string) $scalesData[$i + 1]['max_percentage'];
            
            // bcsub يقوم بعملية الطرح بدقة، bccomp يقوم بالمقارنة بدقة
            // إذا كانت نتيجة الطرح أكبر من 0.01، فهناك فجوة
            if (bccomp(bcsub($min_high, $max_low, 4), '0.01', 4) === 1) {
                return response()->json(['message' => "There is a gap between '{$scalesData[$i+1]['grade_label']}' and '{$scalesData[$i]['grade_label']}'."], 422);
            }
        }

        DB::transaction(function () use ($studyYearId, $scalesData) {
            // حذف المقياس القديم بالكامل لهذه السنة الدراسية
            GradingScale::where('study_year_id', $studyYearId)->delete();
            
            // إضافة السجلات الجديدة
            foreach ($scalesData as $scaleItem) {
                GradingScale::create([
                    'study_year_id' => $studyYearId,
                    'grade_label' => $scaleItem['grade_label'],
                    'min_percentage' => $scaleItem['min_percentage'],
                    'max_percentage' => $scaleItem['max_percentage'],
                    'description' => $scaleItem['description'],
                    'rank_order' => $scaleItem['rank_order'],
                ]);
            }
        });

        return response()->json(['message' => 'Grading scale has been saved successfully.']);
    }
}
