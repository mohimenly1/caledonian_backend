<?php

use App\Http\Controllers\AbsenceController;
use App\Http\Controllers\AbsenceTypeController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\BusController;
use App\Http\Controllers\StudentEnrollmentController;
use App\Http\Controllers\TeacherSubjectController;
use App\Http\Controllers\FinancialReportsController;
use App\Http\Controllers\DepartmentController;
use App\Http\Controllers\ReportCardController;
use App\Http\Controllers\PortalController;
use App\Http\Controllers\NotificationController;
use App\Http\Controllers\FinalGradeCalculationController;
use App\Http\Controllers\InvoiceController;
use App\Http\Controllers\StudentFeeController;
use App\Http\Controllers\CommentController;
use App\Http\Controllers\GeneratedReportCardController;
use App\Http\Controllers\ClassSubjectController;
use App\Http\Controllers\ProfileUserController;
use App\Http\Controllers\ReportCardTemplateController;
use App\Http\Controllers\GradingPolicyController;
use App\Http\Controllers\TeacherController;
use App\Http\Controllers\AssignSubjectsAndClassesAndSectionsToTeachersController;
use App\Http\Controllers\Api\ChatController;
use App\Http\Controllers\UserFilterController;
use App\Http\Controllers\StudentAssessmentScoreController;
use App\Http\Controllers\PostController;
use App\Http\Controllers\ExamTypeController;
use App\Http\Controllers\SchedulePeriodController;
use App\Http\Controllers\ClassScheduleController;
use Illuminate\Support\Facades\Auth;
use App\Http\Controllers\TimetableController;
use App\Http\Controllers\AssessmentController;
use App\Http\Controllers\AssessmentTypeController;
use App\Http\Controllers\HolidayController;
use App\Http\Controllers\GradeLevelController;
use App\Http\Controllers\GradingScaleController;
use App\Http\Controllers\EmployeeController;
use App\Http\Controllers\SubjectCategoryController;
use App\Http\Controllers\ExamController;
use App\Http\Controllers\GradeController;
use App\Http\Controllers\LogCheckInProcessController;
use App\Http\Controllers\ParentInfoController;
use App\Http\Controllers\SalaryController;
use App\Http\Controllers\SalaryDeductionsAbsencesController;
use App\Http\Controllers\SchoolClassController;
use App\Http\Controllers\SectionController;
use App\Http\Controllers\StudentController;
use App\Http\Controllers\SubscriberController;
use App\Http\Controllers\StudentSubjectController;
use App\Http\Controllers\SubscriptionFeeController;
use App\Http\Controllers\TeacherTypeController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\TermController;
use App\Http\Controllers\TreasuryTransactionController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Exports\LogsExport;
use App\Http\Controllers\ActivityLogController;
use App\Http\Controllers\AttendanceController;
use App\Http\Controllers\AttendanceImportController;
use App\Http\Controllers\billsController;
use App\Http\Controllers\AttendanceRecordController;
use App\Http\Controllers\DeductionController;
use App\Http\Controllers\DeductionPerHourController;
use App\Http\Controllers\TeacherAssessmentController;
use App\Http\Controllers\TeacherCourseAssignmentController;
use App\Http\Controllers\CourseOfferingController;
use App\Http\Controllers\DeductionTypeController;
use App\Http\Controllers\Api\StoryController;
use App\Http\Controllers\EmployeeReportController;
use App\Http\Controllers\EmployeeTypeController;
use App\Http\Controllers\EmployeeWalletController;
use App\Http\Controllers\FinancialDocumentController;
use App\Http\Controllers\HealthFileController;
use App\Http\Controllers\kitchenController;
use App\Http\Controllers\MealCategoryController;
use App\Http\Controllers\MealController;
use App\Http\Controllers\ParentWalletController;
use App\Http\Controllers\PermissionEmployeeController;
use App\Http\Controllers\ReportController;
use App\Http\Controllers\SubjectController;
use App\Http\Controllers\SalaryPerHourController;
use App\Http\Controllers\StatisticsController;
use App\Http\Controllers\StudentPurchaseLimitController;
use App\Http\Controllers\StudentSupervisorController;
use App\Http\Controllers\TreasuryController;
use App\Http\Controllers\GradeCalculationController;
use App\Http\Controllers\BusTrackingController;
use App\Http\Controllers\StudyYearController;
use App\Http\Controllers\Api\EventController;
use App\Models\Employee;
use App\Models\StudentAttendanceRecord;
use App\Models\ParentInfo;
use Illuminate\Support\Facades\Broadcast;
use Maatwebsite\Excel\Facades\Excel;
use App\Http\Controllers\StudentRestrictedMealController;
use Illuminate\Support\Facades\Artisan;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\PrivateConversationController;
use App\Http\Controllers\SiblingDiscountController;
use App\Http\Controllers\StudentAttendanceRecordController;
use App\Http\Controllers\AccountController;
use App\Http\Controllers\BillController;
use App\Http\Controllers\FeeTypeController;
use App\Http\Controllers\FeeStructureController;
use App\Http\Controllers\VendorController;
use App\Http\Controllers\PaymentController;
use App\Http\Controllers\ParentFinancialProfileController;
use App\Http\Controllers\StudentFinancialController;
use App\Http\Controllers\AnnouncementController;
use App\Http\Controllers\CanteenController;
use App\Http\Controllers\ParentWalletDashboardController;
use App\Http\Controllers\LostItemController;
use App\Http\Controllers\StudentAcademicController;
use App\Http\Controllers\ParentAccountController;
use App\Http\Controllers\TeacherAccountsController;
use App\Http\Controllers\TeacherCourseOfferingController;
use App\Http\Controllers\UserSuperViserController;
use App\Http\Controllers\ParentArrivalController;
use App\Http\Controllers\AssessmentQuizController;
use App\Http\Controllers\GradesController;
use App\Http\Controllers\TeacherDataController;
use App\Http\Controllers\AssessmentMetadataController;
use App\Http\Controllers\UserNotificationController;
use App\Http\Controllers\ParentScheduleController;
// use App\Http\Controllers\Api\StatsController; // لا تنسى إضافته في الأعلى
use App\Http\Controllers\StatsController;
use App\Http\Controllers\ClientDashboardController;
use App\Http\Controllers\EduraAttendanceController;
use App\Http\Controllers\EduraTuitionController;
use App\Http\Controllers\EduraAcademicDataController;
use App\Http\Controllers\EduraReportCardController;
use App\Http\Controllers\EduraParentFinanceController;
use App\Http\Controllers\EduraStudentController;
use App\Http\Controllers\EduraTeacherController;
use App\Http\Controllers\EduraChatGroupController;
use App\Http\Controllers\EduraGradingController;





Route::get('/classes/form-data', [SchoolClassController::class, 'getFormData']);
// Private conversations
Route::prefix('private-conversations')->middleware('auth:sanctum')->group(function () {
    // Get or create conversation between current user and another user
    Route::post('/with/{userId}', [PrivateConversationController::class, 'getOrCreateConversation']);

    // List user's private conversations
    Route::get('/', [PrivateConversationController::class, 'index']);

    // Get conversation messages
    Route::get('/{conversationId}/messages', [PrivateConversationController::class, 'messages']);

    // Send message in private conversation
    Route::post('/{conversationId}/messages', [PrivateConversationController::class, 'sendMessage']);

    // Mark messages as read
    Route::post('/{conversationId}/mark-as-read', [PrivateConversationController::class, 'markAsRead']);
    Route::get('/unread-count', [PrivateConversationController::class, 'unreadCount']);
});

