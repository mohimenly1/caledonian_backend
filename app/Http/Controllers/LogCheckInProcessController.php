<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Employee;
use App\Models\LogCheckInProcess;
use Carbon\Carbon;

class LogCheckInProcessController extends Controller
{
    public function checkInOrOut(Request $request)
    {
        $request->validate([
            'pin' => 'required|exists:employees,pin',
        ]);

        $employee = Employee::where('pin', $request->pin)->firstOrFail();

        // Check if the employee has already checked in and not checked out
        $lastLog = LogCheckInProcess::where('employee_id', $employee->id)
            ->orderBy('created_at', 'desc')
            ->first();

        if ($lastLog && is_null($lastLog->check_out_time)) {
            // If the last log exists and check_out_time is null, then perform check-out
            $lastLog->update([
                'check_out_time' => Carbon::now('Africa/Tripoli'),
            ]);

            return response()->json($lastLog, 200);
        } else {
            // If there's no log or the last log has a check_out_time, then perform check-in
            $log = LogCheckInProcess::create([
                'employee_id' => $employee->id,
                'check_in_time' => Carbon::now('Africa/Tripoli'),
            ]);

            return response()->json($log, 201);
        }
    }

    public function index()
    {
        $logs = LogCheckInProcess::with('employee')->paginate(10); // Adjust the number as needed
        return response()->json($logs);
    }
}
