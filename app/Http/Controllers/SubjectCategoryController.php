<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\SubjectCategory;
use App\ApiResponse;

class SubjectCategoryController extends Controller
{
    use ApiResponse;

    public function index()
    {
        $categories = SubjectCategory::with('subjects')->get();
        return $this->success($categories);
    }

    public function store(Request $request)
    {
        $validated = $request->validate(['name' => 'required|string']);
        $category = SubjectCategory::create($validated);
        return $this->success($category, 'Subject category created successfully');
    }

    public function show(SubjectCategory $subjectCategory)
    {
        return $this->success($subjectCategory->load('subjects'));
    }

    public function update(Request $request, SubjectCategory $subjectCategory)
    {
        $validated = $request->validate(['name' => 'required|string']);
        $subjectCategory->update($validated);
        return $this->success($subjectCategory, 'Subject category updated successfully');
    }

    public function destroy(SubjectCategory $subjectCategory)
    {
        $subjectCategory->delete();
        return $this->successMessage('Subject category deleted successfully');
    }
}
