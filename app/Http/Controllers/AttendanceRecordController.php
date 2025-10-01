<?php

namespace App\Http\Controllers;

use App\Models\AttendanceRecord;
use App\Models\Employee;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class AttendanceRecordController extends Controller
{
    /**
     * Store a new attendance record.
     *
     * @param \Illuminate\Http\Request $request
     * @return \Illuminate\Http\JsonResponse
     */


     public function fetchAttendanceRecords(Request $request, $employeeId)
{
    $attendanceRecords = AttendanceRecord::where('employee_id', $employeeId)
        ->orderBy('date', 'desc')
        ->paginate(10); // Adjust the number for pagination as needed

    return response()->json($attendanceRecords);
}

    public function store(Request $request)
    {
        // Validate the request data
        $validator = Validator::make($request->all(), [
            'employee_id' => 'required|exists:employees,id',
            'arrival_time' => 'required|integer|min:0', // Total minutes from midnight (e.g., 540 for 9:00 AM)
            'departure_time' => 'required|integer|min:0|gte:arrival_time', // Ensure departure is after arrival
            'date' => 'required|date',
        ]);
    
        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors(),
            ], 422);
        }
    
        try {
            // Format the date to 'Y-m-d' format
            $formattedDate = \Carbon\Carbon::parse($request->date)->format('Y-m-d');
    
            // Create the attendance record
            $attendance = AttendanceRecord::create([
                'employee_id' => $request->employee_id,
                'arrival_time' => $request->arrival_time,
                'departure_time' => $request->departure_time,
                'date' => $formattedDate,
            ]);
    
            return response()->json([
                'success' => true,
                'message' => 'Attendance record created successfully.',
                'data' => $attendance,
            ], 201);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to create attendance record.',
            ], 500);
        }
    }
    
}
