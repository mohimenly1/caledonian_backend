<?php

namespace App\Http\Controllers;

use App\Models\Student;
use App\Models\ClassRoom;
use App\Models\Section;
use App\Models\StudyYear;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use App\Models\StudentAttendanceRecord;
use Illuminate\Support\Facades\Validator;



class EduraStudentController extends Controller
{


    /**
 * تحديث بيانات طالب
 */
/**
 * تحديث بيانات طالب
 */
/**
 * تحديث بيانات طالب
 */
public function updateStudent(Request $request, $studentId)
{
    try {
        $student = Student::findOrFail($studentId);

        Log::info("=== BEFORE UPDATE ===", [
            'student_id' => $studentId,
            'current_data' => [
                'name' => $student->name,
                'arabic_name' => $student->arabic_name,
                'date_of_birth' => $student->date_of_birth,
                'national_number' => $student->national_number,
                'year' => $student->year,
                'address' => $student->address,
                'note' => $student->note,
            ],
            'request_data' => $request->all()
        ]);

        // تنظيف البيانات
        $cleanedData = array_map(function ($value) {
            return $value === null ? '' : (string) $value;
        }, $request->all());

        // التحقق من البيانات
        $validated = Validator::make($cleanedData, [
            'name' => 'sometimes|string|max:500',
            'arabic_name' => 'nullable|string|max:255',
            'gender' => 'sometimes|in:male,female',
            'date_of_birth' => 'nullable|date',
            'national_number' => 'nullable|string|max:12',
            'year' => 'nullable|string|max:255',
            'address' => 'nullable|string|max:255',
            'note' => 'nullable|string|max:255',
        ])->validate();

        Log::info("=== VALIDATED DATA ===", [
            'validated_data' => $validated
        ]);

        // تحديث البيانات
        $updateResult = $student->update($validated);

        Log::info("=== AFTER UPDATE ===", [
            'update_result' => $updateResult,
            'updated_student' => [
                'name' => $student->fresh()->name,
                'arabic_name' => $student->fresh()->arabic_name,
                'date_of_birth' => $student->fresh()->date_of_birth,
                'national_number' => $student->fresh()->national_number,
                'year' => $student->fresh()->year,
                'address' => $student->fresh()->address,
                'note' => $student->fresh()->note,
            ]
        ]);

        return response()->json([
            'success' => true,
            'message' => 'تم تحديث بيانات الطالب بنجاح',
            'update_result' => $updateResult,
            'student' => $student->fresh()
        ]);

    } catch (\Illuminate\Validation\ValidationException $e) {
        Log::error('Validation error updating student: ' . $e->getMessage(), [
            'student_id' => $studentId,
            'errors' => $e->errors()
        ]);
        
        return response()->json([
            'success' => false,
            'message' => 'بيانات غير صالحة',
            'errors' => $e->errors()
        ], 422);
        
    } catch (\Exception $e) {
        Log::error('Error updating student: ' . $e->getMessage(), [
            'student_id' => $studentId,
            'data' => $request->all()
        ]);
        
        return response()->json([
            'success' => false,
            'message' => 'فشل في تحديث البيانات: ' . $e->getMessage()
        ], 500);
    }
}
    // إضافة الدوال الجديدة في EduraStudentController
/**
 * جلب تفاصيل طالب معين
 */
public function getStudentDetails($studentId)
{
    try {
        $student = Student::with([
            'class:id,name',
            'section:id,name',
            'parent:id,first_name,last_name',
            'studyYear:id,name'
        ])->findOrFail($studentId);

        $parentName = $student->parent ? 
            ($student->parent->first_name . ' ' . $student->parent->last_name) : 
            'غير محدد';

        $studentDetails = [
            'id' => $student->id,
            'name' => $student->name,
            'arabic_name' => $student->arabic_name,
            'gender' => $student->gender,
            'date_of_birth' => $student->date_of_birth,
            'age' => $student->date_of_birth ? \Carbon\Carbon::parse($student->date_of_birth)->age : null,
            'class_name' => $student->class ? $student->class->name : 'غير محدد',
            'section_name' => $student->section ? $student->section->name : 'غير محدد',
            'parent_name' => $parentName,
            'year' => $student->year,
            'national_number' => $student->national_number,
            'study_year' => $student->studyYear ? $student->studyYear->name : null,
            'created_at' => $student->created_at->format('Y-m-d'),
            'address' => $student->address,
            'phone' => $student->parent ? $student->parent->phone_number_one : null,
            'email' => $student->parent ? $student->parent->email : null,
            'note' => $student->note,
        ];

        return response()->json($studentDetails);

    } catch (\Exception $e) {
        Log::error('Error in getStudentDetails: ' . $e->getMessage());
        
        return response()->json([
            'error' => 'Student not found',
            'message' => 'Failed to fetch student details'
        ], 404);
    }
}

/**
 * جلب سجلات حضور طالب معين
 */
public function getStudentAttendance(Request $request, $studentId)
{
    try {
        $query = StudentAttendanceRecord::with([
            'class:id,name',
            'section:id,name',
            'user:id,name'
        ])->where('student_id', $studentId)
          ->orderBy('created_at', 'desc');

        // تطبيق فلترة التاريخ
        if ($request->has('start_date') && $request->start_date) {
            $query->whereDate('created_at', '>=', $request->start_date);
        }

        if ($request->has('end_date') && $request->end_date) {
            $query->whereDate('created_at', '<=', $request->end_date);
        }

        // الترحيل
        $perPage = $request->input('per_page', 15);
        $attendanceRecords = $query->paginate($perPage);

        // إعادة هيكلة البيانات
        $attendanceRecords->getCollection()->transform(function ($record) {
            // تحديد حالة الحضور
            $status = 'present';
            if (str_contains($record->record_state, 'غياب') || str_contains($record->record_state, 'absence')) {
                $status = 'absent';
            } elseif (str_contains($record->record_state, 'تأخر') || str_contains($record->record_state, 'late')) {
                $status = 'late';
            }

            return [
                'id' => $record->id,
                'date' => $record->created_at->format('Y-m-d'),
                'record_state' => $record->record_state,
                'status' => $status,
                'class_name' => $record->class ? $record->class->name : 'غير محدد',
                'section_name' => $record->section ? $record->section->name : 'غير محدد',
                'recorded_by' => $record->user ? $record->user->name : 'غير معروف',
                'created_at' => $record->created_at->format('Y-m-d H:i:s'),
            ];
        });

        // إحصائيات الحضور
        $attendanceStats = [
            'total_records' => $attendanceRecords->total(),
            'present_count' => StudentAttendanceRecord::where('student_id', $studentId)
                ->where(function($q) {
                    $q->where('record_state', 'like', '%حضور%')
                      ->orWhere('record_state', 'like', '%presence%');
                })->count(),
            'absent_count' => StudentAttendanceRecord::where('student_id', $studentId)
                ->where(function($q) {
                    $q->where('record_state', 'like', '%غياب%')
                      ->orWhere('record_state', 'like', '%absence%');
                })->count(),
            'attendance_rate' => 0
        ];

        if ($attendanceStats['total_records'] > 0) {
            $attendanceStats['attendance_rate'] = round(
                ($attendanceStats['present_count'] / $attendanceStats['total_records']) * 100, 
                2
            );
        }

        return response()->json([
            'data' => $attendanceRecords->items(),
            'links' => $attendanceRecords->links(),
            'meta' => [
                'current_page' => $attendanceRecords->currentPage(),
                'last_page' => $attendanceRecords->lastPage(),
                'per_page' => $attendanceRecords->perPage(),
                'total' => $attendanceRecords->total(),
            ],
            'stats' => $attendanceStats
        ]);

    } catch (\Exception $e) {
        Log::error('Error in getStudentAttendance: ' . $e->getMessage());
        
        return response()->json([
            'data' => [],
            'links' => [],
            'meta' => [
                'current_page' => 1,
                'last_page' => 1,
                'per_page' => 15,
                'total' => 0,
            ],
            'stats' => [
                'total_records' => 0,
                'present_count' => 0,
                'absent_count' => 0,
                'attendance_rate' => 0
            ],
            'error' => 'Failed to fetch attendance records'
        ], 500);
    }
}
    /**
     * جلب إحصائيات الطلاب للمدرسة
     */
    public function getStudentsStats()
    {
        try {
            // 1. إجمالي عدد الطلاب
            $totalStudents = Student::count();

            // 2. توزيع الطلاب حسب الجنس
            $genderDistribution = Student::select('gender', DB::raw('COUNT(*) as count'))
                ->groupBy('gender')
                ->get()
                ->pluck('count', 'gender')
                ->toArray();

            $maleStudents = $genderDistribution['male'] ?? 0;
            $femaleStudents = $genderDistribution['female'] ?? 0;

            // 3. توزيع الطلاب حسب الفصول
            $studentsByClass = Student::with(['class'])
                ->select('class_id', DB::raw('COUNT(*) as count'))
                ->groupBy('class_id')
                ->get()
                ->mapWithKeys(function ($item) {
                    $className = $item->class ? $item->class->name : 'غير محدد';
                    return [$className => $item->count];
                })
                ->toArray();

            // 4. توزيع الطلاب حسب الشعبة
            $studentsBySection = Student::with(['section'])
                ->select('section_id', DB::raw('COUNT(*) as count'))
                ->groupBy('section_id')
                ->get()
                ->mapWithKeys(function ($item) {
                    $sectionName = $item->section ? $item->section->name : 'غير محدد';
                    return [$sectionName => $item->count];
                })
                ->toArray();

            // 5. توزيع الطلاب حسب السنة الدراسية
            $studentsByYear = Student::select('year', DB::raw('COUNT(*) as count'))
                ->whereNotNull('year')
                ->groupBy('year')
                ->get()
                ->pluck('count', 'year')
                ->toArray();

            return response()->json([
                'total_students' => $totalStudents,
                'male_students' => $maleStudents,
                'female_students' => $femaleStudents,
                'students_by_class' => $studentsByClass,
                'students_by_section' => $studentsBySection,
                'students_by_year' => $studentsByYear,
                'gender_distribution' => $genderDistribution,
                'last_updated' => now()->toDateTimeString()
            ]);

        } catch (\Exception $e) {
            Log::error('Error in getStudentsStats: ' . $e->getMessage());
            
            return response()->json([
                'total_students' => 0,
                'male_students' => 0,
                'female_students' => 0,
                'students_by_class' => [],
                'students_by_section' => [],
                'students_by_year' => [],
                'gender_distribution' => [],
                'last_updated' => now()->toDateTimeString(),
                'error' => 'Failed to fetch students statistics'
            ], 500);
        }
    }

