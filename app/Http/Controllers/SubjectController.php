<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Subject;
use App\ApiResponse;

class SubjectController extends Controller
{
    use ApiResponse;

    public function index()
    {

        $subjects = Subject::with('category')->get();
        return $this->success($subjects);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string',
            'code' => 'required|string|unique:subjects',
            'description' => 'nullable|string',
            'subject_category_id' => 'nullable|exists:subject_categories,id',
        ]);

        $subject = Subject::create($validated);
        return $this->success($subject, 'Subject created successfully');
    }

    public function show(Subject $subject)
    {
        return $this->success($subject->load('category'));
    }

    public function update(Request $request, Subject $subject)
    {
        $validated = $request->validate([
            'name' => 'required|string',
            'code' => 'required|string|unique:subjects,code,' . $subject->id,
            'description' => 'nullable|string',
            'subject_category_id' => 'nullable|exists:subject_categories,id',
        ]);

        $subject->update($validated);
        return $this->success($subject, 'Subject updated successfully');
    }

    public function destroy(Subject $subject)
    {
        $subject->delete();
        return $this->successMessage('Subject deleted successfully');
    }
}
