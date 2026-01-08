<?php

namespace App\Http\Controllers;

use App\Models\ActivityLog;
use App\Models\ParentInfo;
use App\Models\Student;
use App\Models\StudyYear;
use App\Models\Section;
use App\Models\PrivateConversation;
use Illuminate\Support\Facades\Hash;
use App\Models\ClassRoom;
use App\Models\User;
use App\Notifications\NewParentNotification;
use App\Notifications\NewStudentNotification;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Maatwebsite\Excel\Facades\Excel;

class ParentInfoController extends Controller
{

    public function getChildTeachers($studentId)
{
    // Get the student with class and section
    $student = Student::with(['class', 'section'])->findOrFail($studentId);

    // Get all teachers who teach this student's class and section
    $teachers = User::whereHas('teacherSubjects', function($query) use ($student) {
            $query->where('class_id', $student->class_id)
                  ->where('section_id', $student->section_id);
        })
        ->with(['teacherSubjects' => function($query) use ($student) {
            $query->where('class_id', $student->class_id)
                  ->where('section_id', $student->section_id)
                  ->with(['subject', 'classroom', 'section']);
        }])
        ->get()
        ->map(function($teacher) {
            return [
                'id' => $teacher->id,
                'name' => $teacher->name,
                'email' => $teacher->email,
                'phone' => $teacher->phone,
                'photo' => $teacher->photo ? url($teacher->photo) : null,
                'is_online' => $teacher->is_online,
                'last_seen' => $teacher->lastSeen(),
                'subjects' => $teacher->teacherSubjects->map(function($ts) {
                    return [
                        'subject_id' => $ts->subject_id,
                        'subject_name' => $ts->subject->name,
                        'subject_code' => $ts->subject->code,
                        'class_name' => $ts->classroom->name,
                        'section_name' => $ts->section->name
                    ];
                }),
                'can_chat' => true // You can add logic to determine if parent can chat with this teacher
            ];
        });

    return response()->json([
        'success' => true,
        'student' => [
            'id' => $student->id,
            'name' => $student->name,
            'class' => $student->class->name,
            'section' => $student->section->name
        ],
        'teachers' => $teachers
    ]);
}


// app/Http/Controllers/ParentController.php
public function getTeachers()
{
    $parent = Auth::user();
    $children = $parent->parentInfo->students()->with(['class.teachers.user'])->get();

    $teachers = collect();

    foreach ($children as $child) {
        if ($child->class && $child->class->teachers) {
            foreach ($child->class->teachers as $teacher) {
                $teachers->push($teacher->user);
            }
        }
    }

    // Remove duplicates and format
    $uniqueTeachers = $teachers->unique('id')->map(function($teacher) {
        return [
            'id' => $teacher->id,
            'name' => $teacher->name,
            'email' => $teacher->email,
            'phone' => $teacher->phone,
            'photo' => $teacher->photo_url, // assuming you have this accessor
            'is_online' => $teacher->is_online,
            'last_seen' => $teacher->lastSeen(),
            'subjects' => $teacher->teacherSubjects->map(function($subject) {
                return [
                    'id' => $subject->id,
                    'name' => $subject->subject_name,
                ];
            }),
            'can_chat' => true, // You might want to add logic for this
        ];
    });

    return response()->json([
        'success' => true,
        'teachers' => $uniqueTeachers->values(),
    ]);
}

public function getTeacherConversation($teacherId)
{
    $parent = Auth::user();
    $teacher = User::where('user_type', 'teacher')->findOrFail($teacherId);

    // Verify the teacher teaches one of the parent's children
    $children = $parent->parentInfo->students()->with(['class.teachers'])->get();
    $isValidTeacher = false;

    foreach ($children as $child) {
        if ($child->class && $child->class->teachers->contains('user_id', $teacher->id)) {
            $isValidTeacher = true;
            break;
        }
    }

    if (!$isValidTeacher) {
        return response()->json(['success' => false, 'message' => 'Unauthorized'], 403);
    }

    // Get or create conversation
    $conversation = PrivateConversation::betweenUsers($parent->id, $teacher->id)->first();

    if (!$conversation) {
        $conversation = PrivateConversation::create([
            'user1_id' => $parent->id,
            'user2_id' => $teacher->id,
        ]);
    }

    return response()->json([
        'success' => true,
        'conversation' => $conversation,
        'teacher' => [
            'id' => $teacher->id,
            'name' => $teacher->name,
            'photo' => $teacher->photo_url,
            'is_online' => $teacher->is_online,
        ],
    ]);
}
public function getMyChildren()
{
    $user = Auth::user();

    if ($user->user_type !== 'parent' || !$user->parentProfile) {
        return response()->json(['success' => false, 'message' => 'User is not a parent.'], 403);
    }

    $children = $user->parentProfile->students()->with(['class:id,name', 'section:id,name'])->get();

    $formattedChildren = $children->map(function($student) {
        return [
            'id' => $student->id,
            'name' => $student->name,
            'arabic_name' => $student->arabic_name,
            'class' => $student->class->name ?? 'N/A',
            'class_id' => $student->class_id ?? null, // ✅ إضافة class_id
            'date_of_birth' => $student->date_of_birth ?? 'N/A', // --- الإضافة الأهم هنا ---
            'section' => $student->section->name ?? 'N/A',
            'section_id' => $student->section_id ?? null, // ✅ إضافة section_id
            'photo' => $student->photo ? url('storage/' . $student->photo) : null,
            'srn' => $student->srn,
            'gender' => $student->gender,
            'study_year_id' => $student->study_year_id, // --- الإضافة الأهم هنا ---
            'study_year_name' => $student->studyYear->name ?? 'N/A', // --- الإضافة الأهم هنا ---
        ];
    });

    return response()->json([
        'success' => true,
        'children' => $formattedChildren
    ]);
}

