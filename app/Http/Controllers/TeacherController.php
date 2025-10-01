<?php

// app/Http/Controllers/TeacherController.php
namespace App\Http\Controllers;

use App\Models\Employee;
use App\Models\Timetable;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Http\Request;


class TeacherController extends Controller
{
    public function index(Request $request)
    {
              // ونختار فقط حقلي الاسم والمعرّف (id, name) لأنها فقط ما تحتاجه الواجهة
              $teachers = User::where('user_type', 'teacher')->get(['id', 'name']);

              return response()->json($teachers);
    }
    public function getTimetable()
{
    $teacher = Auth::user();

    if ($teacher->user_type !== 'teacher') {
        return response()->json([
            'success' => false,
            'message' => 'User is not a teacher'
        ], 403);
    }
    
    if (!$teacher) {
        return response()->json([
            'success' => false,
            'message' => 'Teacher not found'
        ], 404);
    }

    $timetable = Timetable::where('teacher_id', $teacher->id)
        ->with(['subject', 'class', 'section'])
        ->orderByRaw("FIELD(day_of_week, 'monday', 'tuesday', 'wednesday', 'thursday', 'friday', 'saturday', 'sunday')")
        ->orderBy('start_time')
        ->get();

    return response()->json([
        'success' => true,
        'timetable' => $timetable
    ]);
}
}