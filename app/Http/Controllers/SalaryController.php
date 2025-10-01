<?php

namespace App\Http\Controllers;

use App\Models\Absence;
use App\Models\AttendanceProcess;
use App\Models\Deduction;
use App\Models\Salary;
use App\Models\Employee;
use Carbon\Carbon;
use Illuminate\Http\Request;

class SalaryController extends Controller
{
    
public function calculateTotalDeductionsFor2025()
{
    // Fetch all salaries for the year 2025
    $salaries = Salary::where('year', 2025)->get();

    // Calculate total deductions for each salary
    $totalDeductions = $salaries->sum('total_deductions');

    // Format the total deductions to 2 decimal places and add thousand separators
    $formattedTotalDeductions = number_format($totalDeductions, 2, '.', ',');

    // Return the formatted total deductions as JSON
    return response()->json([
        'success' => true,
        'total_deductions' => $formattedTotalDeductions,
    ]);
}

    public function calculateSalary(Request $request, $employeeId, $month, $year)
    {
        // Validate the incoming request
        $request->validate([
            'bonus' => 'nullable|numeric',
            'allowance' => 'nullable|numeric',
            'currency' => 'nullable|string|max:3',
        ]);
    
        // Check if a salary record already exists for this employee, month, and year
        $existingSalary = Salary::where('employee_id', $employeeId)
                                ->where('month', $month)
                                ->where('year', $year)
                                ->first();
    
        if ($existingSalary) {
            return response()->json([
                'error' => 'A salary record for this employee for the selected month and year already exists.',
            ], 400);
        }
    
        $employee = Employee::with(['attendanceProcesses' => function ($query) use ($month, $year) {
            $query->whereMonth('day', $month)
                  ->whereYear('day', $year);
        }])->findOrFail($employeeId);
    
        $baseSalary = $employee->base_salary;
        $bonus = $request->input('bonus', 0);
        $allowance = $request->input('allowance', 0);
        $currency = $request->input('currency', 'LYD');
        $totalDeductions = 0;
        $mandatoryStartTime = 490; // 8:10 AM in minutes
        $lateDays = 0;
        $absenceDays = 0;
        $reportDetails = ["Base salary: $baseSalary $currency"];
    
        // Get all days of the month
        $totalDaysInMonth = cal_days_in_month(CAL_GREGORIAN, $month, $year);
        $workingDays = [];
        $excludedDays = []; // To track Fridays and Saturdays
    
        for ($day = 1; $day <= $totalDaysInMonth; $day++) {
            $currentDate = \Carbon\Carbon::createFromDate($year, $month, $day);
            $dayOfWeek = $currentDate->format('l');
    
            if (in_array($dayOfWeek, ['Friday', 'Saturday'])) {
                // Add Fridays and Saturdays to excluded days for deduction calculations
                $excludedDays[] = $currentDate->format('Y-m-d');
                $reportDetails[] = "Excluded $dayOfWeek on " . $currentDate->format('Y-m-d') . " from deductions (Non-working day)";
            } else {
                $workingDays[] = $currentDate->format('Y-m-d');
            }
        }
    
        // Calculate daily salary based on 30 days regardless of actual working days
        $dailySalary = $baseSalary / 30;
        $reportDetails[] = "Daily salary: $dailySalary $currency (Base salary / 30 days)";
    
        // Calculate late arrivals and absence deductions only for working days (excluding Friday and Saturday)
        foreach ($workingDays as $workingDay) {
            $attendance = $employee->attendanceProcesses->where('day', $workingDay)->first();
    
            if ($attendance && $attendance->check_in) {
                $checkInMinutes = date('H', strtotime($attendance->check_in)) * 60 + date('i', strtotime($attendance->check_in));
                if ($checkInMinutes > $mandatoryStartTime) {
                    $lateDays++;
                    $deductionPerLateDay = $dailySalary * 0.20; // 20% deduction for each late day
                    $totalDeductions += $deductionPerLateDay;
                    $reportDetails[] = "Late on $workingDay: Check-in at " . $attendance->check_in . " | Deduction: -$deductionPerLateDay $currency (20% of daily salary)";
                }
            } else {
                // Check if the absence has "أذن من الادارة" (Permission from management)
                $absence = Absence::where('employee_id', $employeeId)
                                  ->where('date', $workingDay)
                                  ->first();
                if ($absence && $absence->absenceType->name === 'أذن من الادارة') {
                    // Skip absence deduction for "أذن من الادارة"
                    $reportDetails[] = "Excluded absence on $workingDay due to management permission";
                } else {
                    $absenceDays++;
                    $reportDetails[] = "Absent on $workingDay";
                }
            }
        }
    
        // Log total late deductions
        if ($lateDays > 0) {
            $reportDetails[] = "Total deductions for $lateDays late days: -$totalDeductions $currency";
        } else {
            $reportDetails[] = "No deductions for late days.";
        }
    
        // Calculate and log absence deductions (excluding "أذن من الادارة")
        if ($absenceDays > 0) {
            $absenceDeductionPerDay = $dailySalary * 3; // 3x daily salary for each absence
            $deduction = min($baseSalary, $absenceDays * $absenceDeductionPerDay);
            $totalDeductions += $deduction;
            $reportDetails[] = "Deduction for $absenceDays absence days (3x per day): -$deduction $currency";
        } else {
            $reportDetails[] = "No deductions for absence days.";
        }
    
        // Calculate final net salary
        $netSalary = max(0, $baseSalary + $bonus + $allowance - $totalDeductions);
        $reportDetails[] = "Final net salary: $netSalary $currency";
    
        // Store the salary with detailed report operations
        Salary::create([
            'employee_id' => $employeeId,
            'month' => $month,
            'year' => $year,
            'base_salary' => $baseSalary,
            'bonus' => $bonus,
            'allowance' => $allowance,
            'total_deductions' => $totalDeductions,
            'net_salary' => $netSalary,
            'effective_from' => now(),
            'currency' => $currency,
            'report_operations' => implode("\n", $reportDetails),
        ]);
    
        return response()->json([
            'message' => 'Salary calculated successfully.',
            'report_operations' => implode("\n", $reportDetails),
        ]);
    }
    
    
    
    

