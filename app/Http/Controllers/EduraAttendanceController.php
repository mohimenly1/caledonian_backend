<?php

namespace App\Http\Controllers;

use App\Models\Student;
use App\Models\StudentAttendanceRecord;
use Carbon\Carbon;
use Illuminate\Http\Request;
use App\Models\ClassRoom;
use App\Models\Section;
use Illuminate\Support\Facades\Log;

class EduraAttendanceController extends Controller
{
    /**
     * جلب معدل الحضور العام لليوم مع الفلترة
     */
    public function getOverallAttendanceRate(Request $request)
    {
        $date = $request->get('date', Carbon::today()->format('Y-m-d'));
        $classId = $request->get('class_id');
        $sectionId = $request->get('section_id');
        
        // بناء الاستعلام بناءاً على الفلاتر
        $query = Student::query();
        
        if ($classId) {
            $query->where('class_id', $classId);
        }
        
        if ($sectionId) {
            $query->where('section_id', $sectionId);
        }
        
        $totalStudents = $query->count();
        
        if ($totalStudents === 0) {
            return response()->json([
                'attendance_rate' => 0,
                'total_students' => 0,
                'present_students' => 0,
                'absent_students' => 0,
                'permission_students' => 0,
                'pending_students' => 0,
                'date' => $date
            ]);
        }

        // جلب جميع سجلات الحضور للطلاب المفلترين
        $students = $query->with(['student_attendance_records' => function($q) use ($date) {
            $q->whereDate('created_at', $date)->latest();
        }])->get();

        $presentStudents = 0;
        $absentStudents = 0;
        $permissionStudents = 0;
        $pendingStudents = 0;

        foreach ($students as $student) {
            $record = $student->student_attendance_records->first();
            $statusKey = $this->getStatusKey($record);
            
            switch ($statusKey) {
                case 'presence':
                    $presentStudents++;
                    break;
                case 'absence':
                    $absentStudents++;
                    break;
                case 'permission':
                    $permissionStudents++;
                    break;
                default:
                    $pendingStudents++;
                    break;
            }
        }

        // حساب معدل الحضور
        $attendanceRate = $totalStudents > 0 ? round(($presentStudents / $totalStudents) * 100, 2) : 0;

        return response()->json([
            'attendance_rate' => $attendanceRate,
            'total_students' => $totalStudents,
            'present_students' => $presentStudents,
            'absent_students' => $absentStudents,
            'permission_students' => $permissionStudents,
            'pending_students' => $pendingStudents,
            'date' => $date
        ]);
    }

