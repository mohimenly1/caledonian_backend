<?php

namespace App\Http\Controllers;

use App\Models\Exam;
use App\Models\ExamType;
use App\Models\Question;
use App\Models\QuestionOption;
use App\Models\Attachment;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;
class ExamController extends Controller
{
    use \App\ApiResponse;

    public function index()
    {
        try {
            if (Auth::guard('web')->check()) {
                $user = Auth::guard('web')->user()->id;
              
            } else {
                $user = Auth::guard('api')->user()->id;
                // dd($user);
            }
            $exams = Exam::with(['type', 'term', 'teacher', 'class', 'subject', 'questions.options', 'attachments'])
                        ->where('teacher_id', $user)
                        ->get();
            
            return $this->success($exams, 'Exams retrieved successfully');
        } catch (\Exception $e) {
            return $this->error('Failed to retrieve exams: ' . $e->getMessage());
        }
    }

    public function store(Request $request)
    {
        try {
            if (Auth::guard('web')->check()) {
                $user = Auth::guard('web')->user()->id;
              
            } else {
                $user = Auth::guard('api')->user()->id;
                // dd($user);
            }
            
          
            $validated = $request->validate([
                'name' => 'required|string|max:255',
                'exam_type_id' => 'required|exists:exam_types,id',
                'term_id' => 'required|exists:terms,id',
                'class_id' => 'nullable|exists:classes,id',
                'subject_id' => 'nullable|exists:subjects,id',
                'start_time' => 'nullable|date',
                'end_time' => 'nullable|date|after:start_time',
                'duration_minutes' => 'nullable|integer',
                'total_score' => 'required|integer',
                'instructions' => 'nullable|string',
                'is_published' => 'boolean',
                'questions' => 'required|array',
                'questions.*.question_text' => 'required|string',
                'questions.*.type' => 'required|in:multiple_choice,essay',
                'questions.*.correct_answer' => 'nullable|string|required_if:questions.*.type,essay',
                'questions.*.points' => 'required|integer|min:1',
                'questions.*.options' => 'required_if:questions.*.type,multiple_choice|array',
                'questions.*.options.*.option_text' => 'required_if:questions.*.type,multiple_choice|string',
                'questions.*.options.*.is_correct' => 'required_if:questions.*.type,multiple_choice|boolean',
                'attachments' => 'nullable|array',
                'attachments.*.file_name' => 'required_with:attachments|string',
                'attachments.*.file_content' => 'required_with:attachments|string', // base64 encoded
            ]);

            $startTime = isset($validated['start_time']) 
            ? date('Y-m-d H:i:s', strtotime($validated['start_time']))
            : null;
            
        $endTime = isset($validated['end_time']) 
            ? date('Y-m-d H:i:s', strtotime($validated['end_time']))
            : null;

            $exam = Exam::create([
                'name' => $validated['name'],
                
                'exam_type_id' => $validated['exam_type_id'],
                'term_id' => $validated['term_id'],
                'teacher_id' => $user,
                'class_id' => $validated['class_id'] ?? null,
                'subject_id' => $validated['subject_id'] ?? null,
                'start_time' => $startTime,
                'end_time' => $endTime,
                'duration_minutes' => $validated['duration_minutes'] ?? null,
                'total_score' => $validated['total_score'],
                'instructions' => $validated['instructions'] ?? null,
                'is_published' => $validated['is_published'] ?? false,
            ]);

            foreach ($validated['questions'] as $questionData) {
                $question = $exam->questions()->create([
                    'question_text' => $questionData['question_text'],
                    'type' => $questionData['type'],
                    'points' => $questionData['points'],
                    'correct_answer' => $questionData['correct_answer'] ?? null, // Add this line
                ]);

                if ($questionData['type'] === 'multiple_choice' && isset($questionData['options'])) {
                    foreach ($questionData['options'] as $optionData) {
                        $question->options()->create([
                            'option_text' => $optionData['option_text'],
                            'is_correct' => $optionData['is_correct'],
                        ]);
                    }
                }
            }

            if (isset($validated['attachments'])) {
                foreach ($validated['attachments'] as $attachmentData) {
                    $fileContent = base64_decode($attachmentData['file_content']);
                    $path = 'exam_attachments/' . uniqid() . '_' . $attachmentData['file_name'];
                    Storage::put($path, $fileContent);
                    
                    $exam->attachments()->create([
                        'file_path' => $path,
                        'file_name' => $attachmentData['file_name'],
                        'mime_type' => Storage::mimeType($path),
                        'size' => Storage::size($path),
                    ]);
                }
            }

            return $this->success($exam->load(['type', 'term', 'teacher', 'class', 'subject', 'questions.options', 'attachments']), 'Exam created successfully');
        } catch (\Exception $e) {
            return $this->error('Failed to create exam: ' . $e->getMessage(), 422);
        }
    }