    public function calculateSalariesForBulkEmployees(Request $request)
    {
        // Validate the incoming request
        $request->validate([
            'employee_ids' => 'required|array',
            'employee_ids.*' => 'exists:employees,id',
            'month' => 'required|integer|min:1|max:12',
            'year' => 'required|integer',
            'bonus' => 'nullable|numeric',
            'allowance' => 'nullable|numeric',
            'currency' => 'nullable|string|max:3',
        ]);
    
        $month = $request->input('month');
        $year = $request->input('year');
        $bonus = $request->input('bonus', 0);
        $allowance = $request->input('allowance', 0);
        $currency = $request->input('currency', 'LYD');
        $mandatoryStartTime = 480; // 8:00 AM in minutes
    
        $results = [];
    
        // Process employees in chunks to optimize memory usage
        $employeeIds = $request->input('employee_ids');
        $chunks = array_chunk($employeeIds, 100); // Process in batches of 100 employees
    
        foreach ($chunks as $chunk) {
            $employees = Employee::with(['attendanceProcesses' => function ($query) use ($month, $year) {
                $query->whereMonth('day', $month)
                      ->whereYear('day', $year);
            }])->whereIn('id', $chunk)->get();
    
            foreach ($employees as $employee) {
                $existingSalary = Salary::where('employee_id', $employee->id)
                                        ->where('month', $month)
                                        ->where('year', $year)
                                        ->first();
    
                if ($existingSalary) {
                    $results[] = [
                        'employee_id' => $employee->id,
                        'status' => 'error',
                        'message' => 'Salary record already exists for this employee for the selected month and year.',
                    ];
                    continue;
                }
    
                $baseSalary = $employee->base_salary;
                $totalDeductions = 0;
                $lateDays = 0;
                $absenceDays = 0;
                $reportDetails = ["Base salary: $baseSalary $currency"];
    
                // Get all days of the month and filter working days (Sunday to Thursday)
                $totalDaysInMonth = cal_days_in_month(CAL_GREGORIAN, $month, $year);
                $workingDays = [];
                for ($day = 1; $day <= $totalDaysInMonth; $day++) {
                    $currentDate = \Carbon\Carbon::createFromDate($year, $month, $day);
                    $dayOfWeek = $currentDate->format('l');
                    if (!in_array($dayOfWeek, ['Friday', 'Saturday'])) {
                        $workingDays[] = $currentDate->format('Y-m-d');
                    }
                }
    
                // Calculate late arrivals and absence deductions
                foreach ($workingDays as $workingDay) {
                    $attendance = $employee->attendanceProcesses->where('day', $workingDay)->first();
    
                    if ($attendance && $attendance->check_in) {
                        $checkInMinutes = date('H', strtotime($attendance->check_in)) * 60 + date('i', strtotime($attendance->check_in));
                        if ($checkInMinutes > $mandatoryStartTime) {
                            $lateDays++;
                            $reportDetails[] = "Late on $workingDay";
                        }
                    } else {
                        // Check if the absence has "أذن من الادارة" (Permission from management)
                        $absence = Absence::where('employee_id', $employee->id)
                                          ->where('date', $workingDay)
                                          ->first();
                        if ($absence && $absence->absenceType->name === 'أذن من الادارة') {
                            // Skip absence deduction for "أذن من الادارة"
                            $reportDetails[] = "Excluded absence on $workingDay due to management permission";
                        } else {
                            $absenceDays++;
                            $dailySalary = $baseSalary / 30; // Assuming 30 days in a month
                            $absenceDeductionPerDay = $dailySalary * 3; // Apply the same factor as in calculateSalary
                            $totalDeductions += $absenceDeductionPerDay;
                            $reportDetails[] = "Absence deduction for $workingDay: -$absenceDeductionPerDay $currency";
                        }
                    }
                }
    
                // Add absence day details to the report for better tracking
                if ($absenceDays > 0) {
                    $reportDetails[] = "Total absence days: $absenceDays";
                } else {
                    $reportDetails[] = "No absences.";
                }
    
                // Apply late deductions
                if ($lateDays >= 3 && $lateDays < 6) {
                    $deductionAmount = $baseSalary * 0.2;
                    $totalDeductions += $deductionAmount;
                    $reportDetails[] = "Late deduction (20% for 3-5 late days): -$deductionAmount $currency";
                } elseif ($lateDays >= 6) {
                    $deductionAmount = $baseSalary * 0.4;
                    $totalDeductions += $deductionAmount;
                    $reportDetails[] = "Late deduction (40% for 6+ late days): -$deductionAmount $currency";
                } else {
                    $reportDetails[] = "No deductions for late days.";
                }
    
                // Calculate net salary
            // Calculate net salary, ensuring it doesn't go below zero
                $netSalary = max(0, $baseSalary - $totalDeductions + $bonus + $allowance);

                $reportDetails[] = "Total bonus: +$bonus $currency";
                $reportDetails[] = "Total allowance: +$allowance $currency";
                $reportDetails[] = "Net salary: $netSalary $currency";
    
                // Save the salary record
                Salary::create([
                    'employee_id' => $employee->id,
                    'month' => $month,
                    'year' => $year,
                    'base_salary' => $baseSalary,
                    'bonus' => $bonus,
                    'allowance' => $allowance,
                    'currency' => $currency,
                    'effective_from' => now(),
                    'total_deductions' => $totalDeductions,
                    'net_salary' => $netSalary,
                    'report_operations' => implode('; ', $reportDetails),
                ]);
    
                $results[] = [
                    'employee_id' => $employee->id,
                    'status' => 'success',
                    'net_salary' => $netSalary,
                    'report' => implode('; ', $reportDetails),
                ];
            }
        }
    
        return response()->json($results);
    }
    
    

