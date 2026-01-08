<?php

// app/Http/Controllers/UserFilterController.php
namespace App\Http\Controllers;

use App\Models\User;
use App\Models\Student;
use App\Models\Employee;
use Illuminate\Http\Request;

class UserFilterController extends Controller
{
    public function filterUsers(Request $request)
    {
        $request->validate([
            'user_type' => 'nullable|string',
            'user_id' => 'nullable|integer|exists:users,id', // ✅ إضافة user_id للبحث عن طالب محدد
            'student_id' => 'nullable|integer|exists:students,id', // ✅ إضافة student_id للبحث عن طالب محدد
            'class_id' => 'nullable|exists:classes,id',
            'section_id' => 'nullable|exists:sections,id',
            'search' => 'nullable|string',
            'per_page' => 'nullable|integer',
            'page' => 'nullable|integer',
            'include_teacher_data' => 'nullable|string|in:true,false,1,0',
            'include_student_data' => 'nullable|string|in:true,false,1,0'
        ]);
    
        $perPage = $request->per_page ?? 15;
        $userType = $request->user_type;
        $includeTeacherData = filter_var($request->include_teacher_data, FILTER_VALIDATE_BOOLEAN);
        $includeStudentData = filter_var($request->include_student_data, FILTER_VALIDATE_BOOLEAN);
    
        // If specifically filtering for students, use the student filter method
        if ($userType === 'student') {
            return $this->filterStudents($request, $perPage);
        }
    
        // Original user filtering logic for other user types
        $query = User::query();
    
        if ($userType) {
            $query->where('user_type', $userType);
        }
    
        if ($request->search) {
            $query->where(function($q) use ($request) {
                $q->where('name', 'like', '%'.$request->search.'%')
                  ->orWhere('phone', 'like', '%'.$request->search.'%')
                  ->orWhere('email', 'like', '%'.$request->search.'%');
            });
        }
    
        // Eager load relationships based on user type
        if ($userType === 'teacher' && $includeTeacherData) {
            $query->with(['employee' => function($query) {
                $query->with(['department', 'subjects']);
            }]);
        } elseif ($userType === 'student' && $includeStudentData) {
            $query->with(['student' => function($query) {
                $query->with(['class', 'section']);
            }]);
        }
    
        $users = $query->paginate($perPage);
    
        // Transform the users collection
        $transformedUsers = collect($users->items())->map(function($user) use ($userType, $includeStudentData) {
            $data = [
                'id' => $user->id,
                'name' => $user->name,
                'phone' => $user->phone,
                'email' => $user->email,
                'user_type' => $user->user_type,
                'user_data' => $user,
                'has_user' => true
            ];
    
            if ($userType === 'teacher' && $user->employee) {
                $data['teacher_data'] = [
                    'id' => $user->employee->id,
                    'department_id' => $user->employee->department_id,
                    'address' => $user->employee->address,
                    'gender' => $user->employee->gender,
                    'classes' => $user->employee->classes,
                    'sections' => $user->employee->sections,
                    'subjects' => $user->employee->subjects,
                    'department' => $user->employee->department
                ];
            } elseif ($userType === 'student' && $user->student) {
                $data['student_data'] = [
                    'id' => $user->student->id,
                    'class_id' => $user->student->class_id,
                    'section_id' => $user->student->section_id,
                    'username' => $user->student->username,
                    'date_of_birth' => $user->student->date_of_birth,
                    'national_number' => $user->student->national_number,
                    'gender' => $user->student->gender,
                    'address' => $user->student->address,
                    'phone_number' => $user->student->phone_number,
                    'class_name' => $user->student->class->name ?? null,
                    'section_name' => $user->student->section->name ?? null
                ];
            }
    
            return $data;
        });
    
        return response()->json([
            'data' => $transformedUsers,
            'current_page' => $users->currentPage(),
            'per_page' => $users->perPage(),
            'total' => $users->total()
        ]);
    }