// For parent-teacher specific routes
Route::prefix('parent')->middleware(['auth:sanctum', 'role:parent'])->group(function () {
    Route::get('/teachers', [ParentInfoController::class, 'getTeachers']);
    Route::get('/teacher/{teacherId}/conversation', [ParentInfoController::class, 'getTeacherConversation']);
});


Route::get('/announcements', [AnnouncementController::class, 'index']);

Route::get('/total-deductions-2025', [SalaryController::class, 'calculateTotalDeductionsFor2025']);

Route::get('/storage-link', function () {
    Artisan::call('storage:link');

    return response()->json([
        'message' => 'Storage link created successfully',
        'output' => Artisan::output() // Optional: Get any output from the command
    ]);
});
Route::post('/employees', [EmployeeController::class, 'store']);
Route::apiResource('sections', SectionController::class);
Route::get('classes/{class}/sections-to', [SchoolClassController::class, 'getSectionsClass']);
Route::get('/employee-types', [EmployeeTypeController::class, 'index']);
Route::get('/employee-types-all', [EmployeeTypeController::class, 'EmployeeAllType']);
// Route::get('/user', function (Request $request) {
//     return $request->user();
// })->middleware('auth:sanctum');


Route::get('/employees/teachers', [SalaryPerHourController::class, 'getTeachers']);
Route::middleware('auth:sanctum')->group(function () {
    Route::get('/stats', [StatsController::class, 'index']);

  // ⭐⭐ التعديل الرئيسي: قم بوضع المسارات داخل مجموعة 'parent' ⭐⭐
  Route::prefix('parent')->group(function () {

    // المسار الأول: لجلب بيانات الجدول كـ JSON
    Route::get('/student/{student}/timetable', [ParentScheduleController::class, 'getStudentTimetable']);

    // المسار الثاني: لتنزيل نفس الجدول كملف PDF
    Route::get('/student/{student}/timetable/download', [ParentScheduleController::class, 'downloadStudentTimetable']);

    // يمكنك وضع أي مسارات مستقبلية خاصة بولي الأمر هنا
});

    // Routes for the Academic Schedule builder
    Route::apiResource('schedule-periods', SchedulePeriodController::class);
    Route::apiResource('class-schedules', ClassScheduleController::class);
    Route::post('schedule-periods/sync', [SchedulePeriodController::class, 'sync']);
    // You might already have something similar, this is for fetching assignments for the palette
    Route::get('/teacher-course-assignments', [TeacherCourseAssignmentController::class, 'index']);

    // Route for printable data
    Route::get('/schedules/printable-data', [ClassScheduleController::class, 'getPrintableData']);

    Route::apiResource('posts', PostController::class);
    Route::post('posts/{post}/like', [PostController::class, 'like']);
    Route::post('posts/{post}/unlike', [PostController::class, 'unlike']);
        // Comments
    Route::get('posts/{post}/comments', [CommentController::class, 'index']);
    Route::post('posts/{post}/comments', [CommentController::class, 'store']);
    Route::put('comments/{comment}', [CommentController::class, 'update']);
    Route::post('final-grade-calculation/run', [FinalGradeCalculationController::class, 'calculateFinalYearGrades']);
    Route::get('final-grade-calculation/classes', [FinalGradeCalculationController::class, 'getClassesForYear']);
    Route::get('final-grade-calculation/final-record', [FinalGradeCalculationController::class, 'getFinalRecordData']);
    Route::get('final-grade-calculation/class-transcripts', [FinalGradeCalculationController::class, 'getClassFinalTranscripts']);



    // For Admin (Vue)
Route::get('/drivers', [BusController::class, 'getDrivers']);
Route::post('/buses/{bus}/assign-students', [BusController::class, 'assignStudents']);
Route::apiResource('/buses', BusController::class);

// For Flutter App
Route::get('/available-buses', [BusController::class, 'getAvailableBuses']);
Route::post('/driver/update-location', [BusTrackingController::class, 'updateLocation']);
Route::get('/parent/bus-location', [BusTrackingController::class, 'getBusLocation']);

    Route::delete('comments/{comment}', [CommentController::class, 'destroy']);
    Route::apiResource('study-years', StudyYearController::class);
    Route::apiResource('sibling-discounts', SiblingDiscountController::class);
    Route::post('/logoutApp', [AuthController::class, 'logoutApp']);
    Route::get('/teacher/classes-sections', [EmployeeController::class, 'getTeacherClassesAndSections']);
    Route::get('employees/with-salary-records', [SalaryPerHourController::class, 'employeesWithSalaryRecords']);
    // Route::get('/classes/{classId}/sections', [EmployeeReportController::class, 'getSectionsByClass']);
    Route::apiResource('users-controller', UserController::class);
    Route::post('users-controller/{users_controller}/restore', [UserController::class, 'restore'])->name('users-controller.restore');
    Route::get('/invoices/data-for-parent/{parent}', [InvoiceController::class, 'getDataForParent']);
    Route::apiResource('/invoices', InvoiceController::class);
    Route::post('/invoices/{invoice}/payments', [InvoiceController::class, 'addPayment']); // ⭐⭐ إضافة دفعة للفاتورة ⭐⭐
    Route::post('/payments', [PaymentController::class, 'store']);
    Route::post('/users-super', [UserController::class, 'store']);
    Route::get('/get-users-super', [UserSuperViserController::class, 'index']);

    Route::apiResource('/vendors', VendorController::class);
Route::apiResource('/bills', BillController::class);
Route::get('/edura/expense-summary', [ClientDashboardController::class, 'getExpenseSummary']);
Route::get('/edura/bills', [ClientDashboardController::class, 'getBills']);
   // --- ⭐⭐ إضافة مسارات الأقساط الدراسية ⭐⭐ ---
   Route::get('/edura/tuition-summary', [EduraTuitionController::class, 'getTuitionSummary']);
   Route::get('/edura/tuition-invoices', [EduraTuitionController::class, 'getTuitionInvoices']);
   Route::get('/edura/income-transactions', [ClientDashboardController::class, 'getIncomeTransactions']);
    // --- ⭐⭐ إضافة مسار المصروفات الشهرية ⭐⭐ ---
    Route::get('/edura/monthly-expenses', [ClientDashboardController::class, 'getMonthlyExpenses']);
    Route::get('/edura/income-report-summary', [ClientDashboardController::class, 'getIncomeReportSummary']);
      // --- ⭐⭐ إضافة مسار تقرير الحضور ⭐⭐ ---
      Route::get('/edura/attendance-report', [EduraAttendanceController::class, 'getDailyReport']);
      Route::get('/edura/attendance-rate', [EduraAttendanceController::class, 'getOverallAttendanceRate']);
      Route::get('/edura/attendance-stats', [EduraAttendanceController::class, 'getAttendanceStats']);
      Route::get('/edura/sections-by-class', [EduraAttendanceController::class, 'getSectionsByClass']); // إضافة جديدة
      Route::get('/edura/teachers', [EduraTeacherController::class, 'getTeachersWithAssignments']);
      Route::get('/edura/teachers-archived', [EduraTeacherController::class, 'getArchivedTeachersWithAssignments']);
      Route::get('/edura/teachers-stats', [EduraTeacherController::class, 'getTeachersStats']);
      Route::get('/edura/chat/activity', [ChatController::class, 'getEduraChatActivity']);


      Route::prefix('edura')->group(function () {
        // إحصائيات المجموعات
        Route::get('/chat-groups-stats', [EduraChatGroupController::class, 'getChatGroupsStats']);

        // قائمة المجموعات مع الفلترة
        Route::get('/chat-groups', [EduraChatGroupController::class, 'getChatGroups']);

        // تفاصيل مجموعة محددة
        Route::get('/chat-groups/{id}', [EduraChatGroupController::class, 'getGroupDetails']);

        // رسائل مجموعة محددة
        Route::get('/chat-groups/{id}/messages', [EduraChatGroupController::class, 'getGroupMessages']);

        // إحصائيات رسائل المجموعة
        Route::get('/chat-groups/{id}/messages-stats', [EduraChatGroupController::class, 'getGroupMessagesStats']);

        // ✅ تحديث رسالة في مجموعة
        Route::put('/chat-groups/{groupId}/messages/{messageId}', [\App\Http\Controllers\Api\ChatController::class, 'updateMessage']);

        // ✅ حذف رسالة من مجموعة
        Route::delete('/chat-groups/{groupId}/messages/{messageId}', [\App\Http\Controllers\Api\ChatController::class, 'deleteMessage']);
      });
         // --- ⭐⭐ إضافة مسارات البيانات الأكاديمية الأساسية ⭐⭐ ---
    Route::prefix('edura/academic-data')->group(function () {
        Route::get('/study-years', [EduraAcademicDataController::class, 'getStudyYears']);
        Route::get('/grade-levels', [EduraAcademicDataController::class, 'getGradeLevels']);
        Route::get('/subjects', [EduraAcademicDataController::class, 'getSubjects']);
                // --- ⭐⭐ إضافة مسار جلب الفصول والشعب ⭐⭐ ---
        Route::get('/classes', [EduraAcademicDataController::class, 'getClassesAndSections']);
        Route::get('/classes-catalog', [EduraAcademicDataController::class, 'getClassesCatalog']);
        // --- ⭐⭐ تم نقل subjects-for-class إلى routes بدون auth:sanctum (أسفل) ⭐⭐ ---
        // Route::get('/subjects-for-class', [EduraAcademicDataController::class, 'getSubjectsForClass']);
        Route::get('/teacher-subjects-for-class', [EduraAcademicDataController::class, 'getTeacherSubjectsForClass']);
         // --- ⭐⭐ إضافة مسار جلب الطلاب للفصل ⭐⭐ ---
         Route::get('/students-for-class', [EduraAcademicDataController::class, 'getStudentsForClass']);
    });

    // --- ⭐⭐ إضافة مسارات سياسات التقييم وحفظ الدرجات ⭐⭐ ---
    Route::prefix('edura/grading')->group(function () {
        Route::get('/policy', [\App\Http\Controllers\EduraGradingController::class, 'getGradingPolicy']);
        Route::post('/scores', [\App\Http\Controllers\EduraGradingController::class, 'storeScores']);
        Route::match(['get', 'post'], '/saved-scores', [\App\Http\Controllers\EduraGradingController::class, 'getSavedScores']);
        Route::get('/school-info', [\App\Http\Controllers\EduraGradingController::class, 'getSchoolInfo']);

        // ⭐⭐ مسارات الواجبات في مجموعات المحادثة ⭐⭐
        Route::prefix('chat-groups/{chatGroupId}/assignments')->group(function () {
            Route::get('/', [\App\Http\Controllers\EduraGradingController::class, 'getAssignments']);
            Route::post('/', [\App\Http\Controllers\EduraGradingController::class, 'storeAssignment']);
            Route::get('/{id}', [\App\Http\Controllers\EduraGradingController::class, 'showAssignment']);
            Route::put('/{id}', [\App\Http\Controllers\EduraGradingController::class, 'updateAssignment']);
            Route::delete('/{id}', [\App\Http\Controllers\EduraGradingController::class, 'deleteAssignment']);
            // ✅ Route لإرسال رسالة النظام بعد إنشاء الواجب من edura-system
            Route::post('/{assignmentId}/send-system-message', [\App\Http\Controllers\EduraGradingController::class, 'sendAssignmentSystemMessageRoute']);
        });
    });
    Route::get('/edura/parent-finance', [EduraParentFinanceController::class, 'index']);

        // --- ⭐⭐ إضافة مسار بيانات الشهادة (Report Card) ⭐⭐ ---
        Route::get('/edura/report-card-data', [EduraReportCardController::class, 'getStudentReportData']);

      Route::get('/edura/students-stats', [EduraStudentController::class, 'getStudentsStats']);
Route::get('/edura/students', [EduraStudentController::class, 'getStudentsWithDetails']);
// طلاب Edura
Route::get('/edura/students/{student}', [EduraStudentController::class, 'getStudentDetails']);
Route::get('/edura/students/{student}/attendance', [EduraStudentController::class, 'getStudentAttendance']);
Route::put('/edura/students/{student}', [EduraStudentController::class, 'updateStudent']);
Route::apiResource('/accounts', AccountController::class); // For Chart of Accounts
Route::apiResource('/fee-types', FeeTypeController::class);
Route::apiResource('/fee-structures', FeeStructureController::class)->except(['show']);
Route::post('/payments/bill', [PaymentController::class, 'storeBillPayment']);

Route::get('/reports/treasury-movement', [FinancialReportsController::class, 'treasuryMovement']);
Route::get('/reports/income-statement', [FinancialReportsController::class, 'incomeStatement']);

Route::get('/financial-profile/parent/{parent}', [ParentFinancialProfileController::class, 'show']);
    Route::get('/assignments/teachers', [TeacherCourseAssignmentController::class, 'getTeachers']);
    Route::get('/assignments/available-courses', [TeacherCourseAssignmentController::class, 'getAvailableCourses']);
    Route::get('/teachers/{teacher}/assignments', [TeacherCourseAssignmentController::class, 'getTeacherAssignments']);
    Route::post('/teachers/{teacher}/assignments', [TeacherCourseAssignmentController::class, 'syncTeacherAssignments']);
    Route::get('/classes/{class}/sections-for-assign', [SchoolClassController::class, 'getSectionsForAssgin']);
    Route::apiResource('parents-account', ParentAccountController::class)->only(['index']);
    Route::apiResource('users-teacher', TeacherAccountsController::class)->only(['index']);
    Route::post('users-account', [UserController::class,'storeParent']);
    Route::post('users-teacher', [UserController::class,'store']);
    Route::get('teacher-course-offerings', [TeacherCourseOfferingController::class,'index']);
    Route::post('/teacher-assignments/{teacher}', [TeacherCourseAssignmentController::class, 'syncTeacherAssignments']);
    Route::post('/fcm-token', [App\Http\Controllers\Api\UserController::class, 'updateFcmToken']);

    // ✅ Route لاختبار Firebase (يمكن حذفه بعد الاختبار)
    Route::get('/firebase/test', [App\Http\Controllers\FirebaseTestController::class, 'testFirebase'])
        ->middleware('auth:sanctum');


    // Route to get available course offerings for assignment
    Route::get('/course-offerings', [CourseOfferingController::class, 'index']);
    Route::apiResource('grading-policies', GradingPolicyController::class);
    Route::prefix('classes/{class}/course-offerings')->group(function () {
        Route::get('/', [CourseOfferingController::class, 'getOfferingsForClass']);
        Route::post('/', [CourseOfferingController::class, 'syncOfferingsForClass']);
    });

    // Routes for fetching and storing student scores for a specific assessment
// Route::prefix('assessments/{assessment}/scores')->group(function () {
//     Route::get('/', [StudentAssessmentScoreController::class, 'index']);
//     Route::post('/', [StudentAssessmentScoreController::class, 'store']);
// });


// Route::get('/students/{student}/assessments', [StudentAcademicController::class, 'getStudentAssessments']);


// Routes for Teacher Assessment Dashboard
Route::get('/teacher/my-classes', [TeacherAssessmentController::class, 'getTeacherClasses']);
Route::get('/teacher/assessment-dashboard-data', [TeacherAssessmentController::class, 'getDashboardData']);
Route::get('/teacher/assessment-dashboard-data-teacher', [TeacherAssessmentController::class, 'getDashboardDataForTeacher']);
Route::get('/teacher/assessments', [TeacherAssessmentController::class, 'getAssessments']);
    // Routes for Assessment Management
Route::apiResource('assessments', AssessmentController::class);
Route::get('/teacher/assessment-form-data', [AssessmentController::class, 'getAssessmentFormData']);
// سيستقبل هذا المسار مصفوفة من الأسئلة لحفظها دفعة واحدة
Route::post('/assessments/{assessment}/questions', [AssessmentQuizController::class, 'store']);
// مسار لجلب "ورقة الرصد" (قائمة الطلاب مع درجاتهم الحالية للتقييم)
Route::get('/assessments/{assessment}/grading-sheet', [AssessmentQuizController::class, 'getGradingSheet']);

// مسار لحفظ الدرجات التي تم رصدها
Route::post('/assessments/{assessment}/scores', [AssessmentQuizController::class, 'storeScores']);
Route::post('/submissions/{submission}/grade', [AssessmentQuizController::class, 'gradeSubmission']);
Route::get('/submissions/{submission}/details', [AssessmentQuizController::class, 'getSubmissionDetails']);
Route::get('/assessments/{assessment}/submissions', [AssessmentQuizController::class, 'getSubmissions']);
Route::get('/students/{student}/assessments', [AssessmentQuizController::class, 'getAssessmentsForStudent']);
Route::get('/assessments/{assessment}/details-for-student', [AssessmentQuizController::class, 'getDetailsForStudent']);
Route::post('/assessments/{assessment}/submit', [AssessmentQuizController::class, 'submitStudentAnswers']);


Route::get('/teachers/{teacher}/classes', [TeacherDataController::class, 'getTeacherClasses']);
Route::get('/teachers/{teacher}/classes/{classRoom}/sections', [TeacherDataController::class, 'getTeacherSectionsForClass']);
Route::get('/teachers/{teacher}/sections/{section}/course-offerings', [TeacherDataController::class, 'getTeacherCoursesForSection']);

// --- مسار جديد لجلب كشف الدرجات النهائي ---
Route::get('/course-offerings/{courseOffering}/grades', [GradesController::class, 'getGradesForCourse']);


Route::get('course-offerings/{courseOffering}/assessments', [AssessmentController::class, 'index']);
Route::post('grade-calculation/run', [GradeCalculationController::class, 'calculateAndStoreFinalGrades']);
Route::post('generated-report-cards/log', [GeneratedReportCardController::class, 'logGeneration']);

Route::get('grade-calculation/statuses', [GradeCalculationController::class, 'getCalculationStatuses']);
Route::apiResource('report-card-templates', ReportCardTemplateController::class);



// Route لتوليد بيانات الشهادات لصف معين
Route::post('report-cards/generate-data', [ReportCardController::class, 'generateData']);
Route::get('report-cards/download', [ReportCardController::class, 'downloadPDF']);
Route::get('grading-scales', [GradingScaleController::class, 'index']);
Route::post('grading-scales', [GradingScaleController::class, 'store']);

// Route::post('course-offerings/{courseOffering}/assessments', [AssessmentController::class, 'store']);

// Route to get available assessment types
Route::apiResource('assessment-types', AssessmentTypeController::class);

    Route::post('/employees/{employee}/classes', [EmployeeController::class, 'addClasses']);
Route::put('/employees/{employee}/classes', [EmployeeController::class, 'syncClasses']);
Route::post('/employees/{employee}/sections', [EmployeeController::class, 'addSections']);
Route::put('/employees/{employee}/sections', [EmployeeController::class, 'syncSections']);
    Route::get('/employees-filtered', [EmployeeReportController::class, 'getFilteredEmployees']);
    Route::get('/employees/report', [EmployeeReportController::class, 'getFilteredEmployeesForReport']);

    Route::get('/classes/{classId}/sections', [StudentController::class, 'getSectionsByClassEmp']);
    Route::get('/financial-report', [ReportController::class, 'getFinancialReport']);
    Route::get('/reports/students', [ReportController::class, 'getStudentsByGender']);
    Route::get('/get-all-students-for-print', [ReportController::class, 'getAllStudentsForPrint']);
    Route::get('/reports/parents-financial', [ReportController::class, 'getParentsWithFinancialDocuments']);
    // Route::get('/reports/class-students', [ReportController::class, 'getClassStudents']);
    Route::get('/reports/parents-info', [ReportController::class, 'getParentsInfoReport']);

    Route::get('teacher-types/{id}/classes', [TeacherTypeController::class, 'getClasses']);

    Route::get('/profile', [ProfileUserController::class, 'me']); // Get current authenticated user's profile
    Route::post('/profile', [ProfileUserController::class, 'update']); // Update current authenticated user's profile
    Route::get('/users/{user}/posts', [ProfileUserController::class, 'posts']);
    Route::get('/users/{user}/profile', [ProfileUserController::class, 'show']);

    Route::get('/assign-subjects/initial-data', [AssignSubjectsAndClassesAndSectionsToTeachersController::class, 'getAllInitialData']);
    Route::get('/get-teachers', [AssignSubjectsAndClassesAndSectionsToTeachersController::class, 'getTeachers']);
    Route::get('/classes/{classId}/sections', [AssignSubjectsAndClassesAndSectionsToTeachersController::class, 'getClassSections']);
    Route::get('/classes/{classId}/sections/{sectionId}/subjects', [AssignSubjectsAndClassesAndSectionsToTeachersController::class, 'getClassSectionSubjects']);
    Route::post('/assign-subjects', [AssignSubjectsAndClassesAndSectionsToTeachersController::class, 'assignSubjects']);
    Route::delete('/assign-subjects/{id}', [AssignSubjectsAndClassesAndSectionsToTeachersController::class, 'removeAssignment']);

    // You can also keep the existing routes if needed for backward compatibility
    Route::get('/teacher-subjects', [AssignSubjectsAndClassesAndSectionsToTeachersController::class, 'getTeacherSubjects']);

    Route::get('/reports/class-students', [ReportController::class, 'studentsByClass']);
    Route::get('/teachers/{teacher}/subjects', [TeacherSubjectController::class, 'index']);
    Route::put('/teachers/{teacher}/subjects', [TeacherSubjectController::class, 'update']);

    Route::get('/classes/{class}/sections', [SchoolClassController::class, 'getSections']);
    Route::post('/employees/{employee}/subjects', [EmployeeController::class, 'addSubjects']);
    Route::put('/employees/{employee}/subjects', [EmployeeController::class, 'syncSubjects']);
    Route::get('/teacher/timetable', [TeacherController::class, 'getTimetable']);
    Route::get('/teachers-for-timetable', [TimetableController::class, 'getTeacher']);

    Route::get('/class-subjects', [ClassSubjectController::class, 'index']);
    Route::post('/class-subjects', [ClassSubjectController::class, 'store']);
    Route::delete('/class-subjects', [ClassSubjectController::class, 'destroy']);
    Route::get('/classes/{class}', [ClassSubjectController::class, 'show']);
    Route::apiResource('grade-levels', GradeLevelController::class);
    Route::apiResource('grade-scales', GradingScaleController::class);
    Route::apiResource('subjects', SubjectController::class);
    Route::get('/reports/students-excel-report', [ReportController::class, 'students_excel_report']);
    Route::get('/reports/class-students/gender', [ReportController::class, 'studentsByClassAndGender']);
    Route::get('/reports/class-students/section', [ReportController::class, 'studentsByClassAndSection']);
    Route::get('/reports/class-students/section/gender', [ReportController::class, 'studentsByClassSectionAndGender']);
    Route::get('/report-sections-based-on-class/{class_id}', [ReportController::class, 'getSectionsByClasses']);
    Route::post('salaries', [SalaryController::class, 'store']);
    Route::get('salaries', [SalaryController::class, 'index']);
    Route::get('absences', [AbsenceController::class, 'index']);
    Route::get('deductions', [DeductionController::class, 'index']);
    Route::post('/absences', [AbsenceController::class, 'store']);
    Route::post('/deductions', [DeductionController::class, 'store']);
    Route::get('/absence-types', [AbsenceTypeController::class, 'index']);
    Route::post('/absence-types', [AbsenceTypeController::class, 'store']);
    Route::put('/absence-types/{id}', [AbsenceTypeController::class, 'update']);
    Route::delete('/absence-types/{id}', [AbsenceTypeController::class, 'destroy']);
    Route::post('/salary-deductions-absences', [SalaryDeductionsAbsencesController::class, 'storeSalaryDeductionsAbsences']);

    Route::post('/employee-types', [EmployeeTypeController::class, 'store']);
    Route::get('/employee-types/{id}', [EmployeeTypeController::class, 'show']);
    Route::put('/employee-types/{id}', [EmployeeTypeController::class, 'update']);
    Route::delete('/employee-types/{id}', [EmployeeTypeController::class, 'destroy']);
    Route::get('/employees/{id}/financial-details', [EmployeeController::class, 'getFinancialData']);
    Route::apiResource('/deduction-types', DeductionTypeController::class);
    Route::put('/employees/{id}/update-salary', [EmployeeController::class, 'updateEmployeeSalary']);
    Route::get('/employees', [EmployeeController::class, 'index']);

    Route::get('/employees/{id}', [EmployeeController::class, 'show']);
    Route::put('/employees/{id}', [EmployeeController::class, 'update']);
    Route::delete('/employees/{id}', [EmployeeController::class, 'destroy']);

    Route::get('/reports/class-students', [ReportController::class, 'studentsByClass']);

    Route::get('/reports/class-students/gender', [ReportController::class, 'studentsByClassAndGender']);
    Route::get('/reports/class-students/section', [ReportController::class, 'studentsByClassAndSection']);
    Route::get('/reports/class-students/section/gender', [ReportController::class, 'studentsByClassSectionAndGender']);
    Route::get('/report-sections-based-on-class/{class_id}', [ReportController::class, 'getSectionsByClasses']);
    Route::post('salaries', [SalaryController::class, 'store']);
    Route::get('salaries', [SalaryController::class, 'index']);
    Route::post('/send-email-to-parents', [ParentInfoController::class, 'sendEmailToParents']);
    Route::get('/get-parents-for-emails', [ParentInfoController::class, 'getParentsForEmails']);
    Route::get('absences', [AbsenceController::class, 'index']);

    Route::delete('/salaries-per-hour/{employeeId}', [SalaryPerHourController::class, 'destroySalaryPerHour']);
    Route::get('/salaries_per_hour/{employeeId}', [SalaryPerHourController::class, 'show']);
    // In routes/api.php
    Route::get('/attendance-processes/absences/{employeeId}/{month}/{year}', [SalaryController::class, 'getEmployeeAbsences']);
    Route::get('/attendance-processes/{employeeId}/{month}/{year}', [SalaryController::class, 'getEmployeeAbsences']);

    Route::delete('attendance-records/{employeeId}', [SalaryPerHourController::class, 'destroyAttendanceRecord']);
    Route::get('issued-salaries-per-hour', [SalaryPerHourController::class, 'fetchIssuedSalaries']);
    Route::post('attendance_records', [AttendanceRecordController::class, 'store']);
    Route::get('attendance_records/{employeeId}', [AttendanceRecordController::class, 'fetchAttendanceRecords']);

    // Group routes
Route::get('/chat-groups', [ChatController::class, 'index']);
Route::post('/chat-groups', [ChatController::class, 'store']);
Route::get('/chat-groups/{group}', [ChatController::class, 'show']);
Route::put('/chat-groups/{group}', [ChatController::class, 'update']);
Route::delete('/chat-groups/{group}', [ChatController::class, 'destroy']);

// Group member management
Route::post('/chat-groups/{group}/members', [ChatController::class, 'addMembers']);
Route::post('/chat-groups/{group}/remove-members', [ChatController::class, 'removeMembers']);
Route::post('/chat-groups/{group}/block-members', [ChatController::class, 'blockMembers']);
Route::post('/chat-groups/{group}/update-role', [ChatController::class, 'updateMemberRole']);

// Group participation
Route::post('/chat-groups/{group}/join', [ChatController::class, 'joinGroup']);
Route::post('/chat-groups/{group}/leave', [ChatController::class, 'leaveGroup']);
Route::post('/announcements/send', [AnnouncementController::class, 'send']);
// Messages


Route::post('/notifications/send-to-user', [UserNotificationController::class, 'sendToUser']);

Route::get('/chat-groups/{group}/messages', [ChatController::class, 'getMessages']);
Route::post('/chat-groups/{group}/messages', [ChatController::class, 'sendMessage']);
Route::post('/chat-groups/{group}/upload-media-only', [ChatController::class, 'uploadMediaOnly']); // رفع ملف فقط (للواجبات)
Route::delete('/messages/{message}', [ChatController::class, 'deleteMessage']);


Route::post('/parent-arrivals/scan', [ParentArrivalController::class, 'processParentQrCode']);

Route::get('/parent-arrivals', [ParentArrivalController::class, 'index']);
// Private messages
Route::get('/private-messages/{recipient}', [ChatController::class, 'getPrivateMessages']);
Route::post('/private-messages/{recipient}', [ChatController::class, 'sendPrivateMessage']);

// Utility
Route::get('/chat-groups/{group}/unread-count', [ChatController::class, 'getUnreadCount']);
Route::post('/chat-groups/{group}/mark-as-read', [ChatController::class, 'markAsRead']);
Route::post('/chat-groups/{group}/toggle-chat-for-parents', [ChatController::class, 'toggleChatForParents']);
Route::get('/users', [ChatController::class, 'getAllUsers']);
Route::get('/users-teacher/{user}', [UserController::class, 'show']);
Route::get('/users/filter', [ChatController::class, 'filterUsers']);
Route::put('/messages/{message}', [ChatController::class, 'updateMessage']); // تحديث الرسالة
Route::delete('/messages/{message}', [ChatController::class, 'deleteMessage']); // حذف الرسالة
Route::get('/messages/{message}/edit', [ChatController::class, 'getMessageForEdit']); // جلب الرسالة للتعديل

    Route::post('/salaries_per_hour', [SalaryPerHourController::class, 'store']);
    Route::post('/salary/calculate/{employeeId}', [SalaryPerHourController::class, 'calculateIssuedSalariesPerHour']);
    Route::post('employees/issue-salaries', [SalaryPerHourController::class, 'calculateIssuedSalariesForMultipleEmployees']);
    Route::get('/salary/delay-check/{employeeId}', [SalaryPerHourController::class, 'checkDelay']);
    Route::delete('/issued-salaries-per-hour/{id}', [SalaryPerHourController::class, 'destroy']);
    Route::apiResource('/deductions-per-hour', DeductionPerHourController::class);
    Route::apiResource('subject-categories', SubjectCategoryController::class);

    Route::apiResource('events', EventController::class);
    Route::get('deductions', [DeductionController::class, 'index']);
    Route::post('/absences', [AbsenceController::class, 'store']);
    Route::post('/deductions', [DeductionController::class, 'store']);
    Route::get('/absence-types', [AbsenceTypeController::class, 'index']);
    Route::post('/absence-types', [AbsenceTypeController::class, 'store']);
    Route::put('/absence-types/{id}', [AbsenceTypeController::class, 'update']);
    Route::delete('/absence-types/{id}', [AbsenceTypeController::class, 'destroy']);
    Route::post('/salary-deductions-absences', [SalaryDeductionsAbsencesController::class, 'storeSalaryDeductionsAbsences']);
    Route::delete('salaries/{id}', [SalaryController::class, 'destroy']);
    Route::get('/employee-types', [EmployeeTypeController::class, 'index']);

    Route::post('/employee-types', [EmployeeTypeController::class, 'store']);
    Route::get('/employee-types/{id}', [EmployeeTypeController::class, 'show']);
    Route::put('/employee-types/{id}', [EmployeeTypeController::class, 'update']);
    Route::delete('/employee-types/{id}', [EmployeeTypeController::class, 'destroy']);
    Route::get('/employees/{id}/financial-details', [EmployeeController::class, 'getFinancialData']);
    Route::apiResource('/deduction-types', DeductionTypeController::class);
    Route::put('/employees/{id}/update-salary', [EmployeeController::class, 'updateEmployeeSalary']);
    Route::get('/employees', [EmployeeController::class, 'index']);


    Route::get('/notifications', [App\Http\Controllers\Api\UserController::class, 'getNotifications']);
    Route::post('/notifications/{id}/read', [App\Http\Controllers\Api\UserController::class, 'markNotificationAsRead']);
    Route::post('/notifications/read-all', [App\Http\Controllers\Api\UserController::class, 'markAllNotificationsAsRead']);


    Route::get('/employees-issued-salaries', [EmployeeController::class, 'indexForIssuedSalaries']);
    Route::get('/employees-absences-deductions', [EmployeeController::class, 'indexForabsenceAndDeduction']);
    Route::get('/employees/{id}', [EmployeeController::class, 'show']);
    Route::delete('/employees/{id}', [EmployeeController::class, 'destroy']);
    Route::post('logout', [AuthController::class, 'logout']);
    // Route::put('/students/{id}', [StudentController::class, 'update']);
    Route::apiResource('students', StudentController::class);
    Route::get('/students/{student}/invoices', [StudentController::class, 'getStudentInvoices']);

    Route::put('/students/{id}/update-gender', [StudentController::class, 'updateStudentGender']);
    Route::apiResource('parents', ParentInfoController::class)->except('update');
    Route::post('/parents/update/{id}', [ParentInfoController::class, 'update']);
    Route::get('/parents-with-children', [ParentInfoController::class, 'indexAllParents']);
    Route::post('import-attendance', [AttendanceImportController::class, 'import']);
    Route::post('import-attendance-json', [AttendanceImportController::class, 'importJson']);
    Route::get('/parent/my-children', [ParentInfoController::class, 'getMyChildren']);
    Route::get('/study-years/{studyYear}/terms', [PortalController::class, 'getTermsForStudyYear']);
    Route::get('/students/{student}/dashboard', [PortalController::class, 'getStudentDashboardData']);
    Route::get('/students/{student}/grades-for-parent', [PortalController::class, 'getStudentGradesForParent']);
    Route::get('/students/{student}/teachers', [PortalController::class, 'getTeachersForChild']);
    Route::get('/students/{student}/subjects/{courseOffering}/details', [PortalController::class, 'getSubjectDetails']);
    Route::get('/students/{student}/academic-dashboard', [PortalController::class, 'getStudentAcademicDashboard']);

    Route::get('/parent/child/{studentId}/teachers', [ParentInfoController::class, 'getChildTeachers']);
    Route::get('/activity-logs', [ActivityLogController::class, 'index']);
    Route::post('/financial-document', [FinancialDocumentController::class, 'storeFinancialDocument']);
    Route::get('/financial-documents', [FinancialDocumentController::class, 'index']);
    Route::post('/generate-pdf', [FinancialDocumentController::class, 'generatePdf']);
    Route::get('students-index', [StudentController::class, 'indexForDoc']);
    Route::apiResource('/subscription-fees', SubscriptionFeeController::class)->except('show');
    Route::get('/subscription-fees/{student_id}', [SubscriptionFeeController::class, 'getFees']);
    Route::put('/financial-documents/{id}', [FinancialDocumentController::class, 'updateFinancialDocument']);
    Route::prefix('timetable')->group(function () {
        // Get timetable (class/section based)
        Route::get('/', [TimetableController::class, 'index']);

        // Create timetable entries
        Route::post('/', [TimetableController::class, 'store']);

        // Update timetable entry
        Route::put('/{timetable}', [TimetableController::class, 'update']);

        // Delete timetable entry
        Route::delete('/{timetable}', [TimetableController::class, 'destroy']);
    });

    Route::get('/student/profile', [StudentController::class, 'getProfile']);

    // Get class subjects
    Route::get('/class-subjects-students', [ClassSubjectController::class, 'getClassSubjects']);

    // Holiday routes
    Route::apiResource('holidays', HolidayController::class);
    // Route to delete a financial document and its related subscription fees
    Route::delete('/financial-documents/{id}', [FinancialDocumentController::class, 'destroy']);
    Route::post('/financial-documents/{id}/confirm-delete', [FinancialDocumentController::class, 'confirmDeletion']);

    Route::get('/student/subject/{subjectId}/details', [StudentSubjectController::class, 'getSubjectDetails']);

    // Grades by term
    Route::get('/student/subject/{subjectId}/grades', [StudentSubjectController::class, 'getSubjectGrades']);

    // Progress tracking
    Route::get('/student/subject/{subjectId}/progress', [StudentSubjectController::class, 'getSubjectProgress']);

    // Evaluations (both manual and grade-based)
    Route::get('/student/subject/{subjectId}/evaluations', [StudentSubjectController::class, 'getSubjectEvaluations']);

    // Exams
    Route::get('/student/subject/{subjectId}/exams', [StudentSubjectController::class, 'getSubjectExams']);
    Route::get('/filter-users', [UserFilterController::class, 'filterUsers']);
    Route::get('/statistics/users', [StatisticsController::class, 'getTotalUsers']);
    Route::get('/statistics/employees', [StatisticsController::class, 'getTotalEmployees']);
    // Route::get('/statistics/treasury/manual-balance', [StatisticsController::class, 'getManualTreasuryBalance']);
    Route::get('/statistics/students', [StatisticsController::class, 'getTotalStudents']);
    Route::get('/statistics/students-per-class', [StatisticsController::class, 'getStudentsPerClass']);
    Route::get('/statistics/treasury-transactions', [StatisticsController::class, 'getTreasuryTransactions']);
    Route::get('/statistics/last-check-in', [StatisticsController::class, 'getLastCheckIn']);

    Route::post('/parents-with-students', [ParentInfoController::class, 'storeParentWithStudents']);
    Route::post('/import-parents-students', [ParentInfoController::class, 'importParentStudents']);


    // Meal Category Routes
    Route::get('categories', [MealCategoryController::class, 'index']);
    Route::post('categories-store', [MealCategoryController::class, 'store']);
    Route::put('categories/{category}', [MealCategoryController::class, 'update']);
    Route::delete('categories/{category}', [MealCategoryController::class, 'destroy']);
    Route::apiResource('terms', TermController::class);

    // Route::get('/study-years', [StudyYearController::class, 'index']);
    //  Add Funds
    Route::post('parent-wallet/add-funds', [ParentWalletController::class, 'addFunds']);
    // Meal Routes
    Route::get('meals', [MealController::class, 'index']);
    Route::get('meals/{meal}', [MealController::class, 'show']);
    Route::post('meals', [MealController::class, 'store']);
    Route::put('meals/{meal}', [MealController::class, 'update']);
    Route::delete('meals/{meal}', [MealController::class, 'destroy']);
    Route::post('/buying-from-kitchen', [kitchenController::class, 'buying_from_kitchen']);
    Route::get('students-customers', [kitchenController::class, 'students_customers']);
    Route::get('/students/{student}/purchases', [kitchenController::class, 'getStudentPurchases']);
    Route::get('/parent/wallet-dashboard', [ParentWalletDashboardController::class, 'show']);

    Route::get('employees-customers', [kitchenController::class, 'employees_customers']);
    Route::post('total-price-for-today', [kitchenController::class, 'total_price_for_today']);
    Route::post('verify-parent-identity-page', [StudentSupervisorController::class, 'verify_parent_identity_page']);
    Route::post('parent-chaildrens-data', [StudentSupervisorController::class, 'parent_chaildrens_data']);
    Route::apiResource('employee-wallets', EmployeeWalletController::class);
    Route::get('/employees-without-wallets', [EmployeeWalletController::class, 'getEmployeesWithoutWallets']);


// Parent Routes (for Flutter)
Route::get('/parent/lost-items', [LostItemController::class, 'getParentTickets']);
Route::get('/parent/lost-items/{ticket}', [LostItemController::class, 'showTicketForParent']);
Route::post('/parent/lost-items', [LostItemController::class, 'storeTicket']);
Route::post('/parent/lost-items/{ticket}/reply', [LostItemController::class, 'replyToTicket']);

// Admin Routes (for Vue)
Route::get('/admin/lost-items', [LostItemController::class, 'getAllTickets']);
Route::post('/admin/lost-items/{ticket}/close', [LostItemController::class, 'closeTicket']);

Route::get('/assessment-metadata', [AssessmentMetadataController::class, 'index']);


    Route::get('/notifications', [NotificationController::class, 'index']);
    Route::post('/notifications/{notification}/mark-as-read', [NotificationController::class, 'markAsRead']);
    Route::post('/notifications/mark-all-as-read', [NotificationController::class, 'markAllAsRead']);
    Route::prefix('stories')->group(function () {
        Route::get('/', [StoryController::class, 'index']);
        Route::post('/', [StoryController::class, 'store']);
        Route::get('/my', [StoryController::class, 'myStories']);
        Route::post('/{story}/view', [StoryController::class, 'view']);
        Route::delete('/{story}', [StoryController::class, 'destroy']);
    })->middleware('auth:sanctum');
    ///

    // Route::post('/update-fcm-token', function (Request $request) {
    //     $request->validate([
    //         'fcm_token' => 'required|string',
    //     ]);

    //     $user = Auth::user();
    //     if (!$user) {
    //         return response()->json(['message' => 'Unauthorized'], 401);
    //     }

    //     $user->fcm_token = $request->fcm_token;
    //     $user->save();

    //     return response()->json(['message' => 'FCM token updated successfully']);
    // })->middleware('auth:sanctum');
    Route::apiResource('parent-wallets', ParentWalletController::class);
    Route::get('/parents-without-wallets', [ParentWalletController::class, 'getParentsWithoutWallets']);
    Route::get('/parent-wallets/{wallet}/transactions', [ParentWalletController::class, 'getTransactions']);
    Route::get('/targeted-notifications/parents', [\App\Http\Controllers\TargetedNotificationController::class, 'getParentList']);
    Route::post('/targeted-notifications/send', [\App\Http\Controllers\TargetedNotificationController::class, 'send']);

    //
    Route::post('/parent-wallets/{wallet}/add-funds', [ParentWalletController::class, 'addFunds']);

    //




    Route::get('/students/{student}/financial-profile', [StudentFinancialController::class, 'getProfile']);

    Route::get('/student-fees/{student}', [StudentFeeController::class, 'getStudentFees']);
    Route::post('/fetch-bills', [billsController::class, 'fetch_bills']);
    Route::get('/show-bill', [billsController::class, 'show_bill']);
    Route::put('/update-bill/{id}', [billsController::class, 'updateBillItems']);

    Route::post('/students-for-attendance', [StudentAttendanceRecordController::class, 'students_for_attendance']);
    Route::post('/students-for-attendance-list', [StudentAttendanceRecordController::class, 'students_for_attendance_list']);
    Route::get('/student-attendance/{studentId}', function($studentId) {
        $month = request()->input('month', now()->month);
        $year = request()->input('year', now()->year);

        $records = StudentAttendanceRecord::where('student_id', $studentId)
            ->whereMonth('created_at', $month)
            ->whereYear('created_at', $year)
            ->orderBy('created_at', 'desc')
            ->get();

        return response()->json([
            'success' => true,
            'attendance' => $records
        ]);
    });

    Route::post('/send-attendance-for-one-student', [StudentAttendanceRecordController::class, 'save_attendance_for_one_student']);
    Route::post('/send-attendance-for-selected-students', [StudentAttendanceRecordController::class, 'save_attendance_for_selected_students']);
    Route::post('/cancel-attendance-for-one-student', [StudentAttendanceRecordController::class, 'cancel_attendance_for_one_student']);

    Route::apiResource('/grades', GradeController::class);

    //
    Route::apiResource('exam-types', ExamTypeController::class);

    Route::get('/students-restricted-meals/{studentId}/restricted-meals', [StudentRestrictedMealController::class, 'index']);

    Route::post('/students-restricted-meals/restricted-meals', [StudentRestrictedMealController::class, 'store']);
    Route::apiResource('student-enrollments', StudentEnrollmentController::class);

    Route::delete('/students-restricted-meals/{studentId}/restricted-meals/{mealId}', [StudentRestrictedMealController::class, 'destroy']);
    Route::get('/students/{student}/canteen-profile', [CanteenController::class, 'getStudentCanteenProfile']);
    Route::post('/students/{student}/purchase-limit', [CanteenController::class, 'updatePurchaseLimit']);
    Route::post('/students/{student}/restricted-meals', [CanteenController::class, 'addRestrictedMeal']);
    Route::delete('/students/{student}/restricted-meals', [CanteenController::class, 'removeRestrictedMeal']);


    //

    Route::get('/user/profile', [ProfileController::class, 'show']);
    Route::put('/user/profile/update', [ProfileController::class, 'update']);
    Route::post('/user/profile/change-password', [ProfileController::class, 'changePassword']);
    //
    Route::get('/students-limit/without-purchase-limit', [StudentPurchaseLimitController::class, 'studentsWithoutPurchaseLimit']);
    Route::get('/students-limit/purchase-limits', [StudentPurchaseLimitController::class, 'index']);
    Route::put('/students-limit/{id}/purchase-limit', [StudentPurchaseLimitController::class, 'updatePurchaseLimit']);
    //

});

