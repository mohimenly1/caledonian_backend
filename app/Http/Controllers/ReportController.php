<?php

namespace App\Http\Controllers;

use App\Models\Student;
use App\Models\ParentInfo;
use App\Models\ClassRoom;
use App\Models\FinancialDocument;
use App\Models\Section;
use Illuminate\Http\Request;

class ReportController extends Controller
{

    public function getFinancialReport(Request $request)
    {
        $gender = $request->input('gender');
        $class = $request->input('class');
        $section = $request->input('section');

        // Fetch students based on filters and include parent financial documents with subscription fees
        $studentsQuery = Student::with([
            'class',
            'section',
            'parent.financialDocuments.subscriptionFees' // Load subscription fees with each financial document
        ]);

        $studentsQuery->orderBy('name');

        if ($gender) {
            $studentsQuery->where('gender', $gender);
        }
        if ($class) {
            $studentsQuery->where('class_id', $class);
        }
        if ($section) {
            $studentsQuery->where('section_id', $section);
        }

        $students = $studentsQuery->get();

        $totalStudents = $students->count();
        $studentsWithFinancialDocs = $students->filter(fn($student) => $student->parent && $student->parent->financialDocuments->isNotEmpty())->count();
        $studentsWithoutFinancialDocs = $totalStudents - $studentsWithFinancialDocs;

        return response()->json([
            'students' => $students,
            'total_students' => $totalStudents,
            'students_with_documents' => $studentsWithFinancialDocs,
            'students_without_documents' => $studentsWithoutFinancialDocs,
        ]);
    }



    public function getAllStudentsForPrint(Request $request)
    {
        // Retrieve filters from request
        $gender = $request->input('gender');
        $class = $request->input('class');
        $section = $request->input('section');



        // Apply filters to the query and eager load relationships
        $studentsQuery = Student::with(['class', 'section']); // Eager load the relationships
        $studentsQuery->orderBy('name');
        if ($gender) {
            $studentsQuery->where('gender', $gender);
        }
        if ($class) {
            $studentsQuery->where('class_id', $class); // Use class_id for filtering
        }
        if ($section) {
            $studentsQuery->where('section_id', $section); // Use section_id for filtering
        }

        // Get all the students without pagination
        $students = $studentsQuery->get();

        // Return the data to be used in the front-end for printing
        return response()->json($students);
    }


    // Get students filtered by gender, count male and female
    public function getStudentsByGender(Request $request)
    {
        $gender = $request->input('gender');  // 'male' or 'female'
        $perPage = $request->input('per_page', 10); // Default 10 students per page

        $studentsQuery = Student::query();

        // Apply gender filter if specified
        if ($gender) {
            $studentsQuery->where('gender', $gender);
        }

        // Order students alphabetically by name
        $studentsQuery->orderBy('name'); // Assuming 'name' is the column for student names

        // Paginate the students
        $students = $studentsQuery->with('parent', 'class', 'section')->paginate($perPage);

        // Count for each gender
        $maleCount = Student::where('gender', 'male')->count();
        $femaleCount = Student::where('gender', 'female')->count();

        return response()->json([
            'students' => $students->items(),
            'male_count' => $maleCount,
            'female_count' => $femaleCount,
            'current_page' => $students->currentPage(),
            'total_students' => $students->total(),
            'per_page' => $students->perPage(),
        ]);
    }



    public function getParentsInfoReport()
    {
        // Get parents with financial documents
        $parentsWithDocs = ParentInfo::has('financialDocuments')
            ->withCount('students') // Count number of students for each parent
            ->get();

        // Get parents without financial documents
        $parentsWithoutDocs = ParentInfo::doesntHave('financialDocuments')
            ->withCount('students') // Count number of students for each parent
            ->get();

        $parentCountWithDocs = $parentsWithDocs->count();
        $parentCountWithoutDocs = $parentsWithoutDocs->count();

        return response()->json([
            'parent_count_with_docs' => $parentCountWithDocs,
            'parents_with_docs' => $parentsWithDocs,
            'parent_count_without_docs' => $parentCountWithoutDocs,
            'parents_without_docs' => $parentsWithoutDocs,
        ]);
    }



