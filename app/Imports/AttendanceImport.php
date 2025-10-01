<?php

namespace App\Imports;

use App\Models\AttendanceProcess;
use App\Models\Employee;
use Illuminate\Support\Facades\Log;
use Maatwebsite\Excel\Concerns\ToModel;
use Maatwebsite\Excel\Concerns\WithStartRow;

class AttendanceImport implements ToModel, WithStartRow
{
    public function startRow(): int
    {
        return 3; // Start processing from actual employee data
    }

    public function model(array $row)
    {
        Log::info('Processing row:', $row);

        // Validate and clean fingerprint ID
        if (!isset($row[2]) || !is_numeric(trim($row[2]))) {
            Log::warning('Skipping row due to invalid fingerprint ID:', $row);
            return null;
        }

        $fingerprintId = (int) trim($row[2]);

        // Ensure fingerprint ID is an integer in database
        $employee = Employee::whereRaw('TRIM(CAST(fingerprint_id AS CHAR)) = ?', [$fingerprintId])->first();
        if (!$employee) {
            Log::warning('No employee found for fingerprint ID', ['fingerprint_id' => $fingerprintId]);
            return null;
        }

        $currentYear = date('Y');
        $currentMonth = '01'; // Assuming January

        // Loop through attendance entries (Index 3 to 33)
        for ($i = 3; $i <= 33; $i++) {
            if (!isset($row[$i]) || empty(trim($row[$i]))) {
                continue; // Skip empty cells
            }

            $day = str_pad($i - 2, 2, '0', STR_PAD_LEFT);
            $date = "$currentYear-$currentMonth-$day";
            $timeEntry = trim($row[$i]);

            // Skip invalid non-time values (e.g., metadata like "Name:", "Dept.:", "Company")
            if (preg_match('/[^0-9:\s]/', $timeEntry)) {
                Log::warning('Skipping invalid time entry', ['employee_id' => $employee->id, 'day' => $date, 'timeEntry' => $timeEntry]);
                continue;
            }

            // Extract time values (Handles cases like "08:3715:43")
            preg_match_all('/\d{2}:\d{2}/', $timeEntry, $matches);
            $times = $matches[0];

            // Handle cases where multiple times are joined (e.g., "07:4015:42")
            if (count($times) > 2) {
                Log::warning('Skipping entry with too many times', ['employee_id' => $employee->id, 'day' => $date, 'timeEntry' => $timeEntry]);
                continue;
            }

            $checkIn = isset($times[0]) ? $times[0] : null;
            $checkOut = isset($times[1]) ? $times[1] : null;

            // Log extracted times for debugging
            Log::info("Extracted times for Employee ID {$employee->id} on {$date}: Check-in: {$checkIn}, Check-out: {$checkOut}");

            // Ensure at least one valid time exists before inserting
            if ($checkIn || $checkOut) {
                try {
                    $attendance = AttendanceProcess::create([
                        'employee_id' => $employee->id,
                        'day' => $date,
                        'check_in' => $checkIn,
                        'check_out' => $checkOut,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]);

                    Log::info('Inserted attendance record successfully', [
                        'employee_id' => $employee->id,
                        'day' => $date,
                        'check_in' => $checkIn,
                        'check_out' => $checkOut,
                    ]);
                } catch (\Exception $e) {
                    Log::error('Error inserting attendance record', [
                        'employee_id' => $employee->id,
                        'day' => $date,
                        'error' => $e->getMessage(),
                    ]);
                }
            }
        }

        return null;
    }
}