// ✅ Routes للاستخدام من Edura system - بدون auth:sanctum middleware
// هذه الـ routes تستخدم api_token من Edura system للتحقق
Route::post('/edura/chat-groups/{group}/messages', [ChatController::class, 'sendMessage']);
Route::get('/edura/chat/activity', [ChatController::class, 'getEduraChatActivity']);

// ✅ Routes للبيانات الأكاديمية من Edura system - بدون auth:sanctum
Route::prefix('edura/academic-data')->group(function () {
    Route::get('/subjects-for-class', [EduraAcademicDataController::class, 'getSubjectsForClass']);
    Route::post('/names-by-ids', [EduraAcademicDataController::class, 'getNamesByIds']); // ✅ جلب مباشر من DB
});

// ✅ Routes للمعلمين من Edura system - بدون auth:sanctum
Route::prefix('edura')->group(function () {
    Route::get('/teachers/{teacherId}/chat-groups', [EduraChatGroupController::class, 'getTeacherChatGroups']); // ✅ مجموعات المعلم
});

Route::post('/check-in', [LogCheckInProcessController::class, 'checkInOrOut']);
Route::post('/check-out', [LogCheckInProcessController::class, 'checkOut']);
Route::get('/logs', [LogCheckInProcessController::class, 'index']);

