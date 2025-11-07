<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Student;
use App\Models\Term;
use App\Models\CourseOffering;
use App\Models\Subject;
use Illuminate\Support\Facades\Log;

class EduraReportCardController extends Controller
{
    /**
     * جلب البيانات الأساسية لإنشاء شهادة الطالب (Report Card) لنظام Edura
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function getStudentReportData(Request $request)
    {
        // 1. التحقق من المدخلات
        $validated = $request->validate([
            'student_id' => 'required|integer|exists:students,id',
            'term_id' => 'required|integer|exists:terms,id',
            'study_year_id' => 'required|integer|exists:study_years,id',
            'class_id' => 'required|integer|exists:classes,id',
            'section_id' => 'nullable|integer|exists:sections,id',
        ]);

        try {
            // 2. جلب البيانات الأساسية للطالب والسنة والفصل الدراسي
            $student = Student::select('id', 'name', 'arabic_name', 'class_id', 'section_id')
                            ->with(['class:id,name', 'section:id,name']) // 'class' هو اسم العلاقة في مودل Student
                            ->findOrFail($validated['student_id']);
                            
            $term = Term::select('id', 'name', 'study_year_id')
                        ->with('studyYear:id,name')
                        ->findOrFail($validated['term_id']);
            
            // 3. جلب المواد (CourseOfferings) التي يدرسها الطالب
            $courseOfferingsQuery = CourseOffering::where('study_year_id', $validated['study_year_id'])
                                        ->where('class_id', $validated['class_id']);

            // فلترة المواد بناءً على الشعبة (إذا كانت المادة مخصصة لشعبة)
            // أو جلب المواد المخصصة للفصل بالكامل (section_id is null)
            $sectionId = $validated['section_id'];
            $courseOfferingsQuery->where(function ($query) use ($sectionId) {
                $query->whereNull('section_id'); // مواد عامة للفصل
                if ($sectionId) {
                    $query->orWhere('section_id', $sectionId); // مواد مخصصة لهذه الشعبة
                }
            });

            // جلب المواد مع أسماء المواد
            $subjects = $courseOfferingsQuery
                            ->with('subject:id,name,code')
                            ->get()
                            ->map(function ($offering) {
                                // إرجاع المواد فقط
                                if ($offering->subject) {
                                     return [
                                         'id' => $offering->subject->id,
                                         'name' => $offering->subject->name,
                                         'code' => $offering->subject->code,
                                     ];
                                }
                                return null;
                            })
                            ->filter() // إزالة أي نتائج null
                            ->unique('id') // ضمان عدم تكرار المادة
                            ->values(); // إعادة ترتيب المفاتيح

            // 4. تجميع البيانات للإرسال
            $reportData = [
                'student_id' => $student->id,
                'student_name' => $student->name,
                'class_name' => $student->class->name ?? 'غير محدد',
                'section_name' => $student->section->name ?? null,
                'study_year_name' => $term->studyYear->name ?? 'غير محدد',
                'term_name' => $term->name,
                'subjects' => $subjects, // قائمة المواد التي سيتم رصد درجاتها
            ];

            return response()->json($reportData);

        } catch (\Exception $e) {
            Log::error("[EduraReportCardData] فشل جلب بيانات الشهادة:", [
                'student_id' => $validated['student_id'],
                'term_id' => $validated['term_id'],
                'error' => $e->getMessage()
            ]);
            return response()->json(['error' => 'حدث خطأ أثناء جلب بيانات الشهادة.'], 500);
        }
    }
}
