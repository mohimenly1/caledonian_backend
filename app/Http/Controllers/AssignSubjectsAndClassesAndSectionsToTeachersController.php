<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\Subject;
use App\Models\ClassRoom;
use App\Models\Section;
use App\Models\StudyYear;
use App\Models\TeacherSubject;
use App\Models\ClassSectionSubject;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class AssignSubjectsAndClassesAndSectionsToTeachersController extends Controller
{
    /**
     * Get all teachers (users with user_type = teacher)
     */
    public function getTeachers()
    {
        $teachers = User::where('user_type', 'teacher')
            ->select('id', 'name')
            ->orderBy('name')
            ->get();

        return response()->json([
            'success' => true,
            'data' => $teachers
        ]);
    }

    /**
     * Get all classes (excluding archived ones)
     */
    public function getClasses()
    {
        $classes = ClassRoom::where('name', 'not like', '%Archive%')
            ->select('id', 'name')
            ->orderBy('name')
            ->get();

        return response()->json([
            'success' => true,
            'data' => $classes
        ]);
    }

    /**
     * Get sections for a specific class
     */
    public function getClassSections($classId)
    {
        $sections = Section::whereHas('classes', function($query) use ($classId) {
                $query->where('class_id', $classId);
            })
            ->select('id', 'name')
            ->orderBy('name')
            ->get();

        return response()->json([
            'success' => true,
            'data' => $sections
        ]);
    }

    /**
     * Get subjects for a specific class and section
     */
    public function getClassSectionSubjects($classId, $sectionId)
    {
        $subjects = Subject::whereHas('classes', function($query) use ($classId, $sectionId) {
                $query->where('class_id', $classId)
                      ->where('section_id', $sectionId);
            })
            ->select('id', 'name', 'code')
            ->orderBy('name')
            ->get();

        return response()->json([
            'success' => true,
            'data' => $subjects
        ]);
    }

    /**
     * Get all study years
     */
    public function getStudyYears()
    {
        $studyYears = StudyYear::select('id', 'year_study')
            ->orderBy('year_study', 'desc')
            ->get();

        return response()->json([
            'success' => true,
            'data' => $studyYears
        ]);
    }

    /**
     * Get all teacher-subject assignments
     */
    public function getTeacherSubjects()
    {
        $teacherSubjects = TeacherSubject::with([
                'teacher:id,name',
                'subject:id,name',
                'classroom:id,name',
                'section:id,name',
                'studyYear:id,year_study'
            ])
            ->latest()
            ->get();

        return response()->json([
            'success' => true,
            'data' => $teacherSubjects
        ]);
    }

    /**
     * Assign subjects to a teacher
     */
    public function assignSubjects(Request $request)
    {
        $request->validate([
            'teacher_id' => 'required|exists:users,id',
            'class_id' => 'required|exists:classes,id',
            'section_id' => 'required|exists:sections,id',
            'study_year_id' => 'required|exists:study_years,id',
            'subject_ids' => 'required|array',
            'subject_ids.*' => 'exists:subjects,id'
        ]);

        try {
            DB::beginTransaction();

            $assignments = [];
            foreach ($request->subject_ids as $subjectId) {
                // Check if this assignment already exists
                $exists = TeacherSubject::where([
                    'teacher_id' => $request->teacher_id,
                    'subject_id' => $subjectId,
                    'class_id' => $request->class_id,
                    'section_id' => $request->section_id,
                    'study_year_id' => $request->study_year_id,
                ])->exists();

                if (!$exists) {
                    $assignment = TeacherSubject::create([
                        'teacher_id' => $request->teacher_id,
                        'subject_id' => $subjectId,
                        'class_id' => $request->class_id,
                        'section_id' => $request->section_id,
                        'study_year_id' => $request->study_year_id,
                    ]);

                    $assignments[] = $assignment;
                }
            }

            DB::commit();

            if (empty($assignments)) {
                return response()->json([
                    'success' => false,
                    'message' => 'All selected subjects are already assigned to this teacher for the selected class, section, and study year.'
                ], 422);
            }

            return response()->json([
                'success' => true,
                'message' => count($assignments) . ' subject(s) assigned successfully.',
                'data' => $assignments
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Failed to assign subjects: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Remove a teacher-subject assignment
     */
    public function removeAssignment($id)
    {
        $assignment = TeacherSubject::findOrFail($id);

        try {
            $assignment->delete();
            return response()->json([
                'success' => true,
                'message' => 'Assignment removed successfully.'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to remove assignment: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get all data needed for the initial load
     * (Combines multiple endpoints into one for efficiency)
     */
    public function getAllInitialData()
    {
        try {
            $teachers = User::where('user_type', 'teacher')
                ->select('id', 'name')
                ->orderBy('name')
                ->get();

            $classes = ClassRoom::where('name', 'not like', '%Archive%')
                ->select('id', 'name')
                ->orderBy('name')
                ->get();

            $studyYears = StudyYear::select('id', 'year_study')
                ->orderBy('year_study', 'desc')
                ->get();

            $teacherSubjects = TeacherSubject::with([
                    'teacher:id,name',
                    'subject:id,name',
                    'classroom:id,name',
                    'section:id,name',
                    'studyYear:id,year_study'
                ])
                ->latest()
                ->get();

            return response()->json([
                'success' => true,
                'data' => [
                    'teachers' => $teachers,
                    'classes' => $classes,
                    'study_years' => $studyYears,
                    'teacher_subjects' => $teacherSubjects
                ]
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to load initial data: ' . $e->getMessage()
            ], 500);
        }
    }
}