Route::post('/calculate-salary/{employeeId}/{month}/{year}', [SalaryController::class, 'calculateSalary']);
Route::post('/salaries/calculate-bulk', [SalaryController::class, 'calculateSalariesForBulkEmployees']);


Route::post('/logs/export', function () {
    return Excel::download(new LogsExport, 'logs.xlsx');
});

Route::post('/logs/export/csv', function () {
    return Excel::download(new LogsExport, 'logs.csv');
});

Route::post('/logs/export/{format}', function ($format) {
    $exportClass = new LogsExport();
    $fileName = "logs.{$format}";

    if ($format == 'csv') {
        return Excel::download($exportClass, $fileName, \Maatwebsite\Excel\Excel::CSV);
    }

    if ($format == 'xlsx') {
        return Excel::download($exportClass, $fileName, \Maatwebsite\Excel\Excel::XLSX);
    }

    return response()->json(['error' => 'Invalid format'], 400);
});

Route::get('parent-pushed-student', function (Request $request) {
    $query = ParentInfo::query();

    if ($request->has('search')) {
        $search = $request->input('search');
        $query->where('first_name', 'like', "%$search%")
            ->orWhere('last_name', 'like', "%$search%")
            ->orWhere('phone_number_one', 'like', "%$search%")
            ->orWhere('national_number', 'like', "%$search%");
    }

    $parents = $query->get();
    return response()->json($parents);
});