    /**
     * جلب قائمة الطلاب مع التفاصيل
     */
    public function getStudentsWithDetails(Request $request)
    {
        try {
            $query = Student::with([
                'class:id,name',
                'section:id,name',
                'parent:id,first_name,last_name', // ⭐ التصحيح هنا
                'studyYear:id,name'
            ])->select([
                'id', 'name', 'arabic_name', 'gender', 'date_of_birth',
                'class_id', 'section_id', 'parent_id', 'year', 
                'national_number', 'study_year_id', 'created_at'
            ]);

            // تطبيق الفلترة
            if ($request->has('search') && $request->search) {
                $search = $request->search;
                $query->where(function ($q) use ($search) {
                    $q->where('name', 'like', "%{$search}%")
                      ->orWhere('arabic_name', 'like', "%{$search}%")
                      ->orWhere('national_number', 'like', "%{$search}%")
                      ->orWhereHas('parent', function ($q) use ($search) {
                          // ⭐ التصحيح هنا: البحث في first_name و last_name
                          $q->where('first_name', 'like', "%{$search}%")
                            ->orWhere('last_name', 'like', "%{$search}%");
                      });
                });
            }

            if ($request->has('class') && $request->class) {
                $query->whereHas('class', function ($q) use ($request) {
                    $q->where('name', 'like', "%{$request->class}%");
                });
            }

            if ($request->has('section') && $request->section) {
                $query->whereHas('section', function ($q) use ($request) {
                    $q->where('name', 'like', "%{$request->section}%");
                });
            }

            if ($request->has('year') && $request->year) {
                $query->where('year', 'like', "%{$request->year}%");
            }

            if ($request->has('gender') && $request->gender) {
                $query->where('gender', $request->gender);
            }

            // الترحيل
            $perPage = $request->input('per_page', 15);
            $students = $query->paginate($perPage);

            // إعادة هيكلة البيانات
            $students->getCollection()->transform(function ($student) {
                // ⭐ التصحيح هنا: دمج first_name و last_name
                $parentName = $student->parent ? 
                    ($student->parent->first_name . ' ' . $student->parent->last_name) : 
                    'غير محدد';

                return [
                    'id' => $student->id,
                    'name' => $student->name,
                    'arabic_name' => $student->arabic_name,
                    'gender' => $student->gender,
                    'date_of_birth' => $student->date_of_birth,
                    'age' => $student->date_of_birth ? \Carbon\Carbon::parse($student->date_of_birth)->age : null,
                    'class_name' => $student->class ? $student->class->name : 'غير محدد',
                    'section_name' => $student->section ? $student->section->name : 'غير محدد',
                    'parent_name' => $parentName, // ⭐ استخدام الاسم المدمج
                    'year' => $student->year,
                    'national_number' => $student->national_number,
                    'study_year' => $student->studyYear ? $student->studyYear->name : null,
                    'created_at' => $student->created_at->format('Y-m-d'),
                ];
            });

            return response()->json($students);

        } catch (\Exception $e) {
            Log::error('Error in getStudentsWithDetails: ' . $e->getMessage());
            
            return response()->json([
                'data' => [],
                'links' => [],
                'total' => 0,
                'per_page' => 15,
                'current_page' => 1,
                'error' => 'Failed to fetch students data'
            ], 500);
        }
    }
}