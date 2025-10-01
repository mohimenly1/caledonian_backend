<?php

namespace App\Http\Controllers;

use App\Models\Employee;
use App\Models\Section;
use Illuminate\Http\Request;

class EmployeeReportController extends Controller
{

    public function getSectionsByClass($classId)
{
    $sections = Section::where('class_id', $classId)->get();
    return response()->json($sections);
}
public function getFilteredEmployees(Request $request)
{
    $query = Employee::with(['subjects', 'classes', 'sections']); // Changed to load subjects

    // Apply filters based on the request parameters
    if ($request->filled('subject_id')) {
        $query->whereHas('subjects', function($q) use ($request) {
            $q->where('subject_id', $request->subject_id);
        });
    }

    if ($request->filled('class_id')) {
        $query->whereHas('classes', function($q) use ($request) {
            $q->where('class_id', $request->class_id);
        });
    }

    if ($request->filled('section_id')) {
        $query->whereHas('sections', function($q) use ($request) {
            $q->where('section_id', $request->section_id);
        });
    }

    if ($request->filled('gender')) {
        $query->where('gender', $request->gender);
    }

    // Apply pagination
    $employees = $query->paginate($request->input('per_page', 10));

    return response()->json($employees);
}

public function getFilteredEmployeesForReport(Request $request)
{
    $query = Employee::with(['subjects', 'classes', 'sections']); // Changed to load subjects
    
    // Apply filters...
    if ($request->filled('subject_id')) {
        $query->whereHas('subjects', function($q) use ($request) {
            $q->where('subject_id', $request->subject_id);
        });
    }
    if ($request->filled('class_id')) {
        $query->whereHas('classes', function($q) use ($request) {
            $q->where('class_id', $request->class_id);
        });
    }
    if ($request->filled('section_id')) {
        $query->whereHas('sections', function($q) use ($request) {
            $q->where('section_id', $request->section_id);
        });
    }
    if ($request->filled('gender')) {
        $query->where('gender', $request->gender);
    }
    
    // Fetch and sort employees by name
    $employees = $query->get()->sortBy('name');
    
    // Counts...
    $totalEmployees = Employee::count();
    $employeesWithSubjects = Employee::has('subjects')->count();
    $employeesWithoutSubjects = $totalEmployees - $employeesWithSubjects;
    
    return response()->json([
        'employees' => $employees->values(),
        'totalEmployees' => $totalEmployees,
        'employeesWithSubjects' => $employeesWithSubjects,
        'employeesWithoutSubjects' => $employeesWithoutSubjects,
    ]);
}
}
