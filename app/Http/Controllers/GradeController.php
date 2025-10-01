<?php

namespace App\Http\Controllers;

use App\ApiResponse;
use App\Models\Grade;
use Illuminate\Http\Request;

class GradeController extends Controller
{
    use ApiResponse;

    public function index()
    {
        $grades = Grade::with(['student.class', 'student.section', 'subject', 'term', 'teacher', 'exam'])->get();
        return $this->success($grades);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'student_id' => 'required|exists:students,id',
            'subject_id' => 'required|exists:subjects,id',
            'term_id' => 'required|exists:terms,id',
            'teacher_id' => 'required|exists:users,id',
            'exam_id' => 'nullable|exists:exams,id',
            'score' => 'required|numeric|min:0|max:100',
            'remarks' => 'nullable|string',
        ]);

        $grade = Grade::create($validated);
        return $this->success($grade, 'Grade recorded successfully');
    }

    public function show(Grade $grade)
    {
        return $this->success($grade->load(['student.class', 'student.section', 'subject', 'term', 'teacher', 'exam']));
    }

    public function update(Request $request, Grade $grade)
    {
        $validated = $request->validate([
            'student_id' => 'required|exists:students,id',
            'subject_id' => 'required|exists:subjects,id',
            'term_id' => 'required|exists:terms,id',
            'teacher_id' => 'required|exists:users,id',
            'exam_id' => 'nullable|exists:exams,id',
            'score' => 'required|numeric|min:0|max:100',
            'remarks' => 'nullable|string',
        ]);

        $grade->update($validated);
        return $this->success($grade, 'Grade updated successfully');
    }

    public function destroy(Grade $grade)
    {
        $grade->delete();
        return $this->successMessage('Grade deleted successfully');
    }
}
