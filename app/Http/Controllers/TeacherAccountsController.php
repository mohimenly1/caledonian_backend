<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\User;

class TeacherAccountsController extends Controller
{
    //

    public function index(Request $request)
{
    $query = User::with('teacherCourseAssignments'); 

    // ✨ فلترة حسب نوع المستخدم (المعلمون في حالتنا) ✨
    if ($request->has('user_type')) {
        $query->where('user_type', $request->user_type);
    }

    // فلترة حسب البحث (إذا كان المستخدم يبحث بالاسم أو اسم المستخدم)
    if ($request->has('search')) {
        $searchTerm = $request->search;
        $query->where(function($q) use ($searchTerm) {
            $q->where('name', 'like', "%{$searchTerm}%")
              ->orWhere('username', 'like', "%{$searchTerm}%");
        });
    }

    // جلب النتائج مع ترقيم الصفحات
    return $query->latest()->paginate($request->get('per_page', 15));
}
}