Route::get('students-print-report', [StudentController::class, 'fetchingBigReport']);

Route::get('students-search', [StudentController::class, 'search']);


Route::apiResource('buses', BusController::class);

Route::post('/employees/{employeeId}/set-salary', [SalaryController::class, 'setSalary']);
Route::post('/employees/{employeeId}/adjust-salary', [SalaryController::class, 'adjustSalary']);
Route::apiResource('/subscribers', SubscriberController::class);
Route::post('/employees/{employeeId}/calculate-absences', [SalaryController::class, 'calculateAbsences']);
Route::get('/employees/salary/{id}', [EmployeeController::class, 'showFinancial']);
Route::post('/subscribers', [SubscriberController::class, 'store']);
Route::get('/salaries/{employeeId}', [SalaryController::class, 'show']);


Route::get('/departments-teacher-types', [EmployeeController::class, 'getDepartmentsAndTeacherTypes']);
Route::get('/filter-employees', [EmployeeController::class, 'filterEmployeesByTeacherType']);
Route::get('/teachers', action: [TeacherController::class, 'index']);

Route::get('/classes', [SchoolClassController::class, 'index']);
Route::put('/classes/{id}', [SchoolClassController::class, 'update']);
Route::post('/classes', [SchoolClassController::class, 'store']);