    public function getParentsForEmails(Request $request)
    {
        $parents = ParentInfo::whereNotNull('email')->paginate(10); // Adjust the number per page as needed
        return response()->json($parents);
    }
    public function sendEmailToParents(Request $request)
    {
        // Retrieve specific emails from the request, or fetch all parent emails if none specified
        $emails = $request->input('emails', User::whereHas('parentInfo')->pluck('email')->toArray());

        // Define the content of the email
        $subject = $request->input('subject', 'Important Update');
        $messageContent = $request->input('message');

        foreach ($emails as $email) {
            Mail::send('emails.parent_email', ['subject' => $subject, 'messageContent' => $messageContent], function ($message) use ($email, $subject) {
                $message->to($email)
                    ->subject($subject)
                    ->from('system@caledonian.ly');
            });
        }

        return response()->json(['message' => 'Emails sent successfully'], 200);
    }
    public function index(Request $request)
    {
        // dd(1);
        $search = $request->query('search');
        $perPage = $request->query('per_page', 10);

        $query = ParentInfo::with(['students', 'students.class', 'students.subscriptionFees'])->withCount('students'); // Add withCount to include student count

        if ($search) {
            $query->where(function ($query) use ($search) {
                $query->where('first_name', 'LIKE', "%{$search}%")
                    ->orWhere('last_name', 'LIKE', "%{$search}%")
                    //   ->orWhere('phone_number_one', 'LIKE', "%{$search}%")
                    ->orWhere('passport_num', 'LIKE', "%{$search}%")
                    ->orWhere('national_number', 'LIKE', "%{$search}%")
                    ->orWhere('phone_number_two', 'LIKE', "%{$search}%")
                    ->orWhere('phone_number_one', 'LIKE', "%{$search}%")


                    ->orWhereRaw("CONCAT(first_name, ' ', last_name) LIKE ?", ["%{$search}%"]);


                // Exact match for ID
                $query->orWhere('id', $search); // Change this line
            });
        }

        $parents = $query->paginate($perPage);

        ActivityLog::create([
            'user_id' => auth()->user()->id,
            'action' => 'show parents',
            'description' => 'Created a new record in Model',
            'new_data' => json_encode($parents),
            'created_at' => now(), // Manually set the created_at timestamp
        ]);

        return response()->json($parents);
    }