    public function getEmployeeAbsences($employeeId, $month, $year)
    {
        // Validate that the employee exists
        $employee = Employee::findOrFail($employeeId);
    
        // Fetch attendance records (check-in & check-out)
        $attendanceRecords = AttendanceProcess::where('employee_id', $employeeId)
            ->whereMonth('day', $month)
            ->whereYear('day', $year)
            ->get(['day', 'check_in', 'check_out']);
    
        // Fetch absence records
        $absences = Absence::where('employee_id', $employeeId)
            ->whereMonth('date', $month)
            ->whereYear('date', $year)
            ->get(['date', 'absence_type_id']);
    
        // Get the start and end of the month
        $startOfMonth = Carbon::createFromDate($year, $month, 1)->startOfMonth();
        $endOfMonth = Carbon::createFromDate($year, $month, 1)->endOfMonth();
    
        // Create an array of all dates in the month
        $allDates = [];
        for ($date = $startOfMonth; $date <= $endOfMonth; $date->addDay()) {
            $allDates[$date->format('Y-m-d')] = [
                'date' => $date->format('Y-m-d'),
                'check_in' => null,
                'check_out' => null,
                'absence_type_id' => null
            ];
        }
    
        // Fill attendance data
        foreach ($attendanceRecords as $record) {
            if (isset($allDates[$record->day])) {
                $allDates[$record->day]['check_in'] = $record->check_in;
                $allDates[$record->day]['check_out'] = $record->check_out;
            }
        }
    
        // Fill absence data
        foreach ($absences as $absence) {
            if (isset($allDates[$absence->date])) {
                $allDates[$absence->date]['absence_type_id'] = $absence->absence_type_id;
            }
        }
    
        // Separate absences and attendance
        $filteredAbsences = [];
        $filteredAttendance = [];
    
        foreach ($allDates as $date => $data) {
            if ($data['absence_type_id'] === 5) {
                // Treat "أذن من الادارة" as attendance
                $filteredAttendance[] = $data;
            } elseif (!$data['check_in'] && !$data['check_out']) {
                // Only mark as absence if there is no check-in or check-out
                $filteredAbsences[] = $data;
            } else {
                $filteredAttendance[] = $data;
            }
        }
    
        return response()->json([
            'attendance' => $filteredAttendance,
            'absences' => $filteredAbsences
        ], 200);
    }
    
    
    
