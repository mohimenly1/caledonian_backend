<?php

namespace App\Http\Controllers;

use App\Models\AttendanceRecord;
use App\Models\DeductionPerHour;
use App\Models\Employee;
use App\Models\EmployeeType;
use App\Models\IssuedSalaryPerHour;
use App\Models\SalaryPerHour;
use Illuminate\Http\Request;

class SalaryPerHourController extends Controller
{



    public function destroyAttendanceRecord($employeeId)
    {
        $attendanceRecord = AttendanceRecord::where('employee_id', $employeeId)->first();

        if (!$attendanceRecord) {
            return response()->json(['message' => 'Attendance record not found'], 404);
        }

        $attendanceRecord->delete();
        return response()->json(['message' => 'Attendance record deleted successfully']);
    }

    public function destroySalaryPerHour($employeeId)
    {
        $salaryRecord = SalaryPerHour::where('employee_id', $employeeId)->first();

        if (!$salaryRecord) {
            return response()->json(['message' => 'Salary record not found'], 404);
        }

        $salaryRecord->delete();
        return response()->json(['message' => 'Salary record deleted successfully']);
    }

    public function destroy($id)
    {
        $issuedSalary = IssuedSalaryPerHour::find($id);

        if (!$issuedSalary) {
            return response()->json(['message' => 'Issued salary not found'], 404);
        }

        $issuedSalary->delete();
        return response()->json(['message' => 'Issued salary deleted successfully']);
    }
    // Fetch employees with employee_type_id corresponding to "معلم"

    // In EmployeeController.php
public function employeesWithSalaryRecords()
{
    $employees = Employee::whereHas('salariesPerHour')->get(); // Assuming there's a relation defined
    return response()->json($employees);
}

public function getTeachers()
{
    $teachers = Employee::whereHas('employeeType', function ($query) {
        $query->where('name', 'معلم');
    })
    ->with(['salaryPerHour' => function ($query) {
        $query->select('employee_id'); // Select only the employee_id to minimize data load
    },'department'])
    ->get()
    ->map(function ($teacher) {
        $teacher->has_salary_record = $teacher->salaryPerHour ? true : false;
        return $teacher;
    });

    return response()->json($teachers);
}

// In your controller (e.g., SalaryPerHourController)

public function show($employeeId)
{
    $salaryData = SalaryPerHour::where('employee_id', $employeeId)->get();
    
    if ($salaryData->isEmpty()) {
        return response()->json(['message' => 'No salary data found for this employee.'], 404);
    }

    return response()->json($salaryData);
}

    // Store SalaryPerHour data for an employee
    public function store(Request $request)
    {
        $validated = $request->validate([
            // 'employee_id' => 'required|exists:employees,id|unique:salaries_per_hours,employee_id',
            'employee_id' => 'required|exists:employees,id',
            'hourly_rate' => 'required|numeric',
            'mandatory_attendance_time' => 'required|integer',
            'num_classes' => 'required|integer',
            'class_rate' => 'nullable|numeric',
        ]);

        $salaryPerHour = SalaryPerHour::create($validated);

        return response()->json(['message' => 'Salary per hour saved successfully.', 'data' => $salaryPerHour]);
    }






    public function fetchIssuedSalaries(Request $request)
    {
        $perPage = $request->input('per_page', 10); // Default to 10 records per page
        $issuedSalaries = IssuedSalaryPerHour::with(['employee','deduction'])
            ->orderBy('issued_date', 'desc')
            ->paginate($perPage);

        return response()->json($issuedSalaries);
    }
















    public function checkDelay($employeeId)
    {
        $attendanceRecords = AttendanceRecord::where('employee_id', $employeeId)->get();
        $totalDelayMinutes = 0;
    
        foreach ($attendanceRecords as $attendance) {
            // Only count the delay if the employee arrived after the mandatory time
            if ($attendance->arrival_time > $attendance->mandatory_attendance_time) {
                // Calculate delay in minutes directly
                $delayMinutes = ($attendance->arrival_time - $attendance->mandatory_attendance_time) / 60; // Convert to minutes
                $totalDelayMinutes += $delayMinutes; // Accumulate total delay minutes
            }
        }
    
        return response()->json(['total_delay_minutes' => $totalDelayMinutes]);
    }
    
    





    // here start fucking hard work

