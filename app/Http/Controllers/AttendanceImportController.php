<?php

namespace App\Http\Controllers;

use Maatwebsite\Excel\Facades\Excel;
use App\Imports\AttendanceImport;
use App\Models\Employee;
use App\Models\AttendanceProcess;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
class AttendanceImportController extends Controller
{
    // public function importJson(Request $request)
    // {
    //     // Validate JSON file input
    //     $request->validate([
    //         'file' => 'required|file|mimes:json'
    //     ]);
    
    //     // Read JSON file
    //     $jsonData = json_decode(file_get_contents($request->file('file')), true);
    
    //     if (!$jsonData) {
    //         return response()->json(['error' => 'Invalid JSON file'], 400);
    //     }
    
    //     Log::info('Starting JSON Attendance Import');
    
    //     $currentYear = date('Y');
    //     $currentMonth = '02'; // Assuming January
    //     $employees = [];
    //     $daysMapping = [];
    //     $foundDays = false;
    //     $employeeFingerprint = null;
    
    //     foreach ($jsonData as $index => $row) {
    //         // Detect and store the days (from 1 to 31) in a mapping array
    //         if (!$foundDays && isset($row['Attendance Record Report']) && is_numeric($row['Attendance Record Report'])) {
    //             foreach ($row as $key => $value) {
    //                 // Only consider keys that start with '__'
    //                 if (strpos($key, '__') === 0 && is_numeric($value)) {
    //                     $daysMapping[$key] = (int)$value;
    //                     Log::info("Mapping column {$key} to day {$value}");
    //                 }
    //             }
    //             $foundDays = true;
    //             Log::info('Days mapping completed', ['mapping' => $daysMapping]);
    //             continue;
    //         }
    
    //         // Detect employee entry (fingerprint ID is in __1)
    //         if (isset($row['Attendance Record Report']) && $row['Attendance Record Report'] == 'ID:' && isset($row['__1']) && is_numeric($row['__1'])) {
    //             $fingerprintId = (int) $row['__1'];
    
    //             $employee = Employee::where('fingerprint_id', $fingerprintId)->first();
    //             if (!$employee) {
    //                 Log::warning('No employee found for fingerprint ID', ['fingerprint_id' => $fingerprintId]);
    //                 // Reset employeeFingerprint to avoid using previous employee context
    //                 $employeeFingerprint = null;
    //                 continue;
    //             }
    
    //             $employees[$fingerprintId] = $employee;
    //             $employeeFingerprint = $fingerprintId;
    //             Log::info("Employee detected", ['fingerprint' => $fingerprintId, 'name' => $employee->name]);
    //             continue;
    //         }
    
    //         // Process attendance data when a valid employee has been identified
    //         if ($employeeFingerprint !== null && isset($row['Attendance Record Report']) && $row['Attendance Record Report'] === "") {
    //             if (!isset($employees[$employeeFingerprint])) {
    //                 Log::warning('Skipping attendance row: No employee context', ['fingerprint' => $employeeFingerprint]);
    //                 continue;
    //             }
    
    //             $employee = $employees[$employeeFingerprint];
    //             Log::info("Processing attendance for employee", ['fingerprint' => $employeeFingerprint, 'row_index' => $index]);
    
    //             foreach ($row as $dayIndex => $timeEntry) {
    //                 // Only process keys that are in our mapping (keys that start with "__")
    //                 if (!isset($daysMapping[$dayIndex])) {
    //                     continue;
    //                 }
    
    //                 $day = str_pad($daysMapping[$dayIndex], 2, '0', STR_PAD_LEFT);
    //                 $date = "$currentYear-$currentMonth-$day";
    
    //                 // Extract time values (Handles cases like "08:3715:43")
    //                 preg_match_all('/\d{2}:\d{2}/', $timeEntry, $matches);
    //                 $times = $matches[0];
    
    //                 $checkIn = $times[0] ?? null;
    //                 $checkOut = $times[1] ?? null;
    
    //                 Log::info("Parsed times", [
    //                     'date' => $date,
    //                     'timeEntry' => $timeEntry,
    //                     'check_in' => $checkIn,
    //                     'check_out' => $checkOut
    //                 ]);
    