    /**
     * جلب تقرير الحضور اليومي المفصل لـ Edura مع الفلترة
     */
    public function getDailyReport(Request $request)
    {
        $date = $request->get('date', Carbon::today()->format('Y-m-d'));
        $classId = $request->get('class_id');
        $sectionId = $request->get('section_id');
        $status = $request->get('status');
    
        try {
            // بناء استعلام الفصول
            $classroomsQuery = ClassRoom::whereHas('students');
            
            if ($classId) {
                $classroomsQuery->where('id', $classId);
            }
    
            // تحميل العلاقات مع معالجة الأخطاء
            $classrooms = $classroomsQuery->with(['sections' => function($query) {
                // استخدام whereExists بدلاً من whereHas لتجنب المشاكل
                $query->whereExists(function ($subQuery) {
                    $subQuery->select('id')
                        ->from('students')
                        ->whereColumn('students.section_id', 'sections.id');
                });
            }])->get(['id', 'name']);
    
            // بناء استعلام الطلاب
            $studentsQuery = Student::select('id', 'name', 'class_id', 'section_id');
                        
            if ($classId) {
                $studentsQuery->where('class_id', $classId);
            }
            
            if ($sectionId) {
                $studentsQuery->where('section_id', $sectionId);
            }
    
            // ⭐⭐ التعديل المهم: تطبيق فلترة التاريخ على سجلات الحضور ⭐⭐
            $students = $studentsQuery->with(['student_attendance_records' => function($query) use ($date) {
                                $query->whereDate('created_at', $date)->latest()->limit(1); 
                            }])
                            ->get();
            
            // تجميع الطلاب حسب الفصل والشعبة
            $studentsByClass = $students->groupBy('class_id');
    
            $report = $classrooms->map(function ($class) use ($studentsByClass, $status, $date) {
                
                $classStudents = $studentsByClass->get($class->id, collect());
                
                // تطبيق فلترة الحالة إذا كانت محددة
                if ($status) {
                    $classStudents = $classStudents->filter(function ($student) use ($status) {
                        $record = $student->student_attendance_records->first();
                        $statusKey = $this->getStatusKey($record);
                        return $statusKey === $status;
                    });
                }
                
                // تجميع الطلاب حسب الشعبة
                $studentsBySection = $classStudents->groupBy('section_id');
    
                // الحصول على الشعب المرتبطة بهذا الفصل
                $sections = collect();
                if ($class->sections) {
                    $sections = $class->sections->map(function ($section) use ($studentsBySection, $date) {
                        $sectionStudents = $studentsBySection->get($section->id, collect());
                        return [
                            'section_id' => $section->id,
                            'section_name' => $section->name,
                            'students' => $sectionStudents->map(fn($s) => $this->formatStudentStatus($s, $date))->values(),
                        ];
                    })->filter(fn($section) => count($section['students']) > 0);
                }
    
                // الطلاب الذين ليس لديهم شعبة (section_id = null أو 0)
                $students_no_section = $studentsBySection->get(null, collect())
                                             ->merge($studentsBySection->get(0, collect()))
                                             ->map(fn($s) => $this->formatStudentStatus($s, $date))
                                             ->values();
    
                return [
                    'class_id' => $class->id,
                    'class_name' => $class->name,
                    'sections' => $sections,
                    'students_no_section' => $students_no_section,
                ];
            });
    
            return response()->json($report);
    
        } catch (\Exception $e) {
            Log::error('[AttendanceReport] خطأ في جلب تقرير الحضور: ' . $e->getMessage(), [
                'exception' => $e
            ]);
            
            // إرجاع بيانات افتراضية في حالة الخطأ
            return response()->json($this->getDefaultAttendanceReportData());
        }
    }

    /**
     * دالة مساعدة لتحديد مفتاح الحالة
     */
    private function getStatusKey($record)
    {
        if (!$record) return 'pending';
        
        $status = $record->record_state;
        if (str_contains($status, 'حضور')) {
            return 'presence';
        } elseif (str_contains($status, 'غياب')) {
            return 'absence';
        } elseif (str_contains($status, 'اذن')) {
            return 'permission';
        }
        
        return 'pending';
    }

    /**
     * دالة مساعدة لتنسيق حالة الطالب
     */
    private function formatStudentStatus($student, $date = null)
    {
        $record = $student->student_attendance_records->first();
        $status = 'لم يسجل';
        $status_key = $this->getStatusKey($record);

        if ($record) {
            $status = $record->record_state;
        }

        return [
            'id' => $student->id,
            'name' => $student->name,
            'status_text' => $status,
            'status_key' => $status_key,
            'created_at' => $record ? $record->created_at : null,
            'class_id' => $student->class_id,
            'section_id' => $student->section_id,
        ];
    }

    /**
     * جلب إحصائيات الحضور للرسوم البيانية
     */
    public function getAttendanceStats(Request $request)
    {
        $date = $request->get('date', Carbon::today()->format('Y-m-d'));
        $classId = $request->get('class_id');
        $sectionId = $request->get('section_id');

        try {
            // استخدام الدالة المحسنة لجلب معدل الحضور
            $attendanceData = $this->getOverallAttendanceRate($request)->getData(true);
            
            // جلب توزيع الحالات
            $statusDistribution = $this->getStatusDistribution($date, $classId, $sectionId);

            return response()->json([
                'attendance_rate' => $attendanceData['attendance_rate'],
                'total_students' => $attendanceData['total_students'],
                'status_distribution' => $statusDistribution,
                'date' => $date
            ]);
        } catch (\Exception $e) {
            Log::error('[AttendanceStats] خطأ في جلب إحصائيات الحضور: ' . $e->getMessage());
            
            return response()->json([
                'attendance_rate' => 0,
                'total_students' => 0,
                'status_distribution' => [
                    'counts' => ['presence' => 0, 'absence' => 0, 'permission' => 0, 'pending' => 0],
                    'percentages' => ['presence' => 0, 'absence' => 0, 'permission' => 0, 'pending' => 0]
                ],
                'date' => $date
            ]);
        }
    }

