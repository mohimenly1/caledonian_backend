<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class SalaryDeductionsAbsencesController extends Controller
{
    public function storeSalaryDeductionsAbsences(Request $request)
    {
        $validatedData = $request->validate([
            'salary_id' => 'required|exists:salaries,id',
            'deduction_ids' => 'array',
            'deduction_ids.*' => 'exists:deductions,id',
            'absence_ids' => 'array',
            'absence_ids.*' => 'exists:absences,id',
            'employee_id' => 'required|exists:employees,id',
        ]);
    
        // Insert deductions once for each selected deduction (without repeating for absences)
        if (!empty($validatedData['deduction_ids'])) {
            foreach ($validatedData['deduction_ids'] as $deductionId) {
                DB::table('salary_deductions_absences')->insert([
                    'salary_id' => $validatedData['salary_id'],
                    'deduction_id' => $deductionId,
                    'absence_id' => null, // No absence associated with this deduction
                    'employee_id' => $validatedData['employee_id'],
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }
        }
    
        // Insert absences with null deduction_id for each selected absence
        if (!empty($validatedData['absence_ids'])) {
            foreach ($validatedData['absence_ids'] as $absenceId) {
                DB::table('salary_deductions_absences')->insert([
                    'salary_id' => $validatedData['salary_id'],
                    'deduction_id' => null, // No deduction associated with this absence
                    'absence_id' => $absenceId,
                    'employee_id' => $validatedData['employee_id'],
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }
        }
    
        return response()->json(['message' => 'Pivot data saved successfully']);
    }
    
    
    

}
