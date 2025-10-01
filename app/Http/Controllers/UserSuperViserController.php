<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\User;

class UserSuperViserController extends Controller
{
    // في UserController.php

public function index(Request $request)
{
    $query = User::query();

    // ✨ --- الجزء الأهم لحل المشكلة --- ✨
    // التحقق إذا كان الطلب يحتوي على 'user_type' وتطبيقه كفلتر
    if ($request->filled('user_type')) {
        $query->where('user_type', $request->user_type);
    }
    // --- نهاية الجزء المهم ---

    // التعامل مع البحث بالاسم أو اسم المستخدم
    if ($request->filled('search')) {
        $searchTerm = $request->search;
        $query->where(function($q) use ($searchTerm) {
            $q->where('name', 'like', "%{$searchTerm}%")
              ->orWhere('username', 'like', "%{$searchTerm}%");
        });
    }

    // إرجاع النتائج مع ترقيم الصفحات
    return $query->latest()->paginate($request->get('per_page', 15));
}
}