    //                 try {
    //                     AttendanceProcess::updateOrCreate(
    //                         [
    //                             'employee_id' => $employee->id,
    //                             'day' => $date,
    //                         ],
    //                         [
    //                             'check_in' => $checkIn,
    //                             'check_out' => $checkOut,
    //                             'created_at' => now(),
    //                             'updated_at' => now(),
    //                         ]
    //                     );
    
    //                     Log::info('Inserted attendance record', [
    //                         'employee_id' => $employee->id,
    //                         'day' => $date,
    //                         'check_in' => $checkIn,
    //                         'check_out' => $checkOut,
    //                     ]);
    //                 } catch (\Exception $e) {
    //                     Log::error('Error inserting attendance record', [
    //                         'employee_id' => $employee->id,
    //                         'day' => $date,
    //                         'error' => $e->getMessage(),
    //                     ]);
    //                 }
    //             }
    //         }
    //     }
    
    //     return response()->json(['message' => 'Attendance data imported successfully']);
    // }
    
    
    //     public function importJson(Request $request)
    // {
    //     // Validate JSON file input
    //     $request->validate([
    //         'file' => 'required|file|mimes:json'
    //     ]);
    
    //     // Read JSON file
    //     $jsonData = json_decode(file_get_contents($request->file('file')), true);
    
    //     if (!$jsonData) {
    //         return response()->json(['error' => 'Invalid JSON file'], 400);
    //     }
    
    //     Log::info('Starting JSON Attendance Import');
    
    //     // Initialize without hard-coded month/year
    //     $currentYear = null;
    //     $currentMonth = null;
    //     $employees = [];
    //     $daysMapping = [];
    //     $foundDays = false;
    //     $employeeFingerprint = null;
    
    //     foreach ($jsonData as $index => $row) {
    //         // 1. استخراج نطاق التقرير (مثلاً "2025-02-01 ~ 2025-02-27") لتحديد السنة والشهر
    //         if (isset($row['Attendance Record Report']) && $row['Attendance Record Report'] === "Att. Time" 
    //             && isset($row['__1']) && strpos($row['__1'], '~') !== false) {
    //             $range = explode('~', $row['__1']);
    //             $startDateStr = trim($range[0]); // مثال: "2025-02-01"
    //             try {
    //                 $startDate = \Carbon\Carbon::parse($startDateStr);
    //                 $currentYear = $startDate->format('Y');
    //                 $currentMonth = $startDate->format('m');
    //                 Log::info("Detected report date range", [
    //                     'start_date' => $startDateStr,
    //                     'year' => $currentYear,
    //                     'month' => $currentMonth
    //                 ]);
    //             } catch(\Exception $e) {
    //                 Log::error("Error parsing report date range", [
    //                     'value' => $row['__1'],
    //                     'error' => $e->getMessage()
    //                 ]);
    //             }
    //             continue;
    //         }
    
    //         // 2. بناء mapping للأعمدة التي تحتوي على أرقام الأيام
    //         // هنا نقوم بمعالجة جميع المفاتيح باستثناء "Attendance Record Report"
    //         if (!$foundDays && isset($row['Attendance Record Report']) && is_numeric($row['Attendance Record Report'])) {
    //             foreach ($row as $key => $value) {
    //                 if ($key !== 'Attendance Record Report' && is_numeric($value)) {
    //                     $daysMapping[$key] = (int)$value;
    //                     Log::info("Mapping column [{$key}] to day {$value}");
    //                 }
    //             }
    //             $foundDays = true;
    //             Log::info('Days mapping completed', ['mapping' => $daysMapping]);
    //             continue;
    //         }
    
    //         // 3. اكتشاف صف الموظف بناءً على "ID:"، حيثُ رقم البصمة في العمود __1
    //         if (isset($row['Attendance Record Report']) && $row['Attendance Record Report'] == 'ID:' 
    //             && isset($row['__1']) && is_numeric($row['__1'])) {
    //             $fingerprintId = (int)$row['__1'];
    //             $employee = Employee::where('fingerprint_id', $fingerprintId)->first();
    //             if (!$employee) {
    //                 Log::warning('No employee found for fingerprint ID', ['fingerprint_id' => $fingerprintId]);
    //                 $employeeFingerprint = null;
    //                 continue;
    //             }
    //             $employees[$fingerprintId] = $employee;
    //             $employeeFingerprint = $fingerprintId;
    //             Log::info("Employee detected", ['fingerprint' => $fingerprintId, 'name' => $employee->name]);
    //             continue;
    //         }
    
