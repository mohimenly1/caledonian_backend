<?php

namespace App\Http\Controllers; // تأكد من أن الـ namespace صحيح

use App\Models\User;
use App\Models\ParentInfo;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class UserController extends Controller
{
    /**
     * عرض قائمة بالمستخدمين مع تحميل دائم للملفات الشخصية.
     */
    public function index(Request $request)
    {
        $request->validate([
            'per_page' => 'sometimes|integer|min:1|max:100',
            'user_type' => 'sometimes|string',
            'search' => 'sometimes|string|max:255',
        ]);

        $perPage = $request->input('per_page', 15);
        $query = User::query();

        if ($request->filled('user_type')) {
            $query->where('user_type', $request->user_type);
        }

        if ($request->filled('search')) {
            $searchTerm = $request->search;
            $query->where(function ($q) use ($searchTerm) {
                $q->where('name', 'like', "%{$searchTerm}%")
                  ->orWhere('username', 'like', "%{$searchTerm}%")
                  ->orWhere('email', 'like', "%{$searchTerm}%")
                  ->orWhere('phone', 'like', "%{$searchTerm}%");
            });
        }

        // تحميل العلاقات دائمًا لضمان عمل "عرض الملف الشخصي" بشكل صحيح
        $query->with(['studentProfile', 'employeeProfile', 'parentProfile']);

        // إرجاع استجابة JSON مع ترقيم الصفحات
        $users = $query->orderBy('name')->paginate($perPage);
        
        return response()->json($users);
    }
    
    public function store(Request $request)
    {
        $validatedData = $request->validate([
            'name' => 'required|string|max:255',
            'phone' => ['required', 'string', 'max:255', Rule::unique('users', 'phone')],
            'username' => ['required', 'string', 'max:255', Rule::unique('users', 'username')],
            'email' => ['nullable', 'string', 'email', 'max:255', Rule::unique('users', 'email')],
            'address' => 'required|string|max:255', // تم تعديله ليكون مطلوبًا
            'password' => 'required|string|min:8',
            'user_type' => ['required', 'string', Rule::in(['admin', 'teacher', 'staff', 'student', 'parent', 'driver', 'financial', 'doctor', 'moder', 'hr', 'prin', 'student_m', 'cashier', 'student supervisor', 'developer'])],
            'photo' => 'nullable|image|mimes:jpeg,png,jpg,gif,webp|max:2048',
            'bio' => 'nullable|string',
        ]);

        if ($request->hasFile('photo')) {
            $validatedData['photo'] = $request->file('photo')->store('users/photos', 'public');
        }

        $user = User::create($validatedData);

        return response()->json([
            'message' => 'User account created successfully. You can now create their specific profile.',
            'user' => $user
        ], 201);
    }


    public function storeParent(Request $request)
{
    // الخطوة 1: أضفنا parent_id إلى قواعد التحقق
    $validatedData = $request->validate([
        'name' => 'required|string|max:255',
        'phone' => ['required', 'string', 'max:255', Rule::unique('users', 'phone')],
        'username' => ['required', 'string', 'max:255', Rule::unique('users', 'username')],
        'email' => ['nullable', 'string', 'email', 'max:255', Rule::unique('users', 'email')],
        'address' => 'required|string|max:255',
        'password' => 'required|string|min:8',
        'user_type' => ['required', 'string', Rule::in(['admin', 'teacher', 'staff', 'student', 'parent', 'driver', 'financial', 'doctor', 'moder', 'hr', 'prin', 'student_m', 'cashier', 'student supervisor', 'developer'])],
        'photo' => 'nullable|image|mimes:jpeg,png,jpg,gif,webp|max:2048',
        'bio' => 'nullable|string',
        'parent_id' => 'required|exists:parents,id', // <-- ✨ إضافة مهمة للتحقق من ولي الأمر
    ]);

    if ($request->hasFile('photo')) {
        $validatedData['photo'] = $request->file('photo')->store('users/photos', 'public');
    }

    // الخطوة 2: إنشاء المستخدم (الكود الحالي)
    $user = User::create($validatedData);

    // الخطوة 3: ✨ المنطق الجديد لربط المستخدم بولي الأمر ✨
    // نبحث عن ولي الأمر باستخدام الـ ID الذي تم التحقق منه
    $parent = ParentInfo::find($validatedData['parent_id']);
    
    // نقوم بتحديث حقل user_id بمعرّف المستخدم الجديد وحفظه
    $parent->user_id = $user->id;
    $parent->save();
    // --- نهاية المنطق الجديد ---

    return response()->json([
        'message' => 'User account created and linked to the parent successfully.',
        'user' => $user
    ], 201);
}

public function show(User $user)
{
    $user->load(
        'teacherCourseAssignments.courseOffering.subject', 
        'teacherCourseAssignments.courseOffering.schoolClass', 
        'teacherCourseAssignments.section'
    );

    return response()->json($user->toArray());
}
   
    public function update(Request $request, User $users_controller) 
    {
        // يمكنك استخدام $users_controller مباشرة أو إسناده إلى متغير أوضح
        $user = $users_controller;

        // بقية الكود يبقى كما هو تمامًا
        $validatedData = $request->validate([
            'name' => 'sometimes|required|string|max:255',
            'phone' => ['sometimes', 'required', 'string', 'max:255', Rule::unique('users', 'phone')->ignore($user->id)],
            'username' => ['sometimes', 'required', 'string', 'max:255', Rule::unique('users', 'username')->ignore($user->id)],
            'email' => ['nullable', 'string', 'email', 'max:255', Rule::unique('users', 'email')->ignore($user->id)],
            'address' => 'sometimes|required|string|max:255',
            'password' => 'nullable|string|min:8',
            'user_type' => ['sometimes', 'required', 'string', Rule::in(['admin', 'teacher', 'staff', 'student', 'parent', 'driver', 'financial', 'doctor', 'moder', 'hr', 'prin', 'student_m', 'cashier', 'student supervisor', 'developer'])],
            'photo' => 'nullable|image|mimes:jpeg,png,jpg,gif,webp|max:2048',
            'bio' => 'nullable|string',
        ]);

        // ... بقية منطق الدالة ...
        if ($request->hasFile('photo')) {
            if ($user->photo) Storage::disk('public')->delete($user->photo);
            $validatedData['photo'] = $request->file('photo')->store('users/photos', 'public');
        }

        if (!$request->filled('password')) {
            unset($validatedData['password']);
        }
        
        $user->update($validatedData);

        return response()->json(['message' => 'User account updated successfully.','user' => $user]);
    }

    public function destroy(User $users_controller)
    {
        // ✅ استخدام نفس اسم الـ parameter الذي يستخدمه route model binding
        $user = $users_controller;
        
        try {
            // ✅ التحقق من أن الـ user موجود وله ID صحيح
            if (!$user || !$user->id) {
                throw new \Exception('المستخدم غير موجود أو ID غير صحيح.');
            }
            
            // ✅ استخدام database transaction لضمان الحذف الكامل
            DB::beginTransaction();
            
            // 1. ✅ حذف جميع الإسنادات الدراسية للمعلم (teacher_course_assignments) باستخدام soft delete
            // ✅ لأن soft delete للمعلم لا ينشئ cascade delete تلقائياً
            $assignments = $user->teacherCourseAssignments()->get();
            foreach ($assignments as $assignment) {
                $assignment->delete(); // soft delete
            }
            
            // 2. ✅ استخدام soft delete للمعلم (حذف ناعم)
            // ✅ هذا سيضع deleted_at timestamp بدلاً من حذف السجل نهائياً
            $user->delete();
            
            DB::commit();
            
            Log::info('[UserController::destroy] User soft-deleted successfully', [
                'user_id' => $user->id,
                'user_name' => $user->name ?? 'N/A',
                'assignments_count' => $assignments->count()
            ]);
            
            return response()->json([
                'message' => 'تم حذف المستخدم بنجاح.',
                'success' => true
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            
            // ✅ تسجيل الخطأ للتحقق منه
            Log::error('[UserController::destroy] Error deleting user', [
                'user_id' => $user->id ?? null,
                'user_name' => $user->name ?? 'N/A',
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            
            return response()->json([
                'message' => 'حدث خطأ أثناء حذف المستخدم: ' . $e->getMessage(),
                'success' => false,
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * ⭐⭐ استعادة معلم محذوف بشكل ناعم (soft deleted) ⭐⭐
     */
    public function restore($users_controller)
    {
        try {
            // ✅ جلب المستخدم المحذوف باستخدام withTrashed() لأن route model binding لا يجلب soft deleted users
            $user = User::withTrashed()->find($users_controller);
            
            // ✅ التحقق من أن الـ user موجود وله ID صحيح
            if (!$user || !$user->id) {
                return response()->json([
                    'message' => 'المستخدم غير موجود أو ID غير صحيح.',
                    'success' => false
                ], 404);
            }
            
            // ✅ التحقق من أن المستخدم محذوف بشكل ناعم
            if (!$user->trashed()) {
                return response()->json([
                    'message' => 'المستخدم غير محذوف، لا يمكن استعادته.',
                    'success' => false
                ], 400);
            }
            
            // ✅ استخدام database transaction لضمان الاستعادة الكاملة
            DB::beginTransaction();
            
            // 1. ✅ استعادة جميع الإسنادات الدراسية للمعلم (teacher_course_assignments)
            $assignments = $user->teacherCourseAssignments()->onlyTrashed()->get();
            foreach ($assignments as $assignment) {
                $assignment->restore(); // استعادة soft delete
            }
            
            // 2. ✅ استعادة المعلم (soft delete)
            $user->restore();
            
            DB::commit();
            
            Log::info('[UserController::restore] User restored successfully', [
                'user_id' => $user->id,
                'user_name' => $user->name ?? 'N/A',
                'assignments_count' => $assignments->count()
            ]);
            
            return response()->json([
                'message' => 'تم استعادة المستخدم بنجاح.',
                'success' => true
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            
            // ✅ تسجيل الخطأ للتحقق منه
            Log::error('[UserController::restore] Error restoring user', [
                'user_id' => $user->id ?? null,
                'user_name' => $user->name ?? 'N/A',
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            
            return response()->json([
                'message' => 'حدث خطأ أثناء استعادة المستخدم: ' . $e->getMessage(),
                'success' => false,
                'error' => $e->getMessage()
            ], 500);
        }
    }
}