    // Get parents who have financial documents and their student count
    public function getParentsWithFinancialDocuments()
    {
        $parents = ParentInfo::has('financialDocuments')
            ->withCount('students') // Count number of students for each parent
            ->get();

        $parentCount = $parents->count();

        return response()->json([
            'parent_count' => $parentCount,
            'parents' => $parents,
        ]);
    }

    // Get students in a specific class and section with gender filtering
    public function getClassStudents(Request $request)
    {
        $classId = $request->input('class_id');
        $sectionId = $request->input('section_id');
        $gender = $request->input('gender'); // Optional gender filter

        $studentsQuery = Student::where('class_id', $classId);

        if ($sectionId) {
            $studentsQuery->where('section_id', $sectionId);
        }

        if ($gender) {
            $studentsQuery->where('gender', $gender);
        }

        $students = $studentsQuery->with('class', 'section')->get();

        $totalCount = $students->count();
        $maleCount = $studentsQuery->clone()->where('gender', 'male')->count();
        $femaleCount = $studentsQuery->clone()->where('gender', 'female')->count();

        return response()->json([
            'students' => $students,
            'total_count' => $totalCount,
            'male_count' => $maleCount,
            'female_count' => $femaleCount,
        ]);
    }


    // Report based on all students for a specific class
    public function studentsByClass(Request $request)
    {

        $classId = $request->input('class_id');
        $sectionId = $request->input('section_id');
        $gender = $request->input('gender', '');
        $perPage = $request->input('per_page', 10); // Default to 10 students per page

        $query = Student::with(['class', 'section']); // Eager load the relationships

        if ($classId) {
            $query->where('class_id', $classId);
        }

        if ($sectionId) {
            $query->where('section_id', $sectionId);
        }

        if ($gender) {
            $query->where('gender', $gender);
        }

        // Paginate the students instead of returning all at once
        $students = $query->paginate($perPage);

        // Count for class and section
        $classCount = Student::where('class_id', $classId)->count(); // Total for class
        $sectionCount = Student::where('class_id', $classId)
            ->where('section_id', $sectionId)
            ->count(); // Count for class + section

        return response()->json([
            'students' => $students->items(), // Send only the current page of students
            'current_page' => $students->currentPage(),
            'total_pages' => $students->lastPage(),
            'total_students' => $students->total(),
            'class_count' => $classCount,
            'section_count' => $sectionCount,
            'class_name' => optional(ClassRoom::find($classId))->name, // Get class name
        ]);
    }





    // Report based on all students for a specific class based on gender
    public function studentsByClassAndGender(Request $request)
    {
        $request->validate([
            'class_id' => 'required|exists:classes,id',
            'gender' => 'required|in:male,female',
        ]);

        $students = Student::where('class_id', $request->class_id)
            ->where('gender', $request->gender)
            ->get();

        return response()->json(['students' => $students]);
    }

    // Report based on all students for a specific class based on the section
    public function studentsByClassAndSection(Request $request)
    {
        $request->validate([
            'class_id' => 'required|exists:classes,id',
            'section_id' => 'required|exists:sections,id',
        ]);

        $students = Student::where('class_id', $request->class_id)
            ->where('section_id', $request->section_id)
            ->get();

        return response()->json(['students' => $students]);
    }

    // Report based on all students for a specific class and section, and filtering by gender
    public function studentsByClassSectionAndGender(Request $request)
    {
        $request->validate([
            'class_id' => 'required|exists:classes,id',
            'section_id' => 'required|exists:sections,id',
            'gender' => 'required|in:male,female',
        ]);

        $students = Student::where('class_id', $request->class_id)
            ->where('section_id', $request->section_id)
            ->where('gender', $request->gender)
            ->get();

        return response()->json(['students' => $students]);
    }

    public function getSectionsByClasses($class_id) // Change here
    {

        $sections = Section::where('class_id', $class_id)->get();

        return response()->json(['sections' => $sections]);
    }
    public function students_excel_report()
    {
        $class_id = request()->class_id;
        $section_id = request()->section_id;
        $gender = request()->gender;


        $query = Student::with(['class', 'section']);

        if ($class_id)
            $query->where('class_id', '=', $class_id);
        if ($section_id)
            $query->where('section_id', '=', $section_id);
        if ($gender)
            $query->where('gender', '=', $gender);

        $students = $query->orderBy('class_id')
            ->orderBy('section_id')
            ->orderBy('gender')->get();

        return response()->json(['students' => $students]);
    }
}
