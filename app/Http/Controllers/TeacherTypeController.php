<?php

namespace App\Http\Controllers;

use App\Models\ClassRoom;
use App\Models\TeacherType;
use Illuminate\Http\Request;
use App\ApiResponse;


class TeacherTypeController extends Controller
{
    use ApiResponse;

    public function index()
    {
        return $this->success(TeacherType::all());
    }

    public function store(Request $request)
    {
        $validated = $request->validate(['type' => 'required|string']);
        $type = TeacherType::create($validated);
        return $this->success($type, "Teacher type created successfully");
    }

    public function show(TeacherType $teacherType)
    {
        return $this->success($teacherType);
    }

    public function update(Request $request, TeacherType $teacherType)
    {
        $validated = $request->validate(['type' => 'required|string']);
        $teacherType->update($validated);
        return $this->success($teacherType, "Teacher type updated successfully");
    }

    public function destroy(TeacherType $teacherType)
    {
        $teacherType->delete();
        return $this->successMessage("Teacher type deleted successfully");
    }
}
