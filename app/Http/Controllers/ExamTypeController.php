<?php

namespace App\Http\Controllers;

use App\Models\ExamType;
use Illuminate\Http\Request;
use App\ApiResponse;

class ExamTypeController extends Controller
{
    use ApiResponse;

    public function index()
    {
        $types = ExamType::withCount('exams')->get();
        return $this->success($types);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255|unique:exam_types,name',
            'description' => 'nullable|string|max:1000',
        ]);

        $type = ExamType::create($validated);
        return $this->success($type, 'Exam type created successfully');
    }

    public function show(ExamType $examType)
    {
        return $this->success($examType->loadCount('exams'));
    }

    public function update(Request $request, ExamType $examType)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255|unique:exam_types,name,' . $examType->id,
            'description' => 'nullable|string|max:1000',
        ]);

        $examType->update($validated);
        return $this->success($examType, 'Exam type updated successfully');
    }

    public function destroy(ExamType $examType)
    {
        $examType->delete();
        return $this->successMessage('Exam type deleted successfully');
    }
}
