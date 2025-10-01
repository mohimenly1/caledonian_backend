<?php

namespace App\Http\Controllers;

use App\Models\HealthFile;
use App\Models\ParentInfo;
use App\Models\Student;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class HealthFileController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $query = $request->input('search', ''); // Get search query from request
        $studentsWithHealthFiles = Student::with(['healthFile', 'parent'])
            ->has('healthFile')
            ->where('name', 'like', "%{$query}%") // Filter by student name
            ->paginate(10); // 10 per page

        return response()->json($studentsWithHealthFiles);
    }




    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        // Validation rules
        $request->validate([
            'student_id' => 'nullable|exists:students,id',
            'name' => 'required_if:student_id,null|string|max:255',
            'age' => 'required|date',
            'weight' => 'required|numeric',
            'height' => 'required|numeric',
            'blood_type' => 'nullable|string|max:5',
            'medical_history' => 'nullable|string',
            'hearing' => 'nullable|string',
            'sight' => 'nullable|string',
            'diabetes_mellitus' => 'required|boolean',
            'food_allergies' => 'nullable|string',
            'chronic_disease' => 'nullable|string',
            'clinical_examination' => 'nullable|string',
            'result_clinical_examination' => 'nullable|string',
            'vaccinations' => 'nullable|string',
            // Parent details validation - only if student_id is null
            'parent_details.first_name' => 'required_if:student_id,null|string|max:255',
            'parent_details.last_name' => 'required_if:student_id,null|string|max:255',
            'parent_details.national_number' => 'required_if:student_id,null|digits:12|unique:parents,national_number',
            'parent_details.phone_number_one' => 'nullable|string|max:20',
        ]);

        // If student exists
        if ($request->has('student_id') && $request->student_id) {
            $student = Student::find($request->student_id);
            if (!$student) {
                return response()->json(['error' => 'Student not found'], 404);
            }

            // Optional: If you need to update the student or parent details, you can handle that here
        } else {
            // Create a new parent
            $parent = ParentInfo::create([
                'first_name' => $request->input('parent_details.first_name'),
                'last_name' => $request->input('parent_details.last_name'),
                'phone_number_one' => $request->input('parent_details.phone_number_one'),
                'national_number' => $request->input('parent_details.national_number'),
            ]);

            // Create a new student
            $student = Student::create([
                'name' => $request->name,
                'date_of_birth' => $request->age,
                'national_number' => $request->national_number,
                'parent_id' => $parent->id,
                'address' => $request->address,
            ]);
        }

        // Create a health file for the student
        $healthFile = HealthFile::create([
            'student_id' => $student->id,
            'age' => $request->age,
            'weight' => $request->weight,
            'height' => $request->height,
            'blood_type' => $request->blood_type,
            'medical_history' => $request->medical_history,
            'hearing' => $request->hearing,
            'sight' => $request->sight,
            'diabetes_mellitus' => $request->diabetes_mellitus,
            'food_allergies' => $request->food_allergies,
            'chronic_disease' => $request->chronic_disease,
            'clinical_examination' => $request->clinical_examination,
            'result_clinical_examination' => $request->result_clinical_examination,
            'vaccinations' => $request->vaccinations,
        ]);

        return response()->json($healthFile, 201);
    }


    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        $healthFile = HealthFile::where('student_id', $id)->first();

        if (!$healthFile) {
            return response()->json(['error' => 'Health file not found'], 404);
        }

        return response()->json($healthFile);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        $request->validate([
            'age' => 'required|date',
            'weight' => 'required|numeric',
            'height' => 'required|numeric',
            'blood_type' => 'nullable|string|max:5',
            'medical_history' => 'nullable|string',
            'hearing' => 'nullable|string',
            'sight' => 'nullable|string',
            'diabetes_mellitus' => 'required|boolean',
            'food_allergies' => 'nullable|string',
            'chronic_disease' => 'nullable|string',
            'clinical_examination' => 'nullable|string',
            'result_clinical_examination' => 'nullable|string',
            'vaccinations' => 'nullable|string',
        ]);

        $healthFile = HealthFile::where('student_id', $id)->first();


        if (!$healthFile) {
            return response()->json(['error' => 'Health file not found'], 404);
        }

        $healthFile->update($request->all());

        return response()->json($healthFile);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        $healthFile = HealthFile::find($id); // Use find if querying by primary key

        // dd($healthFile); // Check the result to debug

        if (!$healthFile) {
            return response()->json(['error' => 'Health file not found'], 404);
        }

        $healthFile->delete();

        return response()->json(['message' => 'Health file deleted successfully']);
    }
}