    public function indexAllParents(Request $request)
    {
        $search = $request->query('search');


        $query = ParentInfo::with(['students', 'students.section', 'students.class'])->withCount('students'); // Add withCount to include student count

        if ($search) {
            $query->where(function ($query) use ($search) {
                $query->where('first_name', 'LIKE', "%{$search}%")
                    ->orWhere('last_name', 'LIKE', "%{$search}%")
                    //   ->orWhere('phone_number_one', 'LIKE', "%{$search}%")
                    ->orWhere('passport_num', 'LIKE', "%{$search}%")
                    ->orWhere('national_number', 'LIKE', "%{$search}%");

                // Exact match for ID
                $query->orWhere('id', $search); // Change this line
            });
        }

        $parents = $query->get();

        ActivityLog::create([
            'user_id' => auth()->user()->id,
            'action' => 'show parents',
            'description' => 'Created a new record in Model',
            'new_data' => json_encode($parents),
            'created_at' => now(), // Manually set the created_at timestamp
        ]);

        return response()->json($parents);
    }

    public function show($id)
    {
        $parent = ParentInfo::findOrFail($id);
        return response()->json($parent);
    }

    public function store(Request $request)
    {
        $request->validate([
            'first_name' => 'required|string|max:255',
            'last_name' => 'required|string|max:255',
            'phone_number_one' => 'nullable|string|max:255',
            'phone_number_two' => 'nullable|string|max:255',
            'city' => 'nullable|string|max:255',
            'address' => 'nullable|string|max:255',
            'id_image' => 'nullable|file|mimes:jpeg,png,jpg|max:2048', // Validation for ID image
            'passport_image' => 'nullable|file|mimes:jpeg,png,jpg|max:2048', // Validation for passport image
            'images_info' => 'nullable|json',
            'national_number' => 'nullable|digits:12|unique:parents,national_number',
            'passport_num' => 'nullable|unique:parents,passport_num',
            'email' => 'nullable|email|unique:parents,email|max:255',
            'pin_code' => 'nullable|digits:4|unique:parents,pin_code', // Validation for pin_code
        ]);

        // Handle ID image upload
        $idImagePath = $request->hasFile('id_image') ? $request->file('id_image')->store('id_images', 'public') : null;

        // Handle passport image upload
        $passportImagePath = $request->hasFile('passport_image') ? $request->file('passport_image')->store('passport_images', 'public') : null;

        // Create the parent record
        $parent = ParentInfo::create([
            'user_id' => $request->user_id,
            'first_name' => $request->first_name,
            'last_name' => $request->last_name,
            'phone_number_one' => $request->phone_number_one,
            'phone_number_two' => $request->phone_number_two,
            'city' => $request->city,
            'address' => $request->address,
            'id_image' => $idImagePath,
            'passport_image' => $passportImagePath,
            'images_info' => $request->images_info,
            'national_number' => $request->national_number,
            'passport_num' => $request->passport_num,
            'email' => $request->email,
            'pin_code' => $request->pin_code,
        ]);

        return response()->json(['message' => 'Parent record created successfully', 'data' => $parent], 201);
    }




    public function update(Request $request, $id)
    {


        // Find the parent record or return a 404 error if not found
        $parent = ParentInfo::findOrFail($id);

        // Validate incoming request data
        $validatedData = $request->validate([
            'first_name' => 'nullable|string|max:255',
            'last_name' => 'nullable|string|max:255',
            'phone_number_one' => 'nullable|string|max:255',
            'phone_number_two' => 'nullable|string|max:255',
            'city' => 'nullable|string|max:255',
            'address' => 'nullable|string|max:255',
            'images_info' => 'nullable|json',
            'national_number' => [
                'nullable',
                'digits:12',
                Rule::unique('parents', 'national_number')->ignore($parent->id)
            ],
            'passport_num' => 'nullable|string|max:225',
            'email' => [
                'nullable',
                'email',
                'max:255',
                Rule::unique('parents', 'email')->ignore($parent->id)
            ],
            'pin_code' => 'nullable|digits:4',
            'id_image' => 'nullable|image|mimes:jpeg,png,jpg,gif,svg|max:2048',
            'passport_image' => 'nullable|image|mimes:jpeg,png,jpg,gif,svg|max:2048',
        ]);

        // Remove any empty strings to prevent null overwrites
        $validatedData = array_filter($validatedData, function ($value) {
            return $value !== '' && $value !== null;
        });

        // dd($validatedData);
        // Update all validated fields except for images
        $parent->fill($validatedData);

        // Handle file uploads if they exist
        if ($request->hasFile('id_image')) {
            // Store the photo and get its path
            $path = $request->file('id_image')->store('uploads', 'public');
            $parent->id_image = $path;
        }

        if ($request->hasFile('passport_image')) {
            // Store the photo and get its path
            $path = $request->file('passport_image')->store('uploads', 'public');
            $parent->passport_image = $path;
        }

        // Save the updated parent data
        $parent->save();

        return response()->json([
            'message' => 'Parent updated successfully.',
            'parent' => $parent
        ], 200);
    }


