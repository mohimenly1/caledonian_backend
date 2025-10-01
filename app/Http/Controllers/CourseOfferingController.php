<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\ClassRoom;
use App\Models\CourseOffering;
use Illuminate\Http\Request;

class CourseOfferingController extends Controller
{
    /**
     * عرض قائمة بالمقررات الدراسية المتاحة مع إمكانية الفلترة.
     * GET /api/course-offerings
     */
    public function index(Request $request)
    {
        $request->validate([
            'study_year_id' => 'sometimes|required|exists:study_years,id',
            'class_id' => 'sometimes|required|exists:classes,id',
            'subject_id' => 'sometimes|required|exists:subjects,id',
            'per_page' => 'sometimes|integer|min:1'
        ]);

        $query = CourseOffering::with(['subject:id,name', 'schoolClass:id,name', 'section:id,name', 'studyYear:id,name']);

        if ($request->filled('study_year_id')) {
            $query->where('study_year_id', $request->study_year_id);
        }
        if ($request->filled('class_id')) {
            $query->where('class_id', $request->class_id);
        }
        if ($request->filled('subject_id')) {
            $query->where('subject_id', $request->subject_id);
        }

        $offerings = $query->orderBy('id', 'desc')->paginate($request->input('per_page', 20));

        return response()->json($offerings);
    }

        /**
     * جلب المقررات الدراسية المسندة لصف معين في سنة دراسية معينة.
     * GET /api/classes/{class}/course-offerings
     */
    public function getOfferingsForClass(Request $request, ClassRoom $class)
    {
        $validated = $request->validate([
            'study_year_id' => 'required|exists:study_years,id',
            'section_id' => 'required|exists:sections,id', // <-- الإضافة هنا
        ]);

        $offerings = CourseOffering::where('class_id', $class->id)
            ->where('study_year_id', $validated['study_year_id'])
            ->where('section_id', $validated['section_id']) // <-- الإضافة هنا
            ->with('subject:id,name')
            ->get();
            
        return response()->json($offerings);
    }



    /**
     * مزامنة (إضافة/حذف) المقررات الدراسية لصف معين في سنة دراسية معينة.
     * POST /api/classes/{class}/course-offerings
     */
    public function syncOfferingsForClass(Request $request, ClassRoom $class)
    {
        $validated = $request->validate([
            'study_year_id' => 'required|exists:study_years,id',
            'section_id' => 'required|exists:sections,id', // <-- الإضافة هنا
            'subject_ids' => 'present|array',
            'subject_ids.*' => 'exists:subjects,id',
        ]);

        $studyYearId = $validated['study_year_id'];
        $sectionId = $validated['section_id'];
        $subjectIds = $validated['subject_ids'];
        
        // Get the IDs of the subjects that were successfully created or found
        $syncedSubjectIds = [];

        foreach ($subjectIds as $subjectId) {
            $offering = CourseOffering::updateOrCreate(
                [
                    'class_id' => $class->id,
                    'study_year_id' => $studyYearId,
                    'section_id' => $sectionId, // <-- الإضافة هنا
                    'subject_id' => $subjectId,
                ]
            );
            $syncedSubjectIds[] = $offering->subject_id;
        }

        // حذف المقررات التي لم تعد مسندة لهذا القسم تحديدًا
        CourseOffering::where('class_id', $class->id)
            ->where('study_year_id', $studyYearId)
            ->where('section_id', $sectionId) // <-- الإضافة هنا
            ->whereNotIn('subject_id', $syncedSubjectIds)
            ->delete();

        return response()->json(['message' => 'Course offerings for the section have been updated successfully.']);
    }
}