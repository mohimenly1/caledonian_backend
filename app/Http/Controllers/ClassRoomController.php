<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\ClassRoom;

class ClassRoomController extends Controller
{
    //
    public function getSections(ClassRoom $class)
    {
        // باستخدام Route Model Binding، يتم جلب الفصل تلقائيًا
        // ثم نرجع الشعب المرتبطة به مباشرةً
        return response()->json($class->sections);
    }

}