Route::get('/test',function(){
    return response()->json(['message' => 'API is working']);
});

Route::delete('/classes/{id}', [SchoolClassController::class, 'destroy']);
Route::post('/login', [AuthController::class, 'login']);
Route::post('/loginApp', [AuthController::class, 'loginApp']);
// Route::post('/logout', [AuthController::class, 'logout']);
Route::get('/report-cards/{student}/{term}/{template?}', [ReportCardController::class, 'generateReportCard']);
Route::get('/report-cards/{student}/{term}/{template?}/download', [ReportCardController::class, 'downloadReportCard']);
Route::post('/report-cards/{student}/{term}/email', [ReportCardController::class, 'emailReportCard']);
Route::apiResource('/exams', ExamController::class);
Route::get('/teacher-types', [TeacherTypeController::class, 'index']);
Route::post('/teacher-types', [TeacherTypeController::class, 'store']);
Route::put('/teacher-types/{id}', [TeacherTypeController::class, 'update']);
Route::delete('/teacher-types/{id}', [TeacherTypeController::class, 'destroy']);

Route::post('/update-activity', function(Request $request) {
    $user = $request->user();
    $user->last_activity = now();
    $user->save();

    return response()->json([
        'success' => true,
        'last_activity' => $user->last_activity,
    ]);
})->middleware('auth:sanctum');

