<?php

namespace App\Http\Controllers;

use App\Models\Student;
use App\Models\StudentAttendance;
use App\Models\StudentAttendanceRecord;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class StudentAttendanceRecordController extends Controller
{

    public function cancel_attendance_for_one_student(Request $request)
    {
        $request->validate([
            'student_id' => 'required|exists:students,id',
        ]);

        $attendanceRecord = StudentAttendanceRecord::where('student_id', $request->student_id)
            ->whereDate('created_at', Carbon::today())
            ->first();

        if ($attendanceRecord) {
            $attendanceRecord->delete();
            return response()->json(['message' => 'Attendance cancelled successfully']);
        }

        return response()->json(['message' => 'No attendance record found for today'], 404);
    }

    public function save_attendance_for_selected_students(Request $request)
    {
        $request->validate([
            'class_id' => 'required',
            'section_id' => 'nullable',
            'selectedStudents' => 'required',
            'status' => 'required'

        ]);
        foreach ($request->selectedStudents as $item) {

            StudentAttendanceRecord::create([
                'student_id' => $item,
                'class_id' => $request->class_id,
                'section_id' => $request->section_id,
                'user_id' => Auth::user()->id,
                'record_state' => $request->status,


            ]);
        }

        return response()->json(['message' => 'Done'], 200);
    }


    public function save_attendance_for_one_student(Request $request)
    {
        $request->validate([
            'class_id' => 'required',
            'section_id' => 'nullable',
            'student_id' => 'required',

        ]);

        StudentAttendanceRecord::create([
            'student_id' => $request->student_id,
            'class_id' => $request->class_id,
            'section_id' => $request->section_id,
            'user_id' => Auth::user()->id,
            'record_state' => 'Attendance List',


        ]);
        return response()->json(['message' => 'Done'], 200);
    }
    public function students_for_attendance_list(Request $request)
    {
    
        $request->validate([
            'class_id' => 'required',
            'section_id' => 'nullable',
            'day' => 'required'
        ]);
        $today = $request->day;

        $query = Student::with(['class', 'section'])->where('class_id', '=', $request->class_id);

        if ($request->section_id) {

            $query->where('section_id', '=', $request->section_id);
        }
        $query2 = Student::with(['class', 'section', 'student_attendance_records' => function ($querytoday) use ($today) {
            $querytoday->whereDate('created_at', $today); // جلب السجلات الخاصة بتاريخ اليوم فقط
        }])->where('class_id', '=', $request->class_id);



        if ($request->section_id) {

            $query2->where('section_id', '=', $request->section_id);
        }
        $query->whereDoesntHave('student_attendance_records', function ($query) use ($today) {
            $query->whereDate('created_at', $today);
        });
        $query2->whereHas('student_attendance_records', function ($query) use ($today) {
            $query->whereDate('created_at', $today);
        });

        $students = $query->get();
        $students2 = $query2->get();
        return response()->json([
            'students' => $students,
            'students2' => $students2

        ], 201);
    }
    public function students_for_attendance(Request $request)
    {
   
        $request->validate([
            'class_id' => 'required',
            'section_id' => 'nullable',
        ]);
        $today = Carbon::today();

        $query = Student::with(['class', 'section'])->where('class_id', '=', $request->class_id);

        if ($request->section_id) {

            $query->where('section_id', '=', $request->section_id);
        }
        $query2 = Student::with(['class', 'section', 'student_attendance_records' => function ($querytoday) use ($today) {
            $querytoday->whereDate('created_at', $today); // جلب السجلات الخاصة بتاريخ اليوم فقط
        }])->where('class_id', '=', $request->class_id);



        if ($request->section_id) {

            $query2->where('section_id', '=', $request->section_id);
        }
        $query->whereDoesntHave('student_attendance_records', function ($query) use ($today) {
            $query->whereDate('created_at', $today);
        });
        $query2->whereHas('student_attendance_records', function ($query) use ($today) {
            $query->whereDate('created_at', $today);
        });

        $students = $query->get();
        $students2 = $query2->get();
        return response()->json([
            'students' => $students,
            'students2' => $students2

        ], 201);
    }
}
