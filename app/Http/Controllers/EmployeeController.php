<?php

namespace App\Http\Controllers;

use App\Models\Department;
use App\Models\Employee;
use App\Models\EmployeeType;
use App\Models\TeacherType;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Auth;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class EmployeeController extends Controller
{

    public function addSubjects(Employee $employee, Request $request)
    {
        $request->validate([
            'subject_ids' => 'required|array',
            'subject_ids.*' => 'exists:subjects,id'
        ]);
    
        $employee->subjects()->attach($request->subject_ids);
    
        return response()->json([
            'message' => 'Subjects added successfully',
            'employee' => $employee->load('subjects')
        ]);
    }

    public function syncSubjects(Employee $employee, Request $request)
    {
        $request->validate([
            'subject_ids' => 'array', // Remove 'required' to allow empty arrays
            'subject_ids.*' => 'exists:subjects,id' // Still validate that if IDs exist, they're valid
        ]);
    
        // This will remove all relationships if empty array is provided
        $employee->subjects()->sync($request->input('subject_ids', []));
    
        return response()->json([
            'message' => 'Subjects synchronized successfully',
            'employee' => $employee->load('subjects')
        ]);
    }


    public function getTeacherClassesAndSections(Request $request)
    {
        // Get the authenticated user
        $user = Auth::user();
    
        // Check if the user is a teacher
        if ($user->user_type !== 'teacher') {
            return response()->json(['message' => 'The logged-in user is not a teacher'], 400);
        }
    
        // Find the employee associated with the user
        $employee = Employee::where('user_id', $user->id)->first();
    
        if (!$employee) {
            return response()->json(['message' => 'Employee not found'], 404);
        }
    
        // Retrieve the classes and sections associated with the employee
        $classes = $employee->classes()->with('sections')->get();
        $sections = $employee->sections()->get();
    
        return response()->json([
            'message' => 'Classes and sections retrieved successfully',
            'classes' => $classes,
            'sections' => $sections,
        ]);
    }

    public function filterEmployeesByTeacherType(Request $request)
    {
        $teacherTypeId = $request->input('teacher_type_id');

        // Query to filter employees based on teacher type
        $employees = Employee::whereExists(function ($query) use ($teacherTypeId) {
            $query->select(DB::raw(1))
                ->from('employee_teacher_type')
                ->join('teachers_type', 'teachers_type.id', '=', 'employee_teacher_type.teacher_type_id')
                ->where('employees.id', '=', 'employee_teacher_type.employee_id')
                ->where('teachers_type.id', '=', $teacherTypeId); // Use qualified name
        })->get();

        return response()->json($employees);
    }

    public function updateEmployeeSalary(Request $request, $employeeId)
    {
        $employee = Employee::findOrFail($employeeId);

        // Validate the incoming data
        $validatedData = $request->validate([
            'salary' => 'required|numeric',
            'deductions' => 'array',
        ]);

        // Update salary (assuming you have a `salary` column or model)
        $employee->salary()->updateOrCreate([], ['amount' => $validatedData['salary']]);

        // Update deductions if provided
        if (isset($validatedData['deductions'])) {
            $employee->deductions()->sync($validatedData['deductions']);
        }

        return response()->json([
            'message' => 'Employee salary updated successfully',
            'employee' => $employee
        ]);
    }


    public function getFinancialData($employeeId)
    {
        // Fetch the employee's salary, bonuses, and deductions
        $employee = Employee::with(['salary', 'deductions.deductionType'])->findOrFail($employeeId);

        return response()->json([
            'employee' => $employee,
            'salary' => $employee->salary, // Assuming there's a salary relationship
            'deductions' => $employee->deductions // Assuming deductions are related to the employee
        ]);
    }
    public function showFinancial($id)
    {
        $employee = Employee::with(['salary', 'teacherType', 'department', 'section', 'classRoom'])->findOrFail($id);

        return response()->json([
            'employee' => $employee,
            'salary' => $employee->salary,
        ]);
    }



    public function show($id)
    {
        $employee = Employee::with('department',  'classes', 'sections', 'employeeType','subjects')->findOrFail($id);

        $employee->photos = json_decode($employee->photos); // Ensure it's returned as an array
        $employee->attached_files = json_decode($employee->attached_files); // Ensure it's returned as an array

        // Convert file paths to full URLs
        $employee->photos = array_map(function ($photo) {
            return asset('storage/' . $photo); // This will create a full URL
        }, $employee->photos ?? []);

        $employee->attached_files = array_map(function ($file) {
            return asset('storage/' . $file); // This will create a full URL
        }, $employee->attached_files ?? []);


        return response()->json($employee);
    }

    public function getDepartmentsAndTeacherTypes()
    {
        $departments = Department::all();
        $teacherTypes = TeacherType::all();
        $employeeTypes = EmployeeType::all();

        return response()->json([
            'departments' => $departments,
            'teacherTypes' => $teacherTypes,
            'employeeTypes' => $employeeTypes
        ]);
    }

    public function indexForabsenceAndDeduction(Request $request)
    {

        $employees = Employee::with(['department', 'teacherTypes', 'employeeType', 'sections', 'classes'])->get();



        // Filter by name
        if ($request->filled('name')) {
            $employees->where('name', 'like', '%' . $request->name . '%');
        }

        // Filter by ID
        if ($request->filled('id')) {
            $employees->where('id', $request->id);
        }

        // Filter by national number
        if ($request->filled('national_number')) {
            $employees->where('national_number', 'like', '%' . $request->national_number . '%');
        }

        // Filter by passport number
        if ($request->filled('passport_number')) {
            $employees->where('passport_number', 'like', '%' . $request->passport_number . '%');
        }

        // Filter by phone number
        if ($request->filled('phone_number')) {
            $employees->where('phone_number', 'like', '%' . $request->phone_number . '%');
        }

        // Filter by department
        if ($request->filled('department_id')) {
            $employees->where('department_id', $request->department_id);
        }

        // Filter by employee type
        if ($request->filled('employee_type_id')) {
            $employees->where('employee_type_id', $request->employee_type_id);
        }



        // Log::info($query->toSql(), $query->getBindings());

        // Pagination

        return response()->json($employees);
    }

    public function indexForIssuedSalaries(Request $request)
    {
        $query = Employee::with(['department', 'teacherTypes', 'employeeType', 'sections', 'classes'])->whereNotNull('base_salary');

        // Filter by name
        if ($request->filled('name')) {
            $query->where('name', 'like', '%' . $request->name . '%');
        }

        // Filter by ID
        if ($request->filled('id')) {
            $query->where('id', $request->id);
        }

        // Filter by national number
        if ($request->filled('national_number')) {
            $query->where('national_number', 'like', '%' . $request->national_number . '%');
        }

        // Filter by passport number
        if ($request->filled('passport_number')) {
            $query->where('passport_number', 'like', '%' . $request->passport_number . '%');
        }

        // Filter by phone number
        if ($request->filled('phone_number')) {
            $query->where('phone_number', 'like', '%' . $request->phone_number . '%');
        }

        // Filter by department
        if ($request->filled('department_id')) {
            $query->where('department_id', $request->department_id);
        }

        // Filter by employee type
        if ($request->filled('employee_type_id')) {
            $query->where('employee_type_id', $request->employee_type_id);
        }

        if ($request->filled('teacher_type_id')) {
            $query->join('employee_teacher_type', 'employees.id', '=', 'employee_teacher_type.employee_id')
                ->where('employee_teacher_type.teacher_type_id', $request->teacher_type_id);
        }

        // Log::info($query->toSql(), $query->getBindings());

        // Pagination
        $perPage = $request->input('per_page', 10);
        $employees = $query->paginate($perPage);

        return response()->json($employees);
    }
    public function index(Request $request)
    {
        $query = Employee::with(['department', 'employeeType', 'sections', 'classes']);

        // Filter by name
        if ($request->filled('name')) {
            $query->where('name', 'like', '%' . $request->name . '%');
        }

        // Filter by ID
        if ($request->filled('id')) {
            $query->where('id', $request->id);
        }

        // Filter by national number
        if ($request->filled('national_number')) {
            $query->where('national_number', 'like', '%' . $request->national_number . '%');
        }

        // Filter by passport number
        if ($request->filled('passport_number')) {
            $query->where('passport_number', 'like', '%' . $request->passport_number . '%');
        }

        // Filter by phone number
        if ($request->filled('phone_number')) {
            $query->where('phone_number', 'like', '%' . $request->phone_number . '%');
        }

        // Filter by department
        if ($request->filled('department_id')) {
            $query->where('department_id', $request->department_id);
        }

        // Filter by employee type
        if ($request->filled('employee_type_id')) {
            $query->where('employee_type_id', $request->employee_type_id);
        }

        if ($request->filled('teacher_type_id')) {
            $query->join('employee_teacher_type', 'employees.id', '=', 'employee_teacher_type.employee_id')
                ->where('employee_teacher_type.teacher_type_id', $request->teacher_type_id);
        }

        // Log::info($query->toSql(), $query->getBindings());

        // Pagination
        $perPage = $request->input('per_page', 10);
        $employees = $query->orderBy('name')->paginate($perPage);

        return response()->json($employees);
    }



    // public function index()
    // {
    //     $employees = Employee::with(['department', 'teacherTypes','employeeType','sections','classes'])->get();

    //     return response()->json($employees);
    // }


    // app/Http/Controllers/EmployeeController.php
    // app/Http/Controllers/EmployeeController.php
    // app/Http/Controllers/EmployeeController.php


    public function addClasses(Employee $employee, Request $request)
    {
        $request->validate([
            'class_ids' => 'required|array',
            'class_ids.*' => 'exists:classes,id'
        ]);
    
        $employee->classes()->attach($request->class_ids);
    
        return response()->json([
            'message' => 'Classes added successfully',
            'employee' => $employee->load('classes')
        ]);
    }

    public function syncClasses(Employee $employee, Request $request)
    {
        $request->validate([
            'class_ids' => 'array',
            'class_ids.*' => 'exists:classes,id'
        ]);
    
        $employee->classes()->sync($request->input('class_ids', []));
    
        return response()->json([
            'message' => 'Classes synchronized successfully',
            'employee' => $employee->load('classes')
        ]);
    }

public function addSections(Employee $employee, Request $request)
{
    $request->validate([
        'section_ids' => 'required|array',
        'section_ids.*' => 'exists:sections,id'
    ]);

    $employee->sections()->attach($request->section_ids);

    return response()->json([
        'message' => 'Sections added successfully',
        'employee' => $employee->load('sections')
    ]);
}

public function syncSections(Employee $employee, Request $request)
{
    $request->validate([
        'section_ids' => 'required|array',
        'section_ids.*' => 'exists:sections,id'
    ]);

    // This will remove all relationships if empty array is provided
    $employee->sections()->sync($request->section_ids);

    return response()->json([
        'message' => 'Sections synchronized successfully',
        'employee' => $employee->load('sections')
    ]);
}
    public function store(Request $request)
    {



        // Validate the incoming request data
        $request->validate([
            'name' => 'required|string',
            'user_id' => 'nullable|unique:employees,user_id', // Add this line
            'department_id' => 'nullable|exists:departments,id',
            'subject_ids' => 'nullable|array', // Changed from teacher_type_ids
            'subject_ids.*' => 'exists:subjects,id', // Validate against subjects table
            'national_number' => 'nullable|unique:employees,national_number',
            'phone_number' => 'required|string',
            'phone_number_two' => 'nullable|string',
            'address' => 'required|string',
            'years_of_experience' => 'nullable|integer',
            'base_salary' => 'nullable|integer',
            'pin' => 'nullable|string|unique:employees,pin',
            'fingerprint_id' => 'nullable|string|unique:employees,fingerprint_id',
            'employee_type_id' => 'nullable|exists:employee_types,id',
            'photos.*' => 'nullable|file|mimes:jpeg,png,jpg|max:2048', // Allow multiple photos
            'teacher_type_ids' => 'nullable|array', // Allow an array for teacher types
            'teacher_type_ids.*' => 'exists:teachers_type,id', // Each ID must exist in the teacher type table
            'class_ids' => 'nullable|array', // Allow an array for classes
            'class_ids.*' => 'exists:classes,id', // Each ID must exist in the classes table
            'section_ids' => 'nullable|array', // Allow an array for sections
            'section_ids.*' => 'exists:sections,id', // Each ID must exist in the sections table
            'is_teacher' => 'nullable|boolean',
        ]);

        $user = User::create([
            'name' => $request->input('name'),
            'phone' => $request->input('phone_number'),
            'username' => 'Cale' . Str::random(4), // Generates "Cale" + 4 random chars
            'email' => Str::slug($request->input('name')) . '@caledonian.com',
            'user_type' => $request->input('is_teacher') ? 'teacher' : 'staff',
            'address' => $request->input('address'),
            'password' => $request->input('Cale@@123'),
        ]);

        // Create a new Employee instance
        $employee = new Employee([
            'name' => $request->input('name'),
            'user_id' => $user->id, // Assign the newly created user ID
            
            'department_id' => $request->input('department_id'),
            'national_number' => $request->input('national_number'),
            'phone_number' => $request->input('phone_number'),
            'phone_number_two' => $request->input('phone_number_two'),
            'address' => $request->input('address'),
            'years_of_experience' => $request->input('years_of_experience'),
            'base_salary' => $request->input('base_salary'),
            'pin' => $request->input('pin'),
            'fingerprint_id' => $request->input('fingerprint_id'),
            'gender' => $request->input('gender'), // New field
            'date_of_birth' => $request->input('date_of_birth'), // New field
            'date_of_join' => $request->input('date_of_join'), // New field
            'passport_number' => $request->input('passport_number'), // New field
            'is_teacher' => $request->input('is_teacher', false),
            'employee_type_id' => $request->input('employee_type_id'),
        ]);

   

        // Handle attached files
        $photos = [];
        if ($request->hasFile('photos')) {
            foreach ($request->file('photos') as $photo) {
                $photos[] = $photo->store('employees/photos', 'public');
            }
        }
        $employee->photos = json_encode($photos);

        $attachedFiles = [];
        if ($request->hasFile('attached_files')) {
            foreach ($request->file('attached_files') as $file) {
                $attachedFiles[] = $file->store('employees/files', 'public');
            }
        }
        $employee->attached_files = json_encode($attachedFiles);

        // Save the employee record
        $employee->save();

        if ($request->has('subject_ids')) {
            $employee->subjects()->attach($request->input('subject_ids'));
        }

        // Attach the optional relationships
        if ($request->has('teacher_type_ids')) {
            $employee->teacherTypes()->attach($request->input('teacher_type_ids'));
        }

        if ($request->has('class_ids')) {
            $employee->classes()->attach($request->input('class_ids'));
        }

        if ($request->has('section_ids')) {
            $employee->sections()->attach($request->input('section_ids'));
        }

        return response()->json([
            'message' => 'Employee created successfully', 
            'employee' => $employee,
            'user' => $user
        
        ]);
    }









    // Update the employee's details
    public function update(Request $request, $id)
    {
        // Find the existing employee record
        $employee = Employee::findOrFail($id);

        // Validate incoming request
        try {
            $validatedData = $request->validate([
                'name' => 'nullable|string|max:255',
                'user_id' => 'nullable|integer|unique:employees,user_id,'.$employee->id,

                'gender' => 'nullable|string',
                'date_of_birth' => 'nullable|date',
                'date_of_join' => 'nullable|date',
                'passport_number' => 'nullable|string',
                'department_id' => 'nullable|exists:departments,id',
                'subject_ids' => 'nullable|array',
                'subject_ids.*' => 'exists:subjects,id',
                'class_ids' => 'nullable|array',
                'class_ids.*' => 'exists:classes,id',
                'section_ids' => 'nullable|array',
                'section_ids.*' => 'exists:sections,id',
                'employee_type_id' => 'nullable|exists:employee_types,id',
                'national_number' => 'nullable|string',
                'phone_number' => 'nullable|string',
                'phone_number_two' => 'nullable|string',
                'address' => 'nullable|string',
                'pin' => 'nullable',
                'fingerprint_id' => 'nullable|string',
                'years_of_experience' => 'nullable|integer|min:0',
                'base_salary' => 'nullable|min:0',
                'photos.*' => 'nullable|file|mimes:jpeg,png,jpg|max:2048',
                'attached_files.*' => 'nullable|file|mimes:pdf,doc,docx|max:2048',
                'is_teacher' => 'nullable|boolean',
            ]);
        } catch (\Illuminate\Validation\ValidationException $e) {
            Log::error('Validation errors: ', $e->errors());
            return response()->json($e->errors(), 422);
        }
        if ($request->filled('subject_ids')) {
            $employee->subjects()->sync($request->subject_ids);
        }
        // Remove fields that are related to many-to-many relationships before updating the employee
        $dataToUpdate = array_diff_key($validatedData, array_flip(['subject_ids', 'class_ids', 'section_ids']));

        // Update the employee fields using the validated data
        $employee->update(array_filter($dataToUpdate)); // Only update fields with non-null values

        if ($request->filled('subject_ids')) {
            $employee->subjects()->sync($request->subject_ids);
        }
        // Handle file uploads (photos)
        if ($request->hasFile('photos')) {
            $photos = [];
            foreach ($request->file('photos') as $photo) {
                $photos[] = $photo->store('employees', 'public');
            }
            $employee->photos = json_encode($photos);
        }

        // Handle attached files
        if ($request->hasFile('attached_files')) {
            $attachedFiles = [];
            foreach ($request->file('attached_files') as $file) {
                $attachedFiles[] = $file->store('employee_files', 'public');
            }
            $employee->attached_files = json_encode($attachedFiles);
        }

        if ($request->filled('class_ids')) {
            $employee->classes()->sync($request->class_ids);
        }
      

        Log::info('Employee updated successfully: ', $employee->toArray());

        return response()->json(['message' => 'Employee updated successfully']);
    }





    public function destroy($id)
    {
        $employee = Employee::findOrFail($id);
        if ($employee->photos) {
            foreach (json_decode($employee->photos, true) as $photo) {
                Storage::disk('public')->delete($photo);
            }
        }
        $employee->delete();
        return response()->json(['message' => 'Employee deleted successfully']);
    }
}