Route::get('/departments', [DepartmentController::class, 'index']);
Route::post('/departments', [DepartmentController::class, 'store']);
Route::put('/departments/{id}', [DepartmentController::class, 'update']);
Route::delete('/departments/{id}', [DepartmentController::class, 'destroy']);



// Route::get('/subjects', [TeacherTypeController::class, 'index']); subject old comment

Route::post('/students/{student}/subscription-fees', [SubscriptionFeeController::class, 'storeSubscriptionFees']);

Route::get('/sections/{id}/subjects', [SectionController::class, 'getSubjects']);
Route::get('/subjects-by-class-and-section', [SectionController::class, 'getSubjectsByClassAndSection']);
Route::get('/students-by-class', [StudentController::class, 'getStudentsByClass']);
Route::get('sections-by-class/{classId}', [StudentController::class, 'getSectionsByClass']);
// routes/api.php
Route::get('students-by-class-and-section', [StudentController::class, 'getStudentsByClassAndSection']);
// routes/api.php
Route::get('subjects-for-exam/{examId}', [GradeController::class, 'getSubjectsForExam']);
Route::post('submit-grades', [GradeController::class, 'store']);

Route::get('grades/{exam_id}/student/{student_id}', [GradeController::class, 'getStudentGrades']);

Route::get('students-by-class-and-section-filtering', [StudentController::class, 'filteringStudent']);
Route::get('students-by-class-and-section-filtering-card', [StudentController::class, 'filteringStudentCard']);


