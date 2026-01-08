<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use App\Models\School as SchoolAppSchool;
use App\Models\ChatGroup;
use App\Models\Message;
use App\Models\MessageStatus;
use App\Models\User;

class EduraGradingController extends Controller
{
    /**
     * جلب معلومات Edura system للمعلم
     * يتم استدعاؤها من Flutter app
     */
    public function getSchoolInfo(Request $request)
    {
        $validated = $request->validate([
            'school_user_id' => 'required|integer',
        ]);

        try {
            $eduraEndpoint = $this->getSchoolEndpoint();
            $eduraApiToken = $this->getSchoolApiToken();

            if (!$eduraEndpoint || !$eduraApiToken) {
                return response()->json([
                    'message' => 'Edura endpoint or API token not found. Please configure school settings.',
                ], 400);
            }

            return response()->json([
                'edura_endpoint' => $eduraEndpoint,
                'edura_api_token' => $eduraApiToken,
            ]);

        } catch (\Exception $e) {
            Log::error('[EduraGradingController@getSchoolInfo] Error', [
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return response()->json([
                'message' => 'Error fetching school info: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * جلب سياسة التقييم لمادة معينة من Edura system
     */
    public function getGradingPolicy(Request $request)
    {

        $validated = $request->validate([
            'subject_id' => 'required|integer',
            'class_id' => 'required|integer',
            'grade_level_id' => 'nullable|integer',
            'school_user_id' => 'required|integer',
        ]);

        try {
            // Get school from authenticated user or request header
            $schoolEndpoint = $this->getSchoolEndpoint();
            $schoolApiToken = $this->getSchoolApiToken();

            if (!$schoolEndpoint || !$schoolApiToken) {
                return response()->json([
                    'message' => 'School endpoint or API token not found'
                ], 400);
            }

            $url = rtrim($schoolEndpoint, '/') . '/api/teacher/gradebook/policy';

            Log::info('[EduraGradingController@getGradingPolicy] Forwarding request to Edura', [
                'url' => $url,
                'subject_id' => $validated['subject_id'],
                'class_id' => $validated['class_id'],
                'grade_level_id' => $validated['grade_level_id'],
            ]);

            $response = Http::withToken($schoolApiToken)
                ->acceptJson()
                ->timeout(15)
                ->get($url, [
                    'subject_id' => $validated['subject_id'],
                    'class_id' => $validated['class_id'],
                    'grade_level_id' => $validated['grade_level_id'] ?? null,
                    'school_user_id' => $validated['school_user_id'],
                ]);

            if ($response->successful()) {
                Log::info('[EduraGradingController@getGradingPolicy] Received response from Edura', [
                    'status' => $response->status(),
                    'body_preview' => substr($response->body(), 0, 200),
                ]);
                return response()->json($response->json());
            }

            Log::warning('[EduraGradingController@getGradingPolicy] Failed response from Edura', [
                'status' => $response->status(),
                'body' => $response->body(),
            ]);
            return response()->json([
                'message' => 'Failed to fetch grading policy',
                'status' => $response->status(),
            ], $response->status());

        } catch (\Exception $e) {
            Log::error('[EduraGradingController@getGradingPolicy] Error', [
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return response()->json([
                'message' => 'Error fetching grading policy: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * حفظ الدرجات في Edura system
     */
    public function storeScores(Request $request)
    {
        Log::info('[EduraGradingController@storeScores] Received request', [
            'request_data' => $request->all(),
            'students_data' => $request->input('students', []),
        ]);

        // تصفية الطلاب الذين لديهم scores فارغة قبل التحقق
        $studentsData = $request->input('students', []);

        Log::info('[EduraGradingController@storeScores] Students data before filtering', [
            'students_count' => count($studentsData),
            'students' => $studentsData,
        ]);

        $filteredStudents = collect($studentsData)->filter(function ($student) {
            return isset($student['scores']) &&
                   is_array($student['scores']) &&
                   count($student['scores']) > 0;
        })->values()->all();

        Log::info('[EduraGradingController@storeScores] Students data after filtering', [
            'filtered_count' => count($filteredStudents),
            'filtered_students' => $filteredStudents,
        ]);

        // التحقق من البيانات المفلترة
        $validated = $request->validate([
            'study_year_id' => 'required|integer',
            'term_id' => 'required|integer',
            'class_id' => 'required|integer',
            'section_id' => 'nullable|integer',
            'school_user_id' => 'required|integer',
        ]);

        // التحقق من students إذا كان لديهم scores
        if (!empty($filteredStudents)) {
            $request->merge(['students' => $filteredStudents]);
            $studentsValidated = $request->validate([
                'students' => 'required|array',
                'students.*.id' => 'required|integer',
                'students.*.scores' => 'required|array|min:1',
                'students.*.scores.*.subject_id' => 'required|integer',
                'students.*.scores.*.component_id' => 'required|integer',
                'students.*.scores.*.score' => 'nullable|numeric|min:0',
            ]);
            $validated['students'] = $studentsValidated['students'];
        } else {
            // إذا لم توجد درجات، إرجاع رسالة
            return response()->json([
                'success' => false,
                'message' => 'لا توجد درجات للحفظ'
            ], 422);
        }

        try {
            $schoolEndpoint = $this->getSchoolEndpoint();
            $schoolApiToken = $this->getSchoolApiToken();

            if (!$schoolEndpoint || !$schoolApiToken) {
                return response()->json([
                    'message' => 'School endpoint or API token not found'
                ], 400);
            }

            $url = rtrim($schoolEndpoint, '/') . '/api/teacher/gradebook/scores';

            Log::info('[EduraGradingController@storeScores] Sending scores to Edura', [
                'url' => $url,
                'students_count' => count($validated['students']),
            ]);

            $response = Http::withToken($schoolApiToken)
                ->timeout(30)
                ->post($url, $validated);

            if ($response->successful()) {
                return response()->json([
                    'success' => true,
                    'message' => 'تم حفظ الدرجات بنجاح',
                    'data' => $response->json(),
                ]);
            }

            return response()->json([
                'success' => false,
                'message' => 'Failed to save scores',
                'status' => $response->status(),
                'errors' => $response->json(),
            ], $response->status());

        } catch (\Exception $e) {
            Log::error('[EduraGradingController@storeScores] Error', [
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Error saving scores: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * جلب الدرجات المحفوظة من Edura system
     */
    public function getSavedScores(Request $request)
    {
        // معالجة arrays من query parameters (قد تأتي كـ strings أو arrays)
        $studentIds = $request->input('student_ids');
        $componentIds = $request->input('component_ids');

        // تحويل strings إلى arrays إذا لزم الأمر
        if (is_string($studentIds)) {
            $studentIds = json_decode($studentIds, true) ?? [$studentIds];
        }
        if (is_string($componentIds)) {
            $componentIds = json_decode($componentIds, true) ?? [$componentIds];
        }

        // إذا كانت strings منفصلة (مثل "138" من query param)، تحويلها إلى array
        if (!is_array($studentIds) && $studentIds !== null) {
            $studentIds = [$studentIds];
        }
        if (!is_array($componentIds) && $componentIds !== null) {
            $componentIds = [$componentIds];
        }

        $validated = $request->validate([
            'study_year_id' => 'required|integer',
            'term_id' => 'required|integer',
            'class_id' => 'required|integer',
            'section_id' => 'nullable|integer',
            'school_user_id' => 'required|integer',
        ]);

        // إضافة arrays إلى validated
        $validated['student_ids'] = $studentIds ?? [];
        $validated['component_ids'] = $componentIds ?? [];

        // التحقق من أن arrays ليست فارغة
        if (empty($validated['student_ids']) || empty($validated['component_ids'])) {
            return response()->json([
                'message' => 'student_ids and component_ids must be arrays and not empty'
            ], 422);
        }

        try {
            $schoolEndpoint = $this->getSchoolEndpoint();
            $schoolApiToken = $this->getSchoolApiToken();

            if (!$schoolEndpoint || !$schoolApiToken) {
                return response()->json([
                    'message' => 'School endpoint or API token not found'
                ], 400);
            }

            $url = rtrim($schoolEndpoint, '/') . '/api/teacher/gradebook/saved-scores';

            Log::info('[EduraGradingController@getSavedScores] Forwarding request to Edura', [
                'url' => $url,
                'student_ids_count' => count($validated['student_ids']),
                'component_ids_count' => count($validated['component_ids']),
                'term_id' => $validated['term_id'],
            ]);

            // استخدام POST request لأن GET لا يدعم arrays بشكل جيد
            $response = Http::withToken($schoolApiToken)
                ->timeout(15)
                ->acceptJson()
                ->post($url, [
                    'study_year_id' => $validated['study_year_id'],
                    'term_id' => $validated['term_id'],
                    'class_id' => $validated['class_id'],
                    'section_id' => $validated['section_id'] ?? null,
                    'school_user_id' => $validated['school_user_id'],
                    'student_ids' => $validated['student_ids'],
                    'component_ids' => $validated['component_ids'],
                ]);

            if ($response->successful()) {
                Log::info('[EduraGradingController@getSavedScores] Received response from Edura', [
                    'status' => $response->status(),
                    'scores_count' => count($response->json('scores', [])),
                ]);
                return response()->json($response->json());
            }

            Log::warning('[EduraGradingController@getSavedScores] Failed response from Edura', [
                'status' => $response->status(),
                'body_preview' => substr($response->body(), 0, 200),
            ]);

            return response()->json([
                'message' => 'Failed to fetch saved scores',
                'status' => $response->status(),
            ], $response->status());

        } catch (\Exception $e) {
            Log::error('[EduraGradingController@getSavedScores] Error', [
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return response()->json([
                'message' => 'Error fetching saved scores: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get school endpoint from request header (sent from mobile app)
     * The mobile app should get this from the login response or school config
     */
    private function getSchoolEndpoint(): ?string
    {
        $endpoint = $this->sanitizeHeader(request()->header('X-Edura-Endpoint'));
        if ($endpoint) {
            return $endpoint;
        }

        $endpoint = $this->sanitizeHeader(request()->header('X-School-Endpoint'));
        if ($endpoint) {
            return $endpoint;
        }

        $configEndpoint = $this->sanitizeHeader(config('edura.endpoint'));
        if ($configEndpoint) {
            return $configEndpoint;
        }

        return null;
    }

    /**
     * Get school API token from request header (sent from mobile app)
     * The mobile app should get this from the login response or school config
     */
    private function getSchoolApiToken(): ?string
    {
        $token = $this->sanitizeHeader(request()->header('X-Edura-Api-Token'));
        if ($token) {
            return $token;
        }

        $token = $this->sanitizeHeader(request()->header('X-School-Api-Token'));
        if ($token) {
            return $token;
        }

        $configToken = $this->sanitizeHeader(config('edura.api_token'));
        if ($configToken) {
            return $configToken;
        }

        return null;
    }

    /**
     * Sanitize header/config value to avoid treating strings like 'null' as valid data
     */
    private function sanitizeHeader($value): ?string
    {
        if ($value === null) {
            return null;
        }

        $trimmed = trim((string) $value);

        if ($trimmed === '' || in_array(strtolower($trimmed), ['null', 'undefined'], true)) {
            return null;
        }

        return $trimmed;
    }

    /**
     * Proxy methods للواجبات في مجموعات المحادثة
     */

    /**
     * جلب الواجبات لمجموعة محددة
     */
    public function getAssignments(Request $request, $chatGroupId)
    {
        try {
            $schoolEndpoint = $this->getSchoolEndpoint();
            $schoolApiToken = $this->getSchoolApiToken();

            if (!$schoolEndpoint || !$schoolApiToken) {
                Log::error('[EduraGradingController@getAssignments] School endpoint or API token not found', [
                    'chat_group_id' => $chatGroupId,
                ]);
                return response()->json([
                    'success' => false,
                    'message' => 'School endpoint or API token not found'
                ], 400);
            }

            $url = rtrim($schoolEndpoint, '/') . '/api/chat-groups/' . $chatGroupId . '/assignments';

            $queryParams = $request->only(['subject_id', 'assignment_type']);

            Log::info('[EduraGradingController@getAssignments] Fetching assignments', [
                'url' => $url,
                'query_params' => $queryParams,
                'chat_group_id' => $chatGroupId,
            ]);

            // ✅ زيادة timeout وإضافة retry للتعامل مع مشاكل الاتصال
            $response = Http::withToken($schoolApiToken)
                ->acceptJson()
                ->timeout(20) // ✅ زيادة timeout إلى 20 ثانية
                ->connectTimeout(10) // ✅ إضافة connection timeout
                ->retry(2, 1000) // ✅ إعادة المحاولة مرتين مع انتظار ثانية واحدة
                ->get($url, $queryParams);

            if ($response->successful()) {
                Log::info('[EduraGradingController@getAssignments] Successfully fetched assignments', [
                    'chat_group_id' => $chatGroupId,
                    'response_data' => $response->json(),
                ]);
                return response()->json($response->json());
            }

            Log::warning('[EduraGradingController@getAssignments] Failed to fetch assignments', [
                'status' => $response->status(),
                'body' => $response->body(),
                'chat_group_id' => $chatGroupId,
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch assignments',
                'status' => $response->status(),
                'errors' => $response->json(),
            ], $response->status());

        } catch (\Illuminate\Http\Client\ConnectionException $e) {
            // ✅ Connection timeout - edura-system غير متاح أو بطيء جداً
            Log::error('[EduraGradingController@getAssignments] Connection timeout', [
                'error' => $e->getMessage(),
                'url' => $url ?? 'N/A',
                'chat_group_id' => $chatGroupId,
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Error fetching assignments: ' . $e->getMessage()
            ], 500);
        } catch (\Exception $e) {
            Log::error('[EduraGradingController@getAssignments] Error', [
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'chat_group_id' => $chatGroupId,
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Error fetching assignments: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * إنشاء واجب جديد
     */
    public function storeAssignment(Request $request, $chatGroupId)
    {
        try {
            // ✅ ✅ ✅ التحقق من مصدر الطلب:
            // - إذا كان من المعلم مباشرة من Flutter: token من Sanctum للمعلم
            // - إذا كان من edura-system (proxy): token من edura-system + X-School-User-Id header
            
            $authHeader = $request->header('Authorization');
            $apiToken = null;
            
            if ($authHeader && str_starts_with($authHeader, 'Bearer ')) {
                $apiToken = substr($authHeader, 7);
            }
            
            // ✅ استخراج المستخدم من token (Sanctum)
            $teacherUser = null;
            $isRequestFromTeacher = false;
            
            if ($apiToken) {
                try {
                    $token = \Laravel\Sanctum\PersonalAccessToken::findToken($apiToken);
                    if ($token && $token->tokenable) {
                        $teacherUser = $token->tokenable;
                        // ✅ إذا كان المستخدم معلم وليس مسؤول من edura-system
                        if ($teacherUser && $teacherUser->user_type === 'teacher') {
                            $isRequestFromTeacher = true;
                            Log::info('[EduraGradingController@storeAssignment] Request from teacher directly (Flutter)', [
                                'teacher_id' => $teacherUser->id,
                                'teacher_name' => $teacherUser->name,
                            ]);
                        }
                    }
                } catch (\Exception $e) {
                    Log::warning('[EduraGradingController@storeAssignment] Failed to get user from token: ' . $e->getMessage());
                }
            }
            
            // ✅ ✅ ✅ إذا كان الطلب من المعلم مباشرة، أرسله مباشرة إلى edura-system
            if ($isRequestFromTeacher) {
                $schoolEndpoint = $this->getSchoolEndpoint();
                $schoolApiToken = $this->getSchoolApiToken();
                
                if (!$schoolEndpoint || !$schoolApiToken) {
                    return response()->json([
                        'success' => false,
                        'message' => 'School endpoint or API token not found'
                    ], 400);
                }
                
                // ✅ إرسال الطلب مباشرة إلى edura-system
                $url = rtrim($schoolEndpoint, '/') . '/api/chat-groups/' . $chatGroupId . '/assignments';
                
                $payload = $request->all();
                $payload['teacher_external_id'] = $teacherUser->id;
                $payload['teacher_name'] = $teacherUser->name ?? 'المعلم';
                
                Log::info('[EduraGradingController@storeAssignment] Forwarding teacher request to edura-system', [
                    'url' => $url,
                    'teacher_id' => $teacherUser->id,
                ]);
                
                $response = Http::withToken($schoolApiToken)
                    ->acceptJson()
                    ->withHeaders([
                        'Accept' => 'application/json',
                        'Content-Type' => 'application/json',
                        'X-School-User-Id' => (string)$teacherUser->id,
                    ])
                    ->timeout(30)
                    ->connectTimeout(10)
                    ->post($url, $payload);
                
                if ($response->successful()) {
                    $responseData = $response->json();
                    
                    // ✅ إرسال رسالة نظام مباشرة من school-app
                    try {
                        $this->sendAssignmentSystemMessage(
                            $chatGroupId,
                            $teacherUser->id,
                            $payload['teacher_name'],
                            $request->input('subject_name'),
                            $request->input('title'),
                            $request->input('assignment_type'),
                            $responseData['data']['id'] ?? null
                        );
                    } catch (\Exception $e) {
                        Log::error('[EduraGradingController@storeAssignment] Error sending system message: ' . $e->getMessage());
                    }
                    
                    return response()->json($responseData, $response->status());
                }
                
                return response()->json([
                    'success' => false,
                    'message' => 'Failed to create assignment',
                    'status' => $response->status(),
                    'errors' => $response->json(),
                ], $response->status());
            }
            
            // ✅ ✅ ✅ إذا كان الطلب من edura-system (proxy)، نرسله إلى edura-system أيضاً
            $schoolEndpoint = $this->getSchoolEndpoint();
            
            // ✅ Fallback إلى getSchoolApiToken() إذا لم يكن موجوداً في Authorization header
            if (!$apiToken) {
                $apiToken = $this->getSchoolApiToken();
            }

            if (!$schoolEndpoint || !$apiToken) {
                return response()->json([
                    'success' => false,
                    'message' => 'School endpoint or API token not found'
                ], 400);
            }

            $url = rtrim($schoolEndpoint, '/') . '/api/chat-groups/' . $chatGroupId . '/assignments';

            // ✅ ✅ ✅ استخراج المستخدم من api_token (Sanctum)
            // ✅ api_token المرسل من edura-system هو token للمدرسة، وليس token لمستخدم محدد
            // ✅ لذلك نستخدم X-School-User-Id من header إذا كان موجوداً
            $schoolUserId = $request->header('X-School-User-Id');
            
            // ✅ محاولة استخراج المستخدم من token إذا كان X-School-User-Id غير موجود
            if (!$schoolUserId && $apiToken) {
                try {
                    $token = \Laravel\Sanctum\PersonalAccessToken::findToken($apiToken);
                    if ($token && $token->tokenable) {
                        $schoolUserId = $token->tokenable->id;
                        Log::info('[EduraGradingController@storeAssignment] Extracted user from token', [
                            'user_id' => $schoolUserId,
                        ]);
                    }
                } catch (\Exception $e) {
                    Log::warning('[EduraGradingController@storeAssignment] Failed to get user from token: ' . $e->getMessage());
                }
            }

            // إعداد payload
            $payload = $request->all();
            
            // ✅ إضافة school_user_id و teacher_external_id إذا كان موجوداً
            if ($schoolUserId) {
                $payload['school_user_id'] = $schoolUserId;
                // ✅ إذا لم يكن teacher_external_id موجوداً، نستخدم school_user_id
                if (!isset($payload['teacher_external_id']) || !$payload['teacher_external_id']) {
                    $payload['teacher_external_id'] = $schoolUserId;
                }
            }

            Log::info('[EduraGradingController@storeAssignment] Sending assignment request', [
                'url' => $url,
                'school_endpoint' => $schoolEndpoint,
                'school_user_id' => $schoolUserId,
                'teacher_external_id' => $payload['teacher_external_id'] ?? null,
                'has_school_user_id' => !empty($schoolUserId),
                'has_api_token' => !empty($apiToken),
            ]);

            // ✅ زيادة timeout وتحديد headers صريحة
            $response = Http::withToken($apiToken)
                ->acceptJson()
                ->withHeaders([
                    'Accept' => 'application/json',
                    'Content-Type' => 'application/json',
                    'X-School-User-Id' => $schoolUserId ?? '',
                ])
                ->timeout(30) // ✅ زيادة timeout إلى 30 ثانية
                ->connectTimeout(10) // ✅ إضافة connection timeout
                ->post($url, $payload);

            if ($response->successful()) {
                $responseData = $response->json();

                // ✅ إرسال رسالة نظام مباشرة من school-app (بدون timeout)
                try {
                    $this->sendAssignmentSystemMessage(
                        $chatGroupId,
                        $request->input('teacher_external_id'),
                        $request->input('teacher_name'),
                        $request->input('subject_name'),
                        $request->input('title'),
                        $request->input('assignment_type'),
                        $responseData['data']['id'] ?? null
                    );
                } catch (\Exception $e) {
                    Log::error('[EduraGradingController@storeAssignment] Error sending system message: ' . $e->getMessage(), [
                        'trace' => $e->getTraceAsString(),
                    ]);
                    // لا نتوقف عن العملية إذا فشل إرسال رسالة النظام
                }

                return response()->json($responseData, $response->status());
            }

            return response()->json([
                'success' => false,
                'message' => 'Failed to create assignment',
                'status' => $response->status(),
                'errors' => $response->json(),
            ], $response->status());

        } catch (\Exception $e) {
            Log::error('[EduraGradingController@storeAssignment] Error', [
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Error creating assignment: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * عرض واجب محدد
     */
    public function showAssignment(Request $request, $chatGroupId, $id)
    {
        try {
            $schoolEndpoint = $this->getSchoolEndpoint();
            $schoolApiToken = $this->getSchoolApiToken();

            if (!$schoolEndpoint || !$schoolApiToken) {
                return response()->json([
                    'success' => false,
                    'message' => 'School endpoint or API token not found'
                ], 400);
            }

            $url = rtrim($schoolEndpoint, '/') . '/api/chat-groups/' . $chatGroupId . '/assignments/' . $id;

            $response = Http::withToken($schoolApiToken)
                ->acceptJson()
                ->timeout(15)
                ->get($url);

            if ($response->successful()) {
                return response()->json($response->json());
            }

            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch assignment',
                'status' => $response->status(),
            ], $response->status());

        } catch (\Exception $e) {
            Log::error('[EduraGradingController@showAssignment] Error', [
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Error fetching assignment: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * تحديث واجب
     */
    public function updateAssignment(Request $request, $chatGroupId, $id)
    {
        try {
            $schoolEndpoint = $this->getSchoolEndpoint();
            $schoolApiToken = $this->getSchoolApiToken();

            if (!$schoolEndpoint || !$schoolApiToken) {
                return response()->json([
                    'success' => false,
                    'message' => 'School endpoint or API token not found'
                ], 400);
            }

            $url = rtrim($schoolEndpoint, '/') . '/api/chat-groups/' . $chatGroupId . '/assignments/' . $id;

            // إضافة school_user_id من header
            $payload = $request->all();
            $payload['school_user_id'] = $request->header('X-School-User-Id');

            $response = Http::withToken($schoolApiToken)
                ->acceptJson()
                ->timeout(15)
                ->put($url, $payload);

            if ($response->successful()) {
                return response()->json($response->json());
            }

            return response()->json([
                'success' => false,
                'message' => 'Failed to update assignment',
                'status' => $response->status(),
                'errors' => $response->json(),
            ], $response->status());

        } catch (\Exception $e) {
            Log::error('[EduraGradingController@updateAssignment] Error', [
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Error updating assignment: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * حذف واجب
     */
    public function deleteAssignment(Request $request, $chatGroupId, $id)
    {
        try {
            $schoolEndpoint = $this->getSchoolEndpoint();
            $schoolApiToken = $this->getSchoolApiToken();

            if (!$schoolEndpoint || !$schoolApiToken) {
                return response()->json([
                    'success' => false,
                    'message' => 'School endpoint or API token not found'
                ], 400);
            }

            $url = rtrim($schoolEndpoint, '/') . '/api/chat-groups/' . $chatGroupId . '/assignments/' . $id;

            // إضافة school_user_id في header
            $response = Http::withToken($schoolApiToken)
                ->acceptJson()
                ->withHeaders([
                    'X-School-User-Id' => $request->header('X-School-User-Id') ?? '',
                ])
                ->timeout(15)
                ->delete($url);

            if ($response->successful()) {
                return response()->json($response->json());
            }

            return response()->json([
                'success' => false,
                'message' => 'Failed to delete assignment',
                'status' => $response->status(),
            ], $response->status());

        } catch (\Exception $e) {
            Log::error('[EduraGradingController@deleteAssignment] Error', [
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Error deleting assignment: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * إرسال رسالة نظام عند إضافة واجب
     */
    private function sendAssignmentSystemMessage(
        $chatGroupId,
        $teacherExternalId,
        $teacherName,
        $subjectName,
        $assignmentTitle,
        $assignmentType,
        $assignmentId
    ) {
        try {
            $group = ChatGroup::find($chatGroupId);
            if (!$group) {
                Log::error('[EduraGradingController@sendAssignmentSystemMessage] Group not found', [
                    'chat_group_id' => $chatGroupId,
                ]);
                return;
            }

            // ✅ جلب المعلم أو المسؤول (إذا كان موجوداً)
            $sender = null;
            if ($teacherExternalId) {
                // ✅ محاولة جلب المعلم أولاً
                $sender = User::where('id', $teacherExternalId)
                    ->where('user_type', 'teacher')
                    ->first();
            }
            
            // ✅ إذا لم يكن معلم، يمكن أن يكون مسؤول من edura-system
            // ✅ في هذه الحالة، نستخدم creator_id أو أول admin متاح
            if (!$sender) {
                $sender = User::where('user_type', 'admin')->first();
            }

            // ✅ إنشاء رسالة نظام جميلة
            $assignmentTypeText = $assignmentType == 'assignment' ? 'واجب' : 'ملاحظة';
            $creatorName = $teacherName ?: 'إدارة المدرسة';
            $messageContent = "📚 {$creatorName} قام بإضافة {$assignmentTypeText} جديد";
            if ($subjectName) {
                $messageContent .= " في مادة {$subjectName}";
            }
            $messageContent .= ": {$assignmentTitle}";

            // ✅ إنشاء رسالة النظام
            // ✅ للمسؤول: استخدام creator_id أو أول admin متاح
            $senderId = $sender ? $sender->id : ($group->creator_id ?? 1);
            
            $messageData = [
                'sender_id' => $senderId, // استخدام المعلم أو المسؤول أو منشئ المجموعة
                'chat_group_id' => $chatGroupId, // ✅ استخدام chat_group_id حسب Model
                'content' => $messageContent,
                'message_type' => 'system',
                'is_system_message' => true,
                'system_message_type' => 'assignment_added',
                'assignment_id' => $assignmentId,
                'assignment_type' => $assignmentType,
                'teacher_external_id' => $teacherExternalId, // يمكن أن يكون null للمسؤول
            ];

            $message = Message::create($messageData);

            // إنشاء statuses لجميع الأعضاء
            $members = $group->members()
                ->where('is_blocked', false)
                ->pluck('user_id');

            $statuses = [];
            foreach ($members as $memberId) {
                $statuses[] = [
                    'message_id' => $message->id,
                    'user_id' => $memberId,
                    'is_read' => false,
                    'created_at' => now(),
                    'updated_at' => now(),
                ];
            }

            if (!empty($statuses)) {
                MessageStatus::insert($statuses);
            }

            Log::info('[EduraGradingController@sendAssignmentSystemMessage] System message created', [
                'message_id' => $message->id,
                'chat_group_id' => $chatGroupId,
                'assignment_id' => $assignmentId,
            ]);

            // ✅ إرسال إشعارات FCM وحفظها في جدول notifications (بنفس طريقة ChatController@sendMessage)
            $recipients = $group->members()
                ->where('is_blocked', false)
                ->get();

            $assignmentTypeText = $assignmentType == 'assignment' ? 'واجب' : 'ملاحظة';
            $notificationTitle = "تم إضافة {$assignmentTypeText} جديد";
            $notificationBody = ($teacherName ?: 'المعلم') . " أضاف {$assignmentTypeText}";
            if ($subjectName) {
                $notificationBody .= " في مادة {$subjectName}";
            }
            $notificationBody .= ": {$assignmentTitle}";

            Log::info('[EduraGradingController@sendAssignmentSystemMessage] 📢 SENDING NOTIFICATIONS', [
                'recipients_count' => $recipients->count(),
                'title' => $notificationTitle,
                'body' => $notificationBody,
            ]);

            foreach ($recipients as $recipient) {
                // ✅ حفظ الإشعار في جدول notifications
                $recipient->caledonianNotifications()->create([
                    'title' => $notificationTitle,
                    'body' => $notificationBody,
                    'data' => [
                        'type' => 'assignment_added',
                        'group_id' => $group->id,
                        'assignment_id' => $assignmentId,
                        'assignment_type' => $assignmentType,
                        'teacher_external_id' => $teacherExternalId,
                        'teacher_name' => $teacherName,
                        'subject_name' => $subjectName,
                        'assignment_title' => $assignmentTitle,
                    ],
                ]);

                // ✅ إرسال FCM notification إذا كان لدى المستخدم token
                Log::info('[EduraGradingController@sendAssignmentSystemMessage] 🔍 Checking notification for recipient', [
                    'recipient_id' => $recipient->id,
                    'recipient_name' => $recipient->name,
                    'has_fcm_token' => !empty($recipient->fcm_token),
                    'fcm_token_preview' => $recipient->fcm_token ? substr($recipient->fcm_token, 0, 50) . '...' : 'NULL',
                ]);

                if ($recipient->fcm_token) {
                    try {
                        Log::info('[EduraGradingController@sendAssignmentSystemMessage] 📤 Attempting to send FCM notification', [
                            'recipient_id' => $recipient->id,
                            'recipient_name' => $recipient->name,
                            'assignment_id' => $assignmentId,
                            'group_id' => $chatGroupId,
                        ]);

                        // ✅ استخدام Firebase Messaging مباشرة (بنفس طريقة ChatController@sendMessage)
                        $credentialsPath = config('firebase.projects.app.credentials');

                        if (!$credentialsPath || !file_exists($credentialsPath)) {
                            Log::error('[EduraGradingController@sendAssignmentSystemMessage] ❌ Firebase credentials not found', [
                                'recipient_id' => $recipient->id,
                                'credentials_path' => $credentialsPath,
                            ]);
                            continue;
                        }

                        $factory = (new \Kreait\Firebase\Factory)->withServiceAccount($credentialsPath);
                        $messaging = $factory->createMessaging();
                        $fcmToken = $recipient->fcm_token;

                        Log::info('[EduraGradingController@sendAssignmentSystemMessage] 🔥 Using Firebase Messaging directly', [
                            'recipient_id' => $recipient->id,
                            'fcm_token_preview' => substr($fcmToken, 0, 50) . '...',
                            'title' => $notificationTitle,
                            'body' => $notificationBody,
                        ]);

                        $fcmMessage = \Kreait\Firebase\Messaging\CloudMessage::withTarget('token', $fcmToken)
                            ->withNotification(\Kreait\Firebase\Messaging\Notification::create($notificationTitle, $notificationBody))
                            ->withData([
                                'type' => 'assignment_added',
                                'group_id' => (string)$group->id,
                                'assignment_id' => (string)$assignmentId,
                                'assignment_type' => $assignmentType,
                                'teacher_name' => $teacherName ?? '',
                                'subject_name' => $subjectName ?? '',
                                'assignment_title' => $assignmentTitle,
                                'click_action' => 'FLUTTER_NOTIFICATION_CLICK',
                            ])
                            ->withAndroidConfig(\Kreait\Firebase\Messaging\AndroidConfig::fromArray([
                                'priority' => 'high',
                                'notification' => [
                                    'sound' => 'default',
                                    'channel_id' => 'bus_tracking_channel',
                                    'color' => '#1a237e',
                                ],
                            ]))
                            ->withApnsConfig(\Kreait\Firebase\Messaging\ApnsConfig::fromArray([
                                'payload' => [
                                    'aps' => [
                                        'sound' => 'default',
                                        'alert' => [
                                            'title' => $notificationTitle,
                                            'body' => $notificationBody,
                                        ],
                                        'badge' => 1,
                                    ],
                                ],
                            ]));

                        $result = $messaging->send($fcmMessage);

                        Log::info('[EduraGradingController@sendAssignmentSystemMessage] ✅ FCM notification sent successfully', [
                            'recipient_id' => $recipient->id,
                            'message_id' => $result,
                        ]);

                    } catch (\Kreait\Firebase\Exception\Messaging\InvalidArgument $e) {
                        Log::error('[EduraGradingController@sendAssignmentSystemMessage] ❌ FCM InvalidArgument', [
                            'recipient_id' => $recipient->id,
                            'error' => $e->getMessage(),
                            'error_code' => $e->getCode(),
                        ]);
                    } catch (\Kreait\Firebase\Exception\MessagingException $e) {
                        Log::error('[EduraGradingController@sendAssignmentSystemMessage] ❌ FCM MessagingException', [
                            'recipient_id' => $recipient->id,
                            'error' => $e->getMessage(),
                            'error_code' => $e->getCode(),
                            'firebase_errors' => method_exists($e, 'errors') ? $e->errors() : 'N/A',
                        ]);
                        // حذف token غير صالح
                        if (str_contains($e->getMessage(), 'invalid-registration-token') || str_contains($e->getMessage(), 'unregistered')) {
                            Log::warning('[EduraGradingController@sendAssignmentSystemMessage] 🗑️ Deleting invalid FCM token', [
                                'recipient_id' => $recipient->id,
                                'fcm_token_preview' => substr($fcmToken, 0, 50) . '...',
                            ]);
                            $recipient->fcm_token = null;
                            $recipient->save();
                        }
                    } catch (\Exception $e) {
                        Log::error('[EduraGradingController@sendAssignmentSystemMessage] ❌ General FCM Error', [
                            'recipient_id' => $recipient->id,
                            'error' => $e->getMessage(),
                            'trace' => $e->getTraceAsString(),
                        ]);
                    }
                } else {
                    Log::info('[EduraGradingController@sendAssignmentSystemMessage] ⚠️ Skipping FCM notification - no token', [
                        'recipient_id' => $recipient->id,
                        'recipient_name' => $recipient->name,
                    ]);
                }
            }

        } catch (\Exception $e) {
            Log::error('[EduraGradingController@sendAssignmentSystemMessage] Error: ' . $e->getMessage(), [
                'trace' => $e->getTraceAsString(),
                'chat_group_id' => $chatGroupId,
            ]);
            throw $e;
        }
    }

    /**
     * ✅ Route لإرسال رسالة النظام بعد إنشاء الواجب من edura-system
     * يتم استدعاؤها من edura-system بعد إنشاء الواجب
     */
    public function sendAssignmentSystemMessageRoute(Request $request, $chatGroupId, $assignmentId)
    {
        try {
            $validated = $request->validate([
                'teacher_name' => 'nullable|string',
                'subject_name' => 'nullable|string',
                'title' => 'required|string',
                'assignment_type' => 'required|in:assignment,note',
                'teacher_external_id' => 'nullable|integer',
            ]);

            $this->sendAssignmentSystemMessage(
                $chatGroupId,
                $validated['teacher_external_id'] ?? null,
                $validated['teacher_name'] ?? null,
                $validated['subject_name'] ?? null,
                $validated['title'],
                $validated['assignment_type'],
                $assignmentId
            );

            return response()->json([
                'success' => true,
                'message' => 'System message sent successfully'
            ]);
        } catch (\Exception $e) {
            Log::error('[EduraGradingController@sendAssignmentSystemMessageRoute] Error: ' . $e->getMessage(), [
                'trace' => $e->getTraceAsString(),
            ]);
            return response()->json([
                'success' => false,
                'message' => 'Failed to send system message: ' . $e->getMessage()
            ], 500);
        }
    }
}

