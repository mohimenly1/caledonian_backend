<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\StudentEnrollment;
use App\ApiResponse;

class StudentEnrollmentController extends Controller
{
    use ApiResponse;

    public function index()
    {
        $enrollments = StudentEnrollment::with(['student', 'class', 'section', 'studyYear'])->get();
        return $this->success($enrollments);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'student_id' => 'required|exists:students,id',
            'class_id' => 'required|exists:classes,id',
            'section_id' => 'required|exists:sections,id',
            'study_year_id' => 'required|exists:study_years,id',
        ]);

        $enrollment = StudentEnrollment::create($validated);
        return $this->success($enrollment, 'Student enrolled successfully');
    }

    public function show(StudentEnrollment $studentEnrollment)
    {
        return $this->success($studentEnrollment->load(['student', 'class', 'section', 'studyYear']));
    }

    public function update(Request $request, StudentEnrollment $studentEnrollment)
    {
        $validated = $request->validate([
            'student_id' => 'required|exists:students,id',
            'class_id' => 'required|exists:classrooms,id',
            'section_id' => 'required|exists:sections,id',
            'study_year_id' => 'required|exists:study_years,id',
        ]);

        $studentEnrollment->update($validated);
        return $this->success($studentEnrollment, 'Enrollment updated successfully');
    }

    public function destroy(StudentEnrollment $studentEnrollment)
    {
        $studentEnrollment->delete();
        return $this->successMessage('Enrollment deleted successfully');
    }
}