Route::get('/attendances/', [AttendanceController::class, 'index']);
Route::get('/attendance-dates', [AttendanceController::class, 'getAvailableDates']);
Route::post('/attendances', [AttendanceController::class, 'store']);
Route::get('/attendances/{class_id}/{section_id}', [AttendanceController::class, 'show']);




// API Routes for Treasury
Route::prefix('treasuries')->group(function () {
    // Get all treasuries
    Route::get('/', [TreasuryController::class, 'index']);

    // Create a new treasury
    Route::post('/', [TreasuryController::class, 'store']);

    // Deposit money into a treasury
    Route::post('{id}/deposit', [TreasuryController::class, 'deposit']);

    // Disburse money from a treasury
    Route::post('{id}/disburse', [TreasuryController::class, 'disburse']);
});
Route::get('/treasury-transactions', [TreasuryTransactionController::class, 'index']);
Route::get('/treasury-transactions/export/{format}', [TreasuryTransactionController::class, 'export']);



Route::prefix('permissions')->group(function () {
    Route::get('/', [PermissionEmployeeController::class, 'index']);
    Route::get('/employees', [PermissionEmployeeController::class, 'getEmployee']);
    Route::post('/', [PermissionEmployeeController::class, 'store']);
    Route::get('/{id}', [PermissionEmployeeController::class, 'show']);
    Route::put('/{id}', [PermissionEmployeeController::class, 'update']);
    Route::delete('/{id}', [PermissionEmployeeController::class, 'destroy']);
    Route::get('/trashed', [PermissionEmployeeController::class, 'trashed']);
    Route::post('/restore/{id}', [PermissionEmployeeController::class, 'restore']);
});

// Route::get('/buses', [BusController::class, 'index']);
// Route::post('/buses', [BusController::class, 'store']);
// Route::put('/buses/{id}', [BusController::class, 'update']);
// Route::delete('/buses/{id}', [BusController::class, 'destroy']);






Route::apiResource('health-files', HealthFileController::class);
Route::get('/students/{student}/parent', [StudentController::class, 'getParent']);
