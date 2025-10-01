<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\TeacherType;
use App\ApiResponse;

class TeachersTypeController extends Controller
{
    use ApiResponse;

    public function index()
    {
        $types = TeacherType::all();
        return $this->success($types);
    }

    public function store(Request $request)
    {
        $validated = $request->validate(['type' => 'required|string']);
        $type = TeacherType::create($validated);
        return $this->success($type, 'Teacher type created successfully');
    }

    public function show(TeacherType $teacherType)
    {
        return $this->success($teacherType);
    }

    public function update(Request $request, TeacherType $teacherType)
    {
        $validated = $request->validate(['type' => 'required|string']);
        $teacherType->update($validated);
        return $this->success($teacherType, 'Teacher type updated successfully');
    }

    public function destroy(TeacherType $teacherType)
    {
        $teacherType->delete();
        return $this->successMessage('Teacher type deleted successfully');
    }
}
