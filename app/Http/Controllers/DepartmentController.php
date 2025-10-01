<?php

namespace App\Http\Controllers;

use App\Models\Department;
use Illuminate\Http\Request;

class DepartmentController extends Controller
{
    public function index()
    {
        $departments = Department::all();
        return response()->json($departments);
    }

    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
        ]);

        $department = Department::create($request->all());
        return response()->json($department, 201);
    }

    public function update(Request $request, $id)
    {
        $request->validate([
            'name' => 'required|string|max:255',
        ]);

        $department = Department::find($id);
        if ($department) {
            $department->update($request->all());
            return response()->json($department, 200);
        }
        return response()->json(['message' => 'Department not found'], 404);
    }

    public function destroy($id)
    {
        $department = Department::find($id);
        if ($department) {
            $department->delete();
            return response()->json(['message' => 'Department deleted successfully'], 200);
        }
        return response()->json(['message' => 'Department not found'], 404);
    }
}