    public function calculateIssuedSalariesPerHour($employeeId)
    {
        $salaryData = SalaryPerHour::where('employee_id', $employeeId)->firstOrFail();
        $attendanceRecords = AttendanceRecord::where('employee_id', $employeeId)->get();
        
        $totalHoursWorked = 0;
        $totalDelayMinutes = 0;
    
        // Calculate total work hours and delay
        foreach ($attendanceRecords as $attendance) {
            // Calculate total work minutes
            $workMinutes = ($attendance->departure_time - $attendance->arrival_time) / 60; // Convert to minutes
            $totalHoursWorked += max(0, $workMinutes); // Only add positive work minutes
    
            // Calculate delay if any
            if ($attendance->arrival_time > $salaryData->mandatory_attendance_time) {
                // Calculate delay minutes
                $delayMinutes = ($attendance->arrival_time - $salaryData->mandatory_attendance_time) / 60; // Convert to minutes
                $totalDelayMinutes += $delayMinutes; // Keep delay in minutes
            }
        }
    
        // Format the delay message
        $delayHours = floor($totalDelayMinutes / 60);
        $remainingMinutes = $totalDelayMinutes % 60;
        $delayMessage = "This employee was delayed in $delayHours hour(s) and $remainingMinutes minute(s)";
    
        // Get the selected deduction ID from the request
        $deductionId = request()->input('deduction_id');
        $deductionPerHour = DeductionPerHour::find($deductionId);
    
        // Initialize deduction amount
        $deductionAmount = 0;
        if ($deductionPerHour) {
            $deductionAmount = $deductionPerHour->amount; // Fixed deduction amount
        }
    
        // Calculate base salary
        $baseSalary = $totalHoursWorked * $salaryData->hourly_rate;
    
        // Calculate net salary
        $netSalary = $baseSalary - $deductionAmount;
    
        // Add bonuses and custom deductions
        $bonus = request()->input('bonus', 0);
        $customDeduction = request()->input('custom_deduction', 0);
        $netSalary += $bonus - $customDeduction;
    
        // Save to issued_salaries_per_hour
        IssuedSalaryPerHour::create([
            'employee_id' => $employeeId,
            'deduction_id' => $deductionId,
            'issued_date' => now(),
            'bonus' => $bonus,
            'currency' => 'LYD',
            'custom_deduction' => $customDeduction,
            'net_salary' => $netSalary,
            'base_salary' => $baseSalary, // Save base salary
            'delay_message' => $delayMessage, // Save delay message
            'note' => 'Salary calculated based on attendance and deductions',
        ]);
    
        return response()->json(['net_salary' => $netSalary, 'delay_message' => $delayMessage], 200);
    }
    
    
    
    








    public function calculateIssuedSalariesForMultipleEmployees(Request $request)
{
    $employeeIds = $request->input('employee_ids');
    $deductionId = $request->input('deduction_id');
    $bonus = $request->input('bonus', 0);
    $customDeduction = $request->input('custom_deduction', 0);

    $salaries = [];
    
    foreach ($employeeIds as $employeeId) {
        // Fetch salary data and attendance records for each employee
        $salaryData = SalaryPerHour::where('employee_id', $employeeId)->first();
        $attendanceRecords = AttendanceRecord::where('employee_id', $employeeId)->get();
        
        $totalHoursWorked = 0;
        $totalDelayMinutes = 0;
        
        // Calculate total work hours and delay
        foreach ($attendanceRecords as $attendance) {
            $workMinutes = ($attendance->departure_time - $attendance->arrival_time) / 60;
            $totalHoursWorked += max(0, $workMinutes);

            if ($attendance->arrival_time > $salaryData->mandatory_attendance_time) {
                $delayMinutes = ($attendance->arrival_time - $salaryData->mandatory_attendance_time) / 60;
                $totalDelayMinutes += $delayMinutes;
            }
        }

        $delayHours = floor($totalDelayMinutes / 60);
        $remainingMinutes = $totalDelayMinutes % 60;
        $delayMessage = "This employee was delayed in $delayHours hour(s) and $remainingMinutes minute(s)";
        
        $deductionPerHour = DeductionPerHour::find($deductionId);
        $deductionAmount = $deductionPerHour ? $deductionPerHour->amount : 0;
        
        $baseSalary = $totalHoursWorked * $salaryData->hourly_rate;
        $netSalary = $baseSalary - $deductionAmount + $bonus - $customDeduction;
        
        // Save to issued_salaries_per_hour
        $issuedSalary = IssuedSalaryPerHour::create([
            'employee_id' => $employeeId,
            'deduction_id' => $deductionId,
            'issued_date' => now(),
            'bonus' => $bonus,
            'currency' => 'LYD',
            'custom_deduction' => $customDeduction,
            'net_salary' => $netSalary,
            'base_salary' => $baseSalary,
            'delay_message' => $delayMessage,
            'note' => 'Salary calculated based on attendance and deductions',
        ]);

        $salaries[] = [
            'employee_id' => $employeeId,
            'net_salary' => $netSalary,
            'delay_message' => $delayMessage,
        ];
    }

    return response()->json(['salaries' => $salaries], 200);
}

    
}
