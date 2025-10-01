<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Employee;
use App\Models\Subject;


class TeacherSubjectController extends Controller
{
    public function index(Employee $teacher)
    {
        $subjects = Subject::all();
        $teacherSubjects = $teacher->subjects()->pluck('subjects.id')->toArray();

        return response()->json([
            'subjects' => $subjects,
            'teacher_subjects' => $teacherSubjects
        ]);
    }

    public function update(Request $request, Employee $teacher)
    {
        $request->validate([
            'subject_ids' => 'required|array',
            'subject_ids.*' => 'exists:subjects,id'
        ]);

        $teacher->subjects()->sync($request->subject_ids);

        return response()->json([
            'message' => 'Teacher subjects updated successfully',
            'teacher' => $teacher->load('subjects')
        ]);
    }

    
}