    //         // 4. معالجة بيانات الحضور للموظف إذا كان معرفه موجودًا
    //         if ($employeeFingerprint !== null 
    //             && isset($row['Attendance Record Report']) 
    //             && $row['Attendance Record Report'] === "") {
    
    //             if ($currentYear === null || $currentMonth === null) {
    //                 Log::error("Report date range not detected. Skipping row", ['row_index' => $index]);
    //                 continue;
    //             }
    
    //             if (!isset($employees[$employeeFingerprint])) {
    //                 Log::warning('Skipping attendance row: No employee context', ['fingerprint' => $employeeFingerprint]);
    //                 continue;
    //             }
        
    //             $employee = $employees[$employeeFingerprint];
    //             Log::info("Processing attendance for employee", ['fingerprint' => $employeeFingerprint, 'row_index' => $index]);
        
    //             foreach ($row as $colKey => $timeEntry) {
    //                 // نستخدم جميع المفاتيح الموجودة في mapping (حتى المفتاح الفارغ)
    //                 if (!isset($daysMapping[$colKey])) {
    //                     continue;
    //                 }
        
    //                 $day = str_pad($daysMapping[$colKey], 2, '0', STR_PAD_LEFT);
    //                 $date = "$currentYear-$currentMonth-$day";
        
    //                 // استخراج أوقات الحضور (check_in) والانصراف (check_out)
    //                 preg_match_all('/\d{2}:\d{2}/', $timeEntry, $matches);
    //                 $times = $matches[0];
        
    //                 $checkIn = $times[0] ?? null;
    //                 $checkOut = $times[1] ?? null;
        
    //                 Log::info("Parsed times", [
    //                     'date' => $date,
    //                     'timeEntry' => $timeEntry,
    //                     'check_in' => $checkIn,
    //                     'check_out' => $checkOut
    //                 ]);
        
    //                 try {
    //                     AttendanceProcess::updateOrCreate(
    //                         [
    //                             'employee_id' => $employee->id,
    //                             'day' => $date,
    //                         ],
    //                         [
    //                             'check_in' => $checkIn,
    //                             'check_out' => $checkOut,
    //                             'created_at' => now(),
    //                             'updated_at' => now(),
    //                         ]
    //                     );
        
    //                     Log::info('Inserted attendance record', [
    //                         'employee_id' => $employee->id,
    //                         'day' => $date,
    //                         'check_in' => $checkIn,
    //                         'check_out' => $checkOut,
    //                     ]);
    //                 } catch (\Exception $e) {
    //                     Log::error('Error inserting attendance record', [
    //                         'employee_id' => $employee->id,
    //                         'day' => $date,
    //                         'error' => $e->getMessage(),
    //                     ]);
    //                 }
    //             }
    //         }
    //     }
        
    //     return response()->json(['message' => 'Attendance data imported successfully']);
    // }
    
    
        public function importJson(Request $request)
    {
        // التحقق من صحة الملف المُحمَّل بصيغة JSON
        $request->validate([
            'file' => 'required|file|mimes:json'
        ]);
    
        // قراءة ملف JSON
        $jsonData = json_decode(file_get_contents($request->file('file')), true);
        if (!$jsonData) {
            return response()->json(['error' => 'Invalid JSON file'], 400);
        }
    
        Log::info('Starting JSON Attendance Import');
    
        // المتغيرات الأولية بدون شهر/سنة ثابتة
        $currentYear = null;
        $currentMonth = null;
        $employees = [];
        $foundMappingRow = false; // صف يحتوي على بيانات mapping، سيتم تخطيه
        $employeeFingerprint = null;
    
        foreach ($jsonData as $index => $row) {
    
            // 1. استخراج نطاق التقرير (مثلاً "2025-02-01 ~ 2025-02-27") لتحديد السنة والشهر
            if (isset($row['Attendance Record Report']) 
                && $row['Attendance Record Report'] === "Att. Time" 
                && isset($row['__1']) 
                && strpos($row['__1'], '~') !== false) {
    
                $range = explode('~', $row['__1']);
                $startDateStr = trim($range[0]); // مثال: "2025-02-01"
                try {
                    $startDate = \Carbon\Carbon::parse($startDateStr);
                    $currentYear = $startDate->format('Y');
                    $currentMonth = $startDate->format('m');
                    Log::info("Detected report date range", [
                        'start_date' => $startDateStr,
                        'year' => $currentYear,
                        'month' => $currentMonth
                    ]);
                } catch (\Exception $e) {
                    Log::error("Error parsing report date range", [
                        'value' => $row['__1'],
                        'error' => $e->getMessage()
                    ]);
                }
                continue;
            }
    
            // 2. تجاهل صف mapping الأيام الذي يحمل قيمة رقمية في "Attendance Record Report"
            if (!$foundMappingRow && isset($row['Attendance Record Report']) && is_numeric($row['Attendance Record Report'])) {
                $foundMappingRow = true;
                Log::info('Mapping row detected and skipped', ['row_index' => $index]);
                continue;
            }
    
            // 3. اكتشاف صف الموظف بناءً على "ID:" في "Attendance Record Report"؛ رقم البصمة موجود في المفتاح "__1"
            if (isset($row['Attendance Record Report']) 
                && $row['Attendance Record Report'] == 'ID:' 
                && isset($row['__1']) && is_numeric($row['__1'])) {
    
                $fingerprintId = (int)$row['__1'];
                $employee = Employee::where('fingerprint_id', $fingerprintId)->first();
                if (!$employee) {
                    Log::warning('No employee found for fingerprint ID', ['fingerprint_id' => $fingerprintId]);
                    $employeeFingerprint = null;
                    continue;
                }
                $employees[$fingerprintId] = $employee;
                $employeeFingerprint = $fingerprintId;
                Log::info("Employee detected", ['fingerprint' => $fingerprintId, 'name' => $employee->name]);
                continue;
            }
    
            // 4. معالجة صف بيانات الحضور للموظف (الصف الذي يحتوي على بيانات الأيام)
            if ($employeeFingerprint !== null && isset($row['Attendance Record Report'])) {
                $atr = trim($row['Attendance Record Report']);
                // تخطي الصفوف التي لا تحمل بيانات الحضور (عناوين الصفوف مثل "ID:" أو "Att. Time" أو الأرقام)
                if ($atr === "ID:" || $atr === "Att. Time" || is_numeric($atr)) {
                    continue;
                }
    
                if ($currentYear === null || $currentMonth === null) {
                    Log::error("Report date range not detected. Skipping row", ['row_index' => $index]);
                    continue;
                }
    
                if (!isset($employees[$employeeFingerprint])) {
                    Log::warning('Skipping attendance row: No employee context', ['fingerprint' => $employeeFingerprint]);
                    continue;
                }
    
                $employee = $employees[$employeeFingerprint];
                Log::info("Processing attendance for employee", ['fingerprint' => $employeeFingerprint, 'row_index' => $index]);
    
                // استخدم array_values بدون إزالة المفتاح "Attendance Record Report"
                $attendanceValues = array_values($row);
    
                // لكل قيمة في الصف، نُعيّن رقم اليوم بناءً على ترتيبها (اليوم = الفهرس + 1)
                foreach ($attendanceValues as $i => $timeEntry) {
                    $dayNumber = $i + 1;
                    $date = "$currentYear-$currentMonth-" . str_pad($dayNumber, 2, '0', STR_PAD_LEFT);
    
                    // استخراج أوقات check_in و check_out (مثلاً "08:3715:43")
                    preg_match_all('/\d{2}:\d{2}/', $timeEntry, $matches);
                    $times = $matches[0];
                    $checkIn = $times[0] ?? null;
                    $checkOut = $times[1] ?? null;
    
                    Log::info("Parsed times", [
                        'date' => $date,
                        'timeEntry' => $timeEntry,
                        'check_in' => $checkIn,
                        'check_out' => $checkOut
                    ]);
    
                    try {
                        AttendanceProcess::updateOrCreate(
                            [
                                'employee_id' => $employee->id,
                                'day' => $date,
                            ],
                            [
                                'check_in' => $checkIn,
                                'check_out' => $checkOut,
                                'created_at' => now(),
                                'updated_at' => now(),
                            ]
                        );
    
                        Log::info('Inserted attendance record', [
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
        }
    
        return response()->json(['message' => 'Attendance data imported successfully']);
    }
    
}
