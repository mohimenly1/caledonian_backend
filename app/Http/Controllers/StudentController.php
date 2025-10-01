<?php

namespace App\Http\Controllers;

use App\Models\ActivityLog;
use App\Models\Section;
use App\Models\Student;
use App\Models\User;
use App\Notifications\DeleteStudentNotification;
use App\Notifications\NewStudentNotification;
use App\Models\ClassRoom;
use App\Models\StudyYear;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use App\Models\Invoice;

class StudentController extends Controller
{

    public function getProfile(Request $request)
    {
        $user = $request->user();
        
        $student = Student::where('user_id', $user->id)
            ->with(['class', 'section'])
            ->firstOrFail();
            
        return response()->json([
            'class_id' => $student->class_id,
            'section_id' => $student->section_id,
            'class_name' => $student->class->name ?? null,
            'section_name' => $student->section->name ?? null,
        ]);
    }
    public function fetchingBigReport(Request $request)
    {
        // Fetch all students with their parents and financial documents
        $students = Student::with(['parent', 'financialDocuments'])->get();

        return response()->json($students);
    }
    public function filteringStudent(Request $request)
    {
        $search = $request->query('search');
        $classId = $request->query('class_id');
        $sectionId = $request->query('section_id');
        $perPage = $request->query('per_page', 10);

        $query = Student::with(['user', 'parent', 'class', 'bus', 'section']);

        if ($search) {
            $query->where('name', 'LIKE', "%{$search}%")
                ->orWhereHas('bus', function ($query) use ($search) {
                    $query->where('number', 'LIKE', "%{$search}%");
                });
        }

        if ($classId) {
            $query->where('class_id', $classId);
        }

        if ($sectionId) {
            $query->where('section_id', $sectionId);
        }

        $students = $query->paginate($perPage);

        return response()->json($students);
    }

    public function filteringStudentCard(Request $request)
    {
        $search = $request->query('search');
        $classId = $request->query('class_id');
        $sectionId = $request->query('section_id');

        $query = Student::with(['user', 'parent', 'class', 'bus', 'section']);

        if ($search) {
            $query->where('name', 'LIKE', "%{$search}%")
                ->orWhereHas('bus', function ($query) use ($search) {
                    $query->where('number', 'LIKE', "%{$search}%");
                });
        }

        if ($classId) {
            $query->where('class_id', $classId);
        }

        if ($sectionId) {
            $query->where('section_id', $sectionId);
        }

        // Get all students without pagination
        $students = $query->get();

        return response()->json($students);
    }