    public function destroy(ParentInfo $parent)
    {
        $parent->delete();
        ActivityLog::create([
            'user_id' => auth()->user()->id,
            'action' => 'delete a parent',
            'description' => 'Created a new record in Model',
            'new_data' => json_encode($parent),
            'created_at' => now(), // Manually set the created_at timestamp
        ]);
        return response()->noContent();
    }


    public function storeParentWithStudents(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'parent.username' => 'required|string|max:255|unique:users,username',
            'parent.password' => 'required|string|min:8',
            'parent.email' => 'required|email|unique:users,email',
            'parent.phone_number_one' => 'required|string|max:255|unique:users,phone',

            'students.*.username' => 'required|string|max:255|unique:users,username',
            'students.*.password' => 'required|string|min:8',
            'students.*.email' => 'nullable|email|unique:users,email',
            'parent.first_name' => 'required|string|max:255',
            'parent.last_name' => 'required|string|max:255',
            'parent.phone_number_two' => 'nullable|string|max:255',
            'parent.city' => 'nullable|string|max:255',
            'parent.address' => 'nullable|string|max:255',
            'parent.national_number' => 'nullable|string|max:12|unique:parents,national_number',
            'parent.passport_num' => 'nullable|string|max:255|unique:parents,passport_num',
            'parent.pin_code' => 'nullable|string|max:4|unique:parents,pin_code',
            'parent.id_image' => 'nullable|file|mimes:jpeg,png,jpg,gif,svg|max:2048',
            'parent.passport_image' => 'nullable|file|mimes:jpeg,png,jpg,gif,svg|max:2048',
            'parent.images_info' => 'nullable|string',
            'parent.note' => 'nullable|string',

            'students' => 'required|array|min:1',
            'students.*.name' => 'required|string|max:500',
            'students.*.arabic_name' => 'nullable|string|max:255',
            'students.*.date_of_birth' => 'required|date',
            'students.*.gender' => 'nullable|string|max:255',
            'students.*.class_id' => 'nullable|exists:classes,id',
            'students.*.section_id' => 'nullable|exists:sections,id',
            'students.*.passport_num' => 'nullable|string|max:255|unique:students,passport_num',
            'students.*.national_number' => 'nullable|string|max:12|unique:students,national_number',
            'students.*.has_books' => 'required|in:yes,no',
            'students.*.daily_allowed_purchase_value' => 'nullable|numeric|between:0,999999.99',
            'students.*.srn' => 'nullable|string|max:255|unique:students,srn',
            'students.*.study_year_id' => 'nullable|exists:study_years,id',
            'students.*.note' => 'nullable|string',
            'students.*.subscriptions' => 'nullable|string',
            'students.*.missing' => 'nullable|string',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        DB::beginTransaction();
        try {
            // Handle file uploads for parent
            $idImagePath = $request->file('parent.id_image') ? $request->file('parent.id_image')->store('id_images') : null;
            $passportImagePath = $request->file('parent.passport_image') ? $request->file('parent.passport_image')->store('passport_images') : null;

            // Create Parent User
            $parentUser = User::create([
                'name' => $request->parent['first_name'] . ' ' . $request->parent['last_name'],
                'username' => $request->parent['username'],
                'email' => $request->parent['email'],
                'password' => $request->parent['password'],
                'phone' => $request->parent['phone_number_one'],
                'address' => $request->parent['address'],
                'user_type' => 'parent',
            ]);

            // Create Parent
            $parent = ParentInfo::create([
                'user_id' => $parentUser->id,
                'first_name' => $request->parent['first_name'],
                'last_name' => $request->parent['last_name'],
                'phone_number_one' => $request->parent['phone_number_one'],
                'phone_number_two' => $request->parent['phone_number_two'],
                'email' => $request->parent['email'],
                'city' => $request->parent['city'],
                'address' => $request->parent['address'],
                'national_number' => $request->parent['national_number'],
                'passport_num' => $request->parent['passport_num'],
                'pin_code' => $request->parent['pin_code'],
                'id_image' => $idImagePath,
                'passport_image' => $passportImagePath,
                'images_info' => $request->parent['images_info'],
                'note' => $request->parent['note'] ?? null,
                'discount' => false,
            ]);

            // Create Students
            foreach ($request->students as $index => $studentData) {
                $studentPhotoPath = $studentData['photo'] ? $studentData['photo']->store('student_photos') : null;

                // Generate unique phone for student by combining parent phone with index
                $studentPhone = $request->parent['phone_number_one'] . '_' . ($index + 1);

                $studentUser = User::create([
                    'name' => $studentData['name'],
                    'username' => $studentData['username'],
                    'email' => $studentData['email'] ?? null,
                    'password' => $studentData['password'],
                    'phone' => $studentPhone,
                    'address' => $parent->address,
                    'user_type' => 'student',
                ]);

                Student::create([
                    'user_id' => $studentUser->id,
                    'name' => $studentData['name'],
                    'arabic_name' => $studentData['arabic_name'],
                    'date_of_birth' => $studentData['date_of_birth'],
                    'gender' => $studentData['gender'],
                    'class_id' => $studentData['class_id'],
                    'section_id' => $studentData['section_id'],
                    'passport_num' => $studentData['passport_num'],
                    'national_number' => $studentData['national_number'],
                    'has_books' => $studentData['has_books'],
                    'daily_allowed_purchase_value' => $studentData['daily_allowed_purchase_value'],
                    'srn' => $studentData['srn'],
                    'photo' => $studentPhotoPath,
                    'parent_id' => $parent->id,
                    'study_year_id' => $studentData['study_year_id'],
                    'note' => $studentData['note'] ?? null,
                    'subscriptions' => $studentData['subscriptions'] ?? null,
                    'missing' => $studentData['missing'] ?? null,
                ]);
            }

            // Update the parent's discount field based on the number of students
            $parent->update([
                'discount' => $parent->students()->count() > 1,
            ]);

            DB::commit();
            return response()->json(['message' => 'Parent and students added successfully'], 200);
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error in storeParentWithStudents:', [
                'message' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'trace' => $e->getTraceAsString(),
            ]);
            return response()->json(['message' => 'Failed to add parent and students: ' . $e->getMessage()], 500);
        }
    }

    public function importParentStudents(Request $request)
    {
        // Validate file upload
        $request->validate([
            'file' => 'required|mimes:xlsx,csv|max:2048'
        ]);

        DB::beginTransaction();
        try {
            Log::info('Starting parent-student import process.');

            // Load the Excel file
            $file = $request->file('file');
            $rows = Excel::toArray([], $file)[0]; // Get first sheet data

            Log::info('Excel file loaded successfully.');

            // Extract headers from the first row
            $headers = array_map('trim', $rows[0]); // Ensure no extra spaces in headers
            Log::info('Extracted Headers:', $headers);

            // Convert data rows into an associative array using headers
            $data = [];
            for ($i = 1; $i < count($rows); $i++) {
                $data[] = array_combine($headers, $rows[$i]); // Map each row to headers
            }

            Log::info('Processed Data Count:', ['count' => count($data)]);

            foreach ($data as $row) {
                Log::info('Processing Row:', $row);

                // Skip empty rows or instruction rows
                if (empty($row['student_name']) || strtolower($row['parent_national_number']) == 'instructions') {
                    Log::warning('Skipping row due to missing required fields.', $row);
                    continue;
                }

                // Convert Excel numeric date to `YYYY-MM-DD`
                $studentDateOfBirth = $row['student_date_of_birth'];
                if (is_numeric($studentDateOfBirth)) {
                    $studentDateOfBirth = \PhpOffice\PhpSpreadsheet\Shared\Date::excelToDateTimeObject($studentDateOfBirth)->format('Y-m-d');
                }

                // 🔹 **Find Parent Using Multiple Identifiers (Without Email)**
                $parent = ParentInfo::where(function ($query) use ($row) {
                    $query->where('national_number', $row['parent_national_number'])
                        ->orWhere('phone_number_one', $row['parent_phone_number_one'])
                        ->orWhere('phone_number_two', $row['parent_phone_number_two'])
                        ->orWhere('passport_num', $row['parent_passport_num']);
                })->first();

                if (!$parent) {
                    // Create the parent
                    $parent = ParentInfo::create([
                        'first_name' => $row['parent_first_name'],
                        'last_name' => $row['parent_last_name'],
                        'phone_number_one' => $row['parent_phone_number_one'],
                        'phone_number_two' => $row['parent_phone_number_two'],
                        'email' => $row['parent_email'] ?? null, // Email is nullable
                        'city' => $row['parent_city'],
                        'address' => $row['parent_address'],
                        'national_number' => $row['parent_national_number'],
                        'passport_num' => $row['parent_passport_num'],
                        'pin_code' => $row['parent_pin_code'],
                        'id_image' => null,
                        'passport_image' => null,
                        'images_info' => $row['parent_images_info'],
                        'note' => $row['parent_note'],
                        'discount' => false,
                    ]);

                    Log::info('Parent created successfully', ['parent_id' => $parent->id]);
                } else {
                    Log::info('Parent already exists', ['parent_id' => $parent->id]);
                }

                // Validate Class ID
                if (!ClassRoom::find($row['student_class_id'])) {
                    Log::error('Invalid Class ID detected.', ['class_id' => $row['student_class_id']]);
                    throw new \Exception("Invalid Class ID: " . $row['student_class_id']);
                }

                // Validate Section ID (Allow `NULL`)
                $sectionId = $row['student_section_id'] ?? null;
                if (!empty($sectionId)) {
                    $sectionExists = Section::where('id', $sectionId)
                        ->where('class_id', $row['student_class_id'])
                        ->exists();

                    if (!$sectionExists) {
                        Log::error('Invalid Section ID detected.', ['section_id' => $sectionId, 'class_id' => $row['student_class_id']]);
                        throw new \Exception("Invalid Section ID: " . $sectionId . " for Class ID: " . $row['student_class_id']);
                    }
                } else {
                    Log::info('Section ID is NULL for class', ['class_id' => $row['student_class_id']]);
                }

                // Validate Study Year ID
                if (!StudyYear::find($row['student_study_year_id'])) {
                    Log::error('Invalid Study Year ID detected.', ['study_year_id' => $row['student_study_year_id']]);
                    throw new \Exception("Invalid Study Year ID: " . $row['student_study_year_id']);
                }

                // Create Student
                $student = Student::create([
                    'name' => $row['student_name'],
                    'arabic_name' => $row['student_arabic_name'],
                    'date_of_birth' => $studentDateOfBirth, // FIXED DATE FORMAT
                    'gender' => $row['student_gender'],
                    'class_id' => $row['student_class_id'],
                    'section_id' => $sectionId, // ALLOWED NULL SECTION ID
                    'passport_num' => $row['student_passport_num'],
                    'national_number' => $row['student_national_number'],
                    'has_books' => $row['student_has_books'],
                    'daily_allowed_purchase_value' => $row['student_daily_allowed_purchase_value'],
                    'srn' => $row['student_srn'],
                    'photo' => null,
                    'study_year_id' => $row['student_study_year_id'],
                    'note' => $row['student_note'],
                    'subscriptions' => $row['student_subscriptions'],
                    'missing' => $row['student_missing'],
                    'parent_id' => $parent->id,
                ]);

                Log::info('Student created successfully', ['student_id' => $student->id, 'name' => $student->name, 'parent_id' => $parent->id]);
            }

            DB::commit();
            Log::info('Parent and students import process completed successfully.');
            return response()->json(['message' => 'Parent and students imported successfully'], 200);
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error during parent-student import', [
                'error' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine()
            ]);
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }




}