    public function index(Request $request)
    {
        $query = Salary::with(['employee:id,name,base_salary']); // Include base_salary here
    
        // Search by employee name
        if ($request->filled('search')) {
            $query->whereHas('employee', function ($q) use ($request) {
                $q->where('name', 'like', '%' . $request->search . '%');
            });
        }
    
        // Filter by month if provided
        if ($request->filled('month')) {
            $query->where('month', $request->month);
        }
    
        // Filter by year if provided
        if ($request->filled('year')) {
            $query->where('year', $request->year);
        }
    
        // Filter by currency if provided
        if ($request->filled('currency')) {
            $query->where('currency', $request->currency);
        }
    
        // Paginate results
        $salaries = $query->paginate(10);  // 10 results per page
    
        // Load deductionsShow relationship with a filter on salary_id for each salary
        $salaries->each(function ($salary) {
            $salary->deductionsShow = $salary->deductionsShow()
                ->wherePivot('salary_id', $salary->id)
                ->get();
        });
    
        return response()->json([
            'data' => $salaries->items(),
            'meta' => [
                'pagination' => [
                    'total' => $salaries->total(),
                    'current_page' => $salaries->currentPage(),
                    'per_page' => $salaries->perPage(),
                ],
            ],
        ]);
    }
    
    
    
    public function getSalaryWithDeductions($salaryId)
{
    $salary = Salary::with(['employee', 'deductions'])->findOrFail($salaryId);

    return response()->json($salary);
}

    // Create new salary for employee
    public function store(Request $request)
    {
        $validated = $request->validate([
            'employee_id' => 'required|exists:employees,id',
            'bonus' => 'nullable|numeric',
            'allowance' => 'nullable|numeric',
            'currency' => 'required|string',
            'month' => 'required|integer|between:1,12',
            'year' => 'required|integer',
            'deductions' => 'array',
            'deductions.*' => 'exists:deductions,id',
        ]);
    
        // Retrieve the employee and their base_salary
        $employee = Employee::findOrFail($validated['employee_id']);
        $base_salary = $employee->base_salary;
    
        // Calculate total deductions
        $deductions = Deduction::whereIn('id', $validated['deductions'])->sum('amount');
    
        // Calculate net salary
        $net_salary = $base_salary + ($validated['bonus'] ?? 0) + ($validated['allowance'] ?? 0) - $deductions;
    
        // Create or update the salary record
        $salary = Salary::updateOrCreate(
            [
                'employee_id' => $validated['employee_id'],
                'month' => $validated['month'],
                'year' => $validated['year'],
            ],
            [
                'bonus' => $validated['bonus'] ?? 0,
                'allowance' => $validated['allowance'] ?? 0,
                'currency' => $validated['currency'],
                'effective_from' => now(),
                'net_salary' => $net_salary,
            ]
        );
    
        return response()->json([
            'message' => 'Salary recorded successfully',
            'salary_id' => $salary->id
        ]);
    }
    
    
    
    
    
    
    
    
    

    // Update existing salary
    public function update(Request $request, $id)
    {
        $salary = Salary::findOrFail($id);
        
        $validatedData = $request->validate([
            'base_salary' => 'required|numeric',
            'bonus' => 'nullable|numeric',
            'allowance' => 'nullable|numeric',
            'currency' => 'required|in:LYD,USD',
            'effective_from' => 'required|date',
        ]);

        $salary->update($validatedData);

        return response()->json([
            'message' => 'Salary updated successfully',
            'salary' => $salary
        ]);
    }

    // Get salary for employee
    public function show($employee_id)
    {
        $salary = Salary::where('employee_id', $employee_id)->first();

        if (!$salary) {
            return response()->json(['message' => 'Salary not found'], 404);
        }

        return response()->json($salary);
    }

    // Delete salary record
    public function destroy($id)
    {
        $salary = Salary::findOrFail($id);
        $salary->delete(); // Soft delete the salary record

        return response()->json(['message' => 'Salary deleted successfully.']);
    }
}