    protected function filterStudents(Request $request, $perPage)
    {
        $query = Student::with(['user', 'class', 'section'])
            ->when($request->student_id, function($q) use ($request) {
                // ✅ البحث عن طالب محدد باستخدام student_id (الأولوية)
                $q->where('id', $request->student_id);
            })
            ->when($request->user_id && !$request->student_id, function($q) use ($request) {
                // ✅ البحث عن طالب محدد باستخدام user_id (إذا لم يتم تحديد student_id)
                $q->whereHas('user', function($userQuery) use ($request) {
                    $userQuery->where('id', $request->user_id);
                });
            })
            ->when($request->class_id, function($q) use ($request) {
                $q->where('class_id', $request->class_id);
            })
            ->when($request->section_id, function($q) use ($request) {
                $q->where('section_id', $request->section_id);
            })
            ->when($request->search, function($q) use ($request) {
                $q->where(function($query) use ($request) {
                    $query->where('name', 'like', '%'.$request->search.'%')
                        ->orWhere('national_number', 'like', '%'.$request->search.'%')
                        ->orWhere('srn', 'like', '%'.$request->search.'%')
                        ->orWhereHas('user', function($userQuery) use ($request) {
                            $userQuery->where('phone', 'like', '%'.$request->search.'%')
                                     ->orWhere('email', 'like', '%'.$request->search.'%');
                        });
                });
            });
    
        $students = $query->paginate($perPage);
    
        return response()->json([
            'data' => $students->map(function($student) {
                // Check if user exists
                if (!$student->user) {
                    return [
                        'id' => null,
                        'name' => $student->name,
                        'phone' => $student->phone_number,
                        'email' => null,
                        'user_type' => 'student',
                        'class_id' => $student->class_id,
                        'class_name' => $student->class?->name,
                        'section_id' => $student->section_id,
                        'section_name' => $student->section?->name,
                        'student_data' => [
                            'id' => $student->id,
                            'name' => $student->name,
                            'class_id' => $student->class_id,
                            'section_id' => $student->section_id,
                            'username' => $student->user?->username,
                            'date_of_birth' => $student->date_of_birth,
                            'national_number' => $student->national_number,
                            'gender' => $student->gender,
                            'address' => $student->address,
                            'phone_number' => $student->phone_number,
                            'class_name' => $student->class?->name,
                            'section_name' => $student->section?->name
                        ],
                        'has_user' => false
                    ];
                }
    
                return [
                    'id' => $student->user->id,
                    'name' => $student->user->name,
                    'phone' => $student->user->phone,
                    'email' => $student->user->email,
                    'username' => $student->user->username,
                    'user_type' => $student->user->user_type,
                    'class_id' => $student->class_id,
                    'class_name' => $student->class?->name,
                    'section_id' => $student->section_id,
                    'section_name' => $student->section?->name,
                    'student_data' => [
                        'id' => $student->id,
                        'name' => $student->name,
                        'username' => $student->user->username,
                        'class_id' => $student->class_id,
                        'section_id' => $student->section_id,
                        'date_of_birth' => $student->date_of_birth,
                        'national_number' => $student->national_number,
                        'gender' => $student->gender,
                        'address' => $student->address,
                        'phone_number' => $student->phone_number,
                        'class_name' => $student->class?->name,
                        'section_name' => $student->section?->name
                    ],
                    'has_user' => true
                ];
            }),
            'current_page' => $students->currentPage(),
            'per_page' => $students->perPage(),
            'total' => $students->total()
        ]);
    }

    protected function filterTeachers(Request $request, $perPage)
    {
        $query = Employee::with(['user', 'classes', 'sections', 'department'])
            ->where(function($q) {
                $q->where('is_teacher', true)
                  ->orWhereHas('user', function($userQuery) {
                      $userQuery->where('user_type', 'teacher');
                  });
            })
            ->when($request->class_id, function($q) use ($request) {
                $q->whereHas('classes', function($classQuery) use ($request) {
                    $classQuery->where('classes.id', $request->class_id);
                });
            })
            ->when($request->section_id, function($q) use ($request) {
                $q->whereHas('sections', function($sectionQuery) use ($request) {
                    $sectionQuery->where('sections.id', $request->section_id);
                });
            })
            ->when($request->search, function($q) use ($request) {
                $q->where(function($query) use ($request) {
                    $query->where('name', 'like', '%'.$request->search.'%')
                        ->orWhere('national_number', 'like', '%'.$request->search.'%')
                        ->orWhere('phone_number', 'like', '%'.$request->search.'%')
                        ->orWhereHas('user', function($userQuery) use ($request) {
                            $userQuery->where('phone', 'like', '%'.$request->search.'%')
                                     ->orWhere('email', 'like', '%'.$request->search.'%')
                                     ->orWhere('name', 'like', '%'.$request->search.'%');
                        });
                });
            });
    
        $teachers = $query->paginate($perPage);
    
        return response()->json([
            'data' => $teachers->map(function($teacher) {
                $user = $teacher->user;
                // Get the first class (assuming a teacher might have multiple classes)
                $class = $teacher->classes->first();
                // Get the first section (assuming a teacher might have multiple sections)
                $section = $teacher->sections->first();
                
                return [
                    'id' => $user ? $user->id : null,
                    'name' => $user ? $user->name : $teacher->name,
                    'phone' => $user ? $user->phone : $teacher->phone_number,
                    'email' => $user ? $user->email : null,
                    'user_type' => $user ? $user->user_type : 'teacher',
                    'class_id' => $class ? $class->id : null,
                    'class_name' => $class ? $class->name : null,
                    'section_id' => $section ? $section->id : null,
                    'section_name' => $section ? $section->name : null,
                    'teacher_data' => $teacher,
                    'has_user' => (bool)$user
                ];
            }),
            'current_page' => $teachers->currentPage(),
            'per_page' => $teachers->perPage(),
            'total' => $teachers->total()
        ]);
    }

    protected function filterGeneralUsers(Request $request, $perPage)
    {
        $query = User::when($request->user_type, function($q) use ($request) {
                $q->where('user_type', $request->user_type);
            })
            ->when($request->search, function($q) use ($request) {
                $q->where(function($query) use ($request) {
                    $query->where('name', 'like', '%'.$request->search.'%')
                        ->orWhere('phone', 'like', '%'.$request->search.'%')
                        ->orWhere('email', 'like', '%'.$request->search.'%')
                        ->orWhere('username', 'like', '%'.$request->search.'%');
                });
            });

        $users = $query->paginate($perPage);

        return response()->json([
            'data' => $users->map(function($user) {
                return [
                    'id' => $user->id,
                    'name' => $user->name,
                    'phone' => $user->phone,
                    'email' => $user->email,
                    'user_type' => $user->user_type,
                    'user_data' => $user,
                    'has_user' => true
                ];
            }),
            'current_page' => $users->currentPage(),
            'per_page' => $users->perPage(),
            'total' => $users->total()
        ]);
    }
}