    public function show($id)
    {
        try {
            $exam = Exam::with(['type', 'term', 'teacher', 'class', 'subject', 'questions.options', 'attachments'])
                        ->where('teacher_id', Auth::id())
                        ->findOrFail($id);
            
            return $this->success($exam, 'Exam retrieved successfully');
        } catch (\Exception $e) {
            return $this->error('Exam not found', 404);
        }
    }

    public function update(Request $request, $id)
    {
        $user = Auth::guard('api')->user()->id;
        
        try {
            // Find the exam belonging to the authenticated teacher
            $exam = Exam::where('teacher_id', $user)->findOrFail($id);
    
            // Validate the request data
            $validated = $request->validate([
                'name' => 'sometimes|string|max:255',
                'exam_type_id' => 'sometimes|exists:exam_types,id',
                'term_id' => 'sometimes|exists:terms,id',
                'class_id' => 'nullable|exists:classes,id',
                'subject_id' => 'nullable|exists:subjects,id',
                'start_time' => 'nullable|date',
                'end_time' => 'nullable|date|after:start_time',
                'duration_minutes' => 'nullable|integer',
                'total_score' => 'sometimes|integer',
                'instructions' => 'nullable|string',
                'is_published' => 'boolean',
                'questions' => 'sometimes|array',
                'questions.*.id' => 'sometimes|exists:questions,id',
                'questions.*.question_text' => 'sometimes|string',
                'questions.*.type' => 'sometimes|in:multiple_choice,essay',
                'questions.*.points' => 'sometimes|integer|min:1',
                'questions.*.correct_answer' => 'nullable|string|required_if:questions.*.type,essay',
                'questions.*.options' => 'sometimes|array',
                'questions.*.options.*.id' => 'sometimes|exists:question_options,id',
                'questions.*.options.*.option_text' => 'sometimes|string',
                'questions.*.options.*.is_correct' => 'sometimes|boolean',
                'attachments_to_delete' => 'sometimes|array',
                'attachments_to_delete.*' => 'exists:attachments,id',
                'attachments' => 'sometimes|array',
                'attachments.*.file_name' => 'required_with:attachments|string',
                'attachments.*.file_content' => 'required_with:attachments|string',
            ]);
    
            // Start database transaction
            DB::beginTransaction();
    
            // Update exam basic information
            $exam->update([
                'name' => $validated['name'] ?? $exam->name,
                'exam_type_id' => $validated['exam_type_id'] ?? $exam->exam_type_id,
                'term_id' => $validated['term_id'] ?? $exam->term_id,
                'class_id' => $validated['class_id'] ?? $exam->class_id,
                'subject_id' => $validated['subject_id'] ?? $exam->subject_id,
                'start_time' => $validated['start_time'] ?? $exam->start_time,
                'end_time' => $validated['end_time'] ?? $exam->end_time,
                'duration_minutes' => $validated['duration_minutes'] ?? $exam->duration_minutes,
                'total_score' => $validated['total_score'] ?? $exam->total_score,
                'instructions' => $validated['instructions'] ?? $exam->instructions,
                'is_published' => $validated['is_published'] ?? $exam->is_published,
            ]);
    
            // Handle questions update
            if (isset($validated['questions'])) {
                $currentQuestionIds = $exam->questions->pluck('id')->toArray();
                $updatedQuestionIds = [];
    
                foreach ($validated['questions'] as $questionData) {
                    // Update or create question
                    $question = $exam->questions()->updateOrCreate(
                        ['id' => $questionData['id'] ?? null],
                        [
                            'question_text' => $questionData['question_text'],
                            'type' => $questionData['type'],
                            'points' => $questionData['points'],
                            'correct_answer' => $questionData['correct_answer'] ?? null,
                        ]
                    );
    
                    $updatedQuestionIds[] = $question->id;
    
                    // Handle options for multiple choice questions
                    if ($questionData['type'] === 'multiple_choice' && isset($questionData['options'])) {
                        $currentOptionIds = $question->options->pluck('id')->toArray();
                        $updatedOptionIds = [];
    
                        foreach ($questionData['options'] as $optionData) {
                            $option = $question->options()->updateOrCreate(
                                ['id' => $optionData['id'] ?? null],
                                [
                                    'option_text' => $optionData['option_text'],
                                    'is_correct' => $optionData['is_correct'],
                                ]
                            );
                            $updatedOptionIds[] = $option->id;
                        }
    
                        // Delete options that were removed
                        $optionsToDelete = array_diff($currentOptionIds, $updatedOptionIds);
                        if (!empty($optionsToDelete)) {
                            QuestionOption::whereIn('id', $optionsToDelete)->delete();
                        }
                    } else {
                        // If question type changed to essay, delete any existing options
                        $question->options()->delete();
                    }
                }
    
                // Delete questions that were removed
                $questionsToDelete = array_diff($currentQuestionIds, $updatedQuestionIds);
                if (!empty($questionsToDelete)) {
                    Question::whereIn('id', $questionsToDelete)->delete();
                }
            }
    
            // Handle attachments to delete
            if (isset($validated['attachments_to_delete'])) {
                $attachments = Attachment::whereIn('id', $validated['attachments_to_delete'])->get();
                foreach ($attachments as $attachment) {
                    Storage::delete($attachment->file_path);
                    $attachment->delete();
                }
            }
    
            // Handle new attachments
            if (isset($validated['attachments'])) {
                foreach ($validated['attachments'] as $attachmentData) {
                    $fileContent = base64_decode($attachmentData['file_content']);
                    $path = 'exam_attachments/' . uniqid() . '_' . $attachmentData['file_name'];
                    Storage::put($path, $fileContent);
                    
                    $exam->attachments()->create([
                        'file_path' => $path,
                        'file_name' => $attachmentData['file_name'],
                        'mime_type' => Storage::mimeType($path),
                        'size' => Storage::size($path),
                    ]);
                }
            }
    
            // Commit the transaction
            DB::commit();
    
            return $this->success(
                $exam->fresh(['type', 'term', 'teacher', 'class', 'subject', 'questions.options', 'attachments']), 
                'Exam updated successfully'
            );
    
        } catch (\Exception $e) {
            // Rollback transaction on error
            DB::rollBack();
            return $this->error('Failed to update exam: ' . $e->getMessage(), 422);
        }
    }

    public function destroy($id)
    {
        try {
            $exam = Exam::where('teacher_id', Auth::id())->findOrFail($id);
            
            // Delete attachments first
            foreach ($exam->attachments as $attachment) {
                Storage::delete($attachment->file_path);
            }
            
            $exam->delete();
            
            return $this->successMessage('Exam deleted successfully');
        } catch (\Exception $e) {
            return $this->error('Failed to delete exam: ' . $e->getMessage());
        }
    }

    public function getExamTypes()
    {
        try {
            $types = ExamType::all();
            return $this->success($types, 'Exam types retrieved successfully');
        } catch (\Exception $e) {
            return $this->error('Failed to retrieve exam types');
        }
    }
}