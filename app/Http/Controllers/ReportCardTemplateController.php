<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\ReportCardTemplate;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\Validator;

class ReportCardTemplateController extends Controller
{
    /**
     * عرض جميع قوالب الشهادات المتاحة.
     */
    public function index()
    {
        $templates = ReportCardTemplate::orderBy('name')->get();
        return response()->json($templates);
    }

    /**
     * إنشاء قالب شهادة جديد.
     */
    public function store(Request $request)
    {
        $data = $request->all();
        
        if ($request->has('layout_options') && is_string($request->layout_options)) {
            $data['layout_options'] = json_decode($request->layout_options, true);
        }

        // --- التعديل الرئيسي هنا: إضافة جميع الخيارات الجديدة لقواعد التحقق ---
        $validator = Validator::make($data, [
            'name' => 'required|string|max:255|unique:report_card_templates,name',
            'logo' => 'nullable|image|mimes:png,jpg,jpeg|max:2048',
            'type' => ['sometimes', 'required', Rule::in(['report_card', 'transcript', 'progress_report'])],
            'description' => 'nullable|string',
            'header_content' => 'nullable|string',
            'footer_content' => 'nullable|string',
            'layout_options' => 'sometimes|array',
            'layout_options.show_gpa' => 'sometimes|boolean',
            'layout_options.show_attendance' => 'sometimes|boolean',
            'layout_options.show_behavioral_evaluation' => 'sometimes|boolean',
            'layout_options.show_teacher_remarks' => 'sometimes|boolean',
            'layout_options.show_grading_policy' => 'sometimes|boolean',
            'layout_options.show_grading_scale_key' => 'sometimes|boolean',
            'layout_options.show_term1_grades' => 'sometimes|boolean',
            'layout_options.show_term2_grades' => 'sometimes|boolean',
            'layout_options.show_final_year_grade' => 'sometimes|boolean',
            'layout_options.show_subject_status' => 'sometimes|boolean',
            'layout_options.show_min_passing_score' => 'sometimes|boolean',
            'layout_options.show_overall_summary' => 'sometimes|boolean',
        ]);

        if ($validator->fails()) {
            return response()->json(['message' => 'Validation failed', 'errors' => $validator->errors()], 422);
        }

        $validatedData = $validator->validated();
        
        if ($request->hasFile('logo')) {
            $validatedData['logo_path'] = $request->file('logo')->store('report-card-logos', 'public');
        }

        $template = ReportCardTemplate::create($validatedData);

        return response()->json([
            'message' => 'Report card template created successfully.',
            'template' => $template
        ], 201);
    }

    /**
     * عرض تفاصيل قالب محدد.
     */
    public function show(ReportCardTemplate $reportCardTemplate)
    {
        return response()->json($reportCardTemplate);
    }

    /**
     * تحديث قالب شهادة موجود.
     */
    public function update(Request $request, ReportCardTemplate $reportCardTemplate)
    {
        $data = $request->all();
        if ($request->has('layout_options') && is_string($request->layout_options)) {
            $data['layout_options'] = json_decode($request->layout_options, true);
        }

        // --- التعديل الرئيسي هنا: إضافة جميع الخيارات الجديدة لقواعد التحقق ---
        $validator = Validator::make($data, [
            'name' => ['sometimes', 'required', 'string', 'max:255', Rule::unique('report_card_templates', 'name')->ignore($reportCardTemplate->id)],
            'logo' => 'nullable|image|mimes:png,jpg,jpeg|max:2048',
            'type' => ['sometimes', 'required', Rule::in(['report_card', 'transcript', 'progress_report'])],
            'description' => 'nullable|string',
            'header_content' => 'nullable|string',
            'footer_content' => 'nullable|string',
            'layout_options' => 'sometimes|array',
            'layout_options.show_gpa' => 'sometimes|boolean',
            'layout_options.show_attendance' => 'sometimes|boolean',
            'layout_options.show_behavioral_evaluation' => 'sometimes|boolean',
            'layout_options.show_teacher_remarks' => 'sometimes|boolean',
            'layout_options.show_grading_policy' => 'sometimes|boolean',
            'layout_options.show_grading_scale_key' => 'sometimes|boolean',
            'layout_options.show_term1_grades' => 'sometimes|boolean',
            'layout_options.show_term2_grades' => 'sometimes|boolean',
            'layout_options.show_final_year_grade' => 'sometimes|boolean',
            'layout_options.show_subject_status' => 'sometimes|boolean',
            'layout_options.show_min_passing_score' => 'sometimes|boolean',
            'layout_options.show_overall_summary' => 'sometimes|boolean',
        ]);

        if ($validator->fails()) {
            return response()->json(['message' => 'Validation failed', 'errors' => $validator->errors()], 422);
        }

        $validatedData = $validator->validated();

        if ($request->hasFile('logo')) {
            $validatedData['logo_path'] = $request->file('logo')->store('report-card-logos', 'public');
        }

        $reportCardTemplate->update($validatedData);

        return response()->json([
            'message' => 'Report card template updated successfully.',
            'template' => $reportCardTemplate
        ]);
    }

    /**
     * حذف قالب شهادة.
     */
    public function destroy(ReportCardTemplate $reportCardTemplate)
    {
        $reportCardTemplate->delete();
        return response()->json(['message' => 'Template deleted successfully.']);
    }
}
