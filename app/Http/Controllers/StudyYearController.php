<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\StudyYear;
use Illuminate\Http\Request;
use App\ApiResponse; // Assuming you have this trait for the success method

class StudyYearController extends Controller
{
    use ApiResponse;

    public function index()
    {
        $studyYears = StudyYear::all();
        return $this->success($studyYears);
    }

    public function store(Request $request)
    {
        // --- THE FIX IS HERE ---
        $validated = $request->validate([
            'name' => 'required|string|unique:study_years,name',
            'is_active' => 'sometimes|boolean',
        ]);

        $studyYear = StudyYear::create($validated);
        return $this->success($studyYear, 'Study year created successfully');
    }

    public function show(StudyYear $studyYear)
    {
        return $this->success($studyYear);
    }

    public function update(Request $request, StudyYear $studyYear)
    {
        // --- THE FIX IS HERE ---
        $validated = $request->validate([
            'name' => 'required|string|unique:study_years,name,' . $studyYear->id,
            'is_active' => 'sometimes|boolean',
        ]);

        $studyYear->update($validated);
        return $this->success($studyYear, 'Study year updated successfully');
    }

    public function destroy(StudyYear $studyYear)
    {
        $studyYear->delete();
    
        return response()->json([
            'message' => 'Study year deleted successfully',
        ]);
    }
}