    /**
     * دالة مساعدة لجلب توزيع الحالات
     */
    private function getStatusDistribution($date, $classId = null, $sectionId = null)
    {
        $query = Student::query();
        
        if ($classId) {
            $query->where('class_id', $classId);
        }
        
        if ($sectionId) {
            $query->where('section_id', $sectionId);
        }
    
        $totalStudents = $query->count();
        
        if ($totalStudents === 0) {
            return [
                'counts' => [
                    'presence' => 0,
                    'absence' => 0,
                    'permission' => 0,
                    'pending' => 0
                ],
                'percentages' => [
                    'presence' => 0,
                    'absence' => 0,
                    'permission' => 0,
                    'pending' => 0
                ]
            ];
        }
    
        // ⭐⭐ التعديل: تطبيق فلترة التاريخ ⭐⭐
        $students = $query->with(['student_attendance_records' => function($q) use ($date) {
            $q->whereDate('created_at', $date)->latest()->limit(1);
        }])->get();
    
        $statusCounts = [
            'presence' => 0,
            'absence' => 0,
            'permission' => 0,
            'pending' => 0
        ];
    
        foreach ($students as $student) {
            $statusKey = $this->getStatusKey($student->student_attendance_records->first());
            $statusCounts[$statusKey]++;
        }
    
        return [
            'counts' => $statusCounts,
            'percentages' => [
                'presence' => $totalStudents > 0 ? round(($statusCounts['presence'] / $totalStudents) * 100, 2) : 0,
                'absence' => $totalStudents > 0 ? round(($statusCounts['absence'] / $totalStudents) * 100, 2) : 0,
                'permission' => $totalStudents > 0 ? round(($statusCounts['permission'] / $totalStudents) * 100, 2) : 0,
                'pending' => $totalStudents > 0 ? round(($statusCounts['pending'] / $totalStudents) * 100, 2) : 0,
            ]
        ];
    }

    /**
     * جلب الشعب بناءاً على الفصل المحدد
     */
    public function getSectionsByClass(Request $request)
    {
        $classId = $request->get('class_id');
        
        if (!$classId) {
            return response()->json([]);
        }

        try {
            $sections = Section::where('class_id', $classId)
                ->whereExists(function ($query) {
                    $query->select('id')
                        ->from('students')
                        ->whereColumn('students.section_id', 'sections.id');
                })
                ->get(['id', 'name']);

            return response()->json($sections);
        } catch (\Exception $e) {
            Log::error('[SectionsByClass] خطأ في جلب الشعب: ' . $e->getMessage());
            return response()->json([]);
        }
    }

    /**
     * بيانات افتراضية لتقرير الحضور في حالة الخطأ
     */
    private function getDefaultAttendanceReportData()
    {
        return [
            [
                'class_id' => 1,
                'class_name' => 'فصل افتراضي',
                'sections' => [
                    [
                        'section_id' => 1,
                        'section_name' => 'شعبة افتراضية',
                        'students' => [
                            ['id' => 1, 'name' => 'طالب افتراضي 1', 'status_text' => 'حضور - presence', 'status_key' => 'presence', 'created_at' => now()],
                            ['id' => 2, 'name' => 'طالب افتراضي 2', 'status_text' => 'غياب - absence', 'status_key' => 'absence', 'created_at' => now()],
                        ]
                    ],
                ],
                'students_no_section' => [
                    ['id' => 3, 'name' => 'طالب بدون شعبة', 'status_text' => 'اذن - permission', 'status_key' => 'permission', 'created_at' => now()],
                ],
            ]
        ];
    }
}