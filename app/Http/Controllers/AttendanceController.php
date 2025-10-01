<?php

namespace App\Http\Controllers;

use App\Models\Attendance;
use App\Models\ClassRoom;
use App\Models\Section;
use App\Models\Student;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class AttendanceController extends Controller
{
    public function index(Request $request)
    {
        // Get the date from the request, or use the current date as the default
        $date = $request->query('date', date('Y-m-d'));
    
        // Fetch distinct class and section combinations along with the latest attendance date for that combination
        $attendances = Attendance::select('class_id', 'section_id', 'date')
            ->with(['class', 'section'])
            ->whereDate('date', $date)
            ->groupBy('class_id', 'section_id', 'date')
            ->orderBy('date', 'desc')
            ->get();
    
        return response()->json($attendances);
    }
    
    

    public function getAvailableDates()
{
    $dates = Attendance::select(DB::raw('DISTINCT date'))
        ->orderBy('date', 'desc')
        ->pluck('date');

    return response()->json($dates);
}



    public function store(Request $request)
    {
        $validatedData = $request->validate([
            'date' => 'required|date',
            'class_id' => 'required|exists:classes,id',
            'section_id' => 'required|exists:sections,id',
            'attendances' => 'required|array',
            'attendances.*.student_id' => 'required|exists:students,id',
            'attendances.*.status' => 'required|in:Present,Absent,Half day,Late,Has permission - Circumstance',
        ]);

        foreach ($validatedData['attendances'] as $attendanceData) {
            Attendance::create([
                'date' => $validatedData['date'],
                'class_id' => $validatedData['class_id'],
                'section_id' => $validatedData['section_id'],
                'student_id' => $attendanceData['student_id'],
                'status' => $attendanceData['status'],
            ]);
        }

        return response()->json(['message' => 'Attendance recorded successfully']);
    }

    public function show($class_id, $section_id, Request $request)
    {
        // Get the date from the request, or use the current date as default
        $date = $request->query('date', date('Y-m-d'));

        // Fetch all students' attendance records for the specific class, section, and date
        $attendances = Attendance::with(['student', 'class', 'section'])
            ->where('class_id', $class_id)
            ->where('section_id', $section_id)
            ->whereDate('date', $date)
            ->get();

        return response()->json($attendances);
    }
}