    public function index(Request $request)
    {
        $query = Student::with(['parent:id,first_name,last_name,phone_number_one,pin_code', 'class:id,name', 'section:id,name', 'studyYear:id,name']);

        if ($request->filled('study_year_id')) {
            $query->where('study_year_id', $request->study_year_id);
        }
        if ($request->filled('class_id')) {
            $query->where('class_id', $request->class_id);
        }
        if ($request->filled('section_id')) {
            $query->where('section_id', $request->section_id);
        }
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('arabic_name', 'like', "%{$search}%")
                  ->orWhere('srn', 'like', "%{$search}%");
            });
        }

        $students = $query->latest()->paginate($request->input('per_page', 10));

        return response()->json($students);
    }

    public function indexForDoc(Request $request)
    {
        $search = $request->query('search');

        $query = Student::with(['user', 'parent', 'class', 'bus']);

        if ($search) {
            $query->where('name', 'LIKE', "%{$search}%")
                ->orWhereHas('bus', function ($query) use ($search) {
                    $query->where('number', 'LIKE', "%{$search}%");
                });
        }

        // Fetch all students instead of paginating
        $students = $query->get();


        ActivityLog::create([
            'user_id' => auth()->user()->id,
            'action' => 'indexForDoc - search',
            'description' => 'Created a new record in Model',
            'new_data' => json_encode($students),
            'created_at' => now(), // Manually set the created_at timestamp
        ]);

        return response()->json($students);
    }


    public function search(Request $request)
    {
        $search = $request->query('search');

        $query = Student::with(['user', 'parent', 'class', 'bus']);

        if ($search) {
            $query->where('name', 'LIKE', "%{$search}%")
                ->orWhereHas('bus', function ($query) use ($search) {
                    $query->where('number', 'LIKE', "%{$search}%");
                });
        }

        // Fetch all matching students without pagination
        $students = $query->get();

        ActivityLog::create([
            'user_id' => auth()->user()->id,
            'action' => 'search',
            'description' => 'Created a new record in Model',
            'new_data' => json_encode($students),
            'created_at' => now(), // Manually set the created_at timestamp
        ]);

        return response()->json($students);
    }

    public function store(Request $request)
    {
        $validatedData = $request->validate([
            'name' => 'required|string|max:255',
            'arabic_name' => 'nullable|string|max:255',
            'date_of_birth' => 'required|date',
            'gender' => 'required|in:male,female',
            'parent_id' => 'required|exists:parents,id',
            'study_year_id' => 'required|exists:study_years,id',
            'class_id' => 'required|exists:classes,id',
            'section_id' => 'nullable|exists:sections,id',
            'address' => 'nullable|string|max:255',
            'passport_num' => 'nullable|string|max:255',
            'has_books' => 'required|in:yes,no',
            'daily_allowed_purchase_value' => 'nullable|numeric|min:0',
            'note' => 'nullable|string',
            'missing' => 'nullable|string',
            'photo' => 'nullable|image|mimes:jpeg,png,jpg|max:2048',
        ]);

        // --- توليد رقم القيد تلقائيًا ---
        $validatedData['srn'] = (Student::withTrashed()->max('srn') ?? 1000) + 1;

        if ($request->hasFile('photo')) {
            $validatedData['photo'] = $request->file('photo')->store('student_photos', 'public');
        }

        $student = Student::create($validatedData);

        return response()->json($student, 201);
    }





    public function show(Student $student)
    {
        return response()->json($student->load(['parent', 'class', 'section', 'studyYear']));
    }

    public function getStudentInvoices(Student $student)
    {
        $invoices = Invoice::whereHas('items', function ($query) use ($student) {
            $query->where('student_id', $student->id);
        })
        ->withSum('payments as paid_amount', 'amount')
        ->get()
        ->map(function ($invoice) {
            $invoice->paid_amount = $invoice->paid_amount ?? 0;
            $invoice->remaining_amount = $invoice->final_amount - $invoice->paid_amount;
            return $invoice;
        });

        return response()->json($invoices);
    }


    public function getFormData()
    {
        $activeStudyYear = StudyYear::where('is_active', true)->first();
        $classes = $activeStudyYear ? ClassRoom::where('study_year_id', $activeStudyYear->id)->get(['id', 'name']) : [];

        return response()->json([
            'study_years' => StudyYear::where('is_active', true)->get(['id', 'name']),
            'classes' => $classes,
            // You can add parents, buses etc. here as needed
        ]);
    }
    public function updateStudentGender(Request $request, $id)
    {
        // Validate only the gender field
        $request->validate([
            'gender' => 'required|in:male,female',
        ]);

        // Find the student by ID
        $student = Student::find($id);

        // Check if the student exists
        if ($student) {
            // Update only the gender field
            $student->update(['gender' => $request->gender]);

            // Log the activity
            ActivityLog::create([
                'user_id' => auth()->user()->id,
                'action' => 'update',
                'description' => 'Updated student gender',
                'new_data' => json_encode($student),
                'created_at' => now(),
            ]);

            return response()->json($student, 200);
        }

        return response()->json(['message' => 'Student not found'], 404);
    }


    public function update(Request $request, Student $student)
    {
        // Note: Using POST with _method=PUT for file uploads
        $validatedData = $request->validate([
            'name' => 'required|string|max:255',
            'arabic_name' => 'nullable|string|max:255',
            'date_of_birth' => 'required|date',
            'gender' => 'required|in:male,female',
            'parent_id' => 'required|exists:parents,id',
            'study_year_id' => 'required|exists:study_years,id',
            'class_id' => 'required|exists:classes,id',
            'section_id' => 'nullable|exists:sections,id',
            'address' => 'nullable|string|max:255',
            'passport_num' => 'nullable|string|max:255',
            'has_books' => 'required|in:yes,no',
            'daily_allowed_purchase_value' => 'nullable|numeric|min:0',
            'note' => 'nullable|string',
            'missing' => 'nullable|string',
            'photo' => 'nullable|image|mimes:jpeg,png,jpg|max:2048',
        ]);

        if ($request->hasFile('photo')) {
            // Delete old photo if it exists
            if ($student->photo) {
                Storage::disk('public')->delete($student->photo);
            }
            $validatedData['photo'] = $request->file('photo')->store('student_photos', 'public');
        }

        $student->update($validatedData);

        return response()->json($student);
    }





    public function destroy($id)
    {
        $student = Student::find($id);
        if ($student) {
            // Log and create activity log
            ActivityLog::create([
                'user_id' => auth()->user()->id,
                'action' => 'delete',
                'description' => 'Deleted a student record',
                'new_data' => json_encode($student),
                'created_at' => now(),
            ]);

            Log::info('About to delete student:', ['student_id' => $student->id, 'student_name' => $student->name]);

            // Notify admins
            $admins = User::where('user_type', 'admin')->get();
            Log::info('Found ' . $admins->count() . ' admins to notify.');

            foreach ($admins as $admin) {
                $admin->notify(new DeleteStudentNotification($student)); // Pass the student instance
                Log::info('Sent delete notification to admin:', ['admin_id' => $admin->id]);
            }

            $student->delete();

            return response()->json(['message' => 'Student deleted successfully'], 200);
        }
        return response()->json(['message' => 'Student not found'], 404);
    }





    public function getStudentsByClassAndSection(Request $request)
    {
        $classId = $request->query('class_id');
        $sectionId = $request->query('section_id');

        $students = Student::where('class_id', $classId)
            ->where('section_id', $sectionId)
            ->get();

        return response()->json($students);
    }


    public function getParent($studentId)
    {
        // Find the student by ID
        $student = Student::findOrFail($studentId);

        // Fetch the parent related to this student
        $parent = $student->parent;

        if (!$parent) {
            return response()->json(['message' => 'Parent record not found.'], 404);
        }

        return response()->json($parent);
    }

    public function getStudentsByClass(Request $request)
    {
        $classId = $request->query('class_id');

        $students = Student::where('class_id', $classId)->get();

        return response()->json($students);
    }

    public function getSectionsByClass($classId)
    {
        $sections = Section::where('class_id', $classId)->get();
        return response()->json($sections);
    }

    public function getSectionsByClassEmp(Request $request, $classId)
    {
        $sections = Section::where('class_id', $classId)
            ->with('subjects') // Eager load subjects
            ->get();

        return response()->json($sections);
    }
}
