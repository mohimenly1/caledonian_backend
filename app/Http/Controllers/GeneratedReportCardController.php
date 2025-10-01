<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\GeneratedReportCard;
use App\Models\Term;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class GeneratedReportCardController extends Controller
{
    /**
     * تسجيل وتحديث عملية توليد مجموعة من الشهادات دفعة واحدة.
     * POST /api/generated-report-cards/log
     */
    public function logGeneration(Request $request)
    {
        $validated = $request->validate([
            'term_id' => 'required|exists:terms,id',
            'report_card_template_id' => 'required|exists:report_card_templates,id',
            'report_data' => 'required|array|min:1',
            'report_data.*.student_info.id' => 'required|exists:students,id',
        ]);

        $term = Term::find($validated['term_id']);
        if (!$term) {
            return response()->json(['message' => 'The provided term does not exist.'], 404);
        }
        $studyYearId = $term->study_year_id;
        $generatedByUserId = Auth::id();
        $now = now();

        DB::transaction(function () use ($validated, $generatedByUserId, $now, $studyYearId) {
            foreach ($validated['report_data'] as $studentReport) {
                
                // --- التعديل الرئيسي هنا: منطق الترقيم الديناميكي ---
                
                // البحث عن سجل موجود لنفس الطالب والفصل الدراسي والقالب
                $existingRecord = GeneratedReportCard::where([
                    'student_id' => $studentReport['student_info']['id'],
                    'term_id' => $validated['term_id'],
                    'report_card_template_id' => $validated['report_card_template_id'],
                ])->first();

                // تحديد رقم الإصدار الجديد
                $newVersion = $existingRecord ? $existingRecord->version + 1 : 1;

                // استخدام updateOrCreate لتحديث السجل أو إنشائه
                GeneratedReportCard::updateOrCreate(
                    [
                        'student_id' => $studentReport['student_info']['id'],
                        'term_id' => $validated['term_id'],
                        'report_card_template_id' => $validated['report_card_template_id'],
                    ],
                    [
                        'study_year_id' => $studyYearId,
                        'report_type' => 'report_card',
                        'generation_date' => $now,
                        'generated_by_user_id' => $generatedByUserId,
                        'data_snapshot_json' => json_encode($studentReport),
                        'version' => $newVersion, // استخدام رقم الإصدار المحسوب
                        'status' => 'Generated',
                        'updated_at' => $now,
                    ]
                );
            }
        });

        return response()->json(['message' => 'Report generation event logged successfully.'], 200);
    }
}
