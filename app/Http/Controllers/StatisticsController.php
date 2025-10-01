<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\Employee;
use App\Models\LogCheckInProcess;
use App\Models\Treasury;
use App\Models\Student;
use App\Models\TreasuryTransaction;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;

class StatisticsController extends Controller
{
    // Get the total number of users
    public function getTotalUsers(): JsonResponse
    {
        $userCount = User::count();
        return response()->json(['total_users' => $userCount]);
    }

    // Get the total number of employees
    public function getTotalEmployees(): JsonResponse
    {
        $employeeCount = Employee::count();
        $employeeData = Employee::get();
        return response()->json(['total_employees' => $employeeCount,'employeeData' => $employeeData]);
    }

    // Get the balance of the manual treasury
    public function getManualTreasuryBalance(): JsonResponse
    {
        $balance = Treasury::where('type', 'manual')->sum('balance');
        return response()->json(['manual_treasury_balance' => $balance]);
    }

    // Get the total number of students
    public function getTotalStudents(): JsonResponse
    {
        $studentCount = Student::count();
        return response()->json(['total_students' => $studentCount]);
    }

    // Get the count of students for each class
    public function getStudentsPerClass(): JsonResponse
    {
        $studentsPerClass = Student::select('class_id')
            ->selectRaw('COUNT(*) as student_count')
            ->groupBy('class_id')
            ->with('class:id,name') // Ensure 'class' relation returns 'id' and 'name'
            ->get()
            ->map(function ($item) {
                return [
                    'class_name' => $item->class ? $item->class->name : 'No Class',
                    'student_count' => $item->student_count,
                ];
            });
    
        return response()->json(['students_per_class' => $studentsPerClass]);
    }
    

    // Add this method to your StatisticsController
public function getTreasuryTransactions(): JsonResponse
{
    $transactions = TreasuryTransaction::select('transaction_type')
        ->selectRaw('SUM(amount) as total_amount')
        ->groupBy('transaction_type')
        ->get();

    return response()->json(['transactions' => $transactions]);
}



public function getLastCheckIn(): JsonResponse
{
    $lastCheckIn = LogCheckInProcess::with('employee')
        ->orderBy('check_in_time', 'desc')
        ->first();

    if ($lastCheckIn) {
        $employeeName = $lastCheckIn->employee->name;
        $checkInTime = Carbon::parse($lastCheckIn->check_in_time)->format('Y-m-d h:iA');
        $checkOutTime = $lastCheckIn->check_out_time 
            ? Carbon::parse($lastCheckIn->check_out_time)->format('Y-m-d h:iA')
            : 'Not Checked Out';

        return response()->json([
            'employee_name' => $employeeName,
            'check_in_time' => $checkInTime,
            'check_out_time' => $checkOutTime,
        ]);
    }

    return response()->json([
        'employee_name' => 'N/A',
        'check_in_time' => 'N/A',
        'check_out_time' => 'N/A',
    ]);
}
    
}
