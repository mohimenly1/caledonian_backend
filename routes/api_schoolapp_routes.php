<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\StatsController;
use App\Http\Controllers\ClientDashboardController; // <-- إضافة جديدة
// ... (باقي use statements)

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
*/

// ... (المسارات العامة)

// --- المسارات المحمية ---
Route::middleware('auth:sanctum')->group(function () {

    Route::get('/stats', [StatsController::class, 'index']);
    
    // ⭐⭐ المسار الجديد لبيانات المصروفات ⭐⭐
    Route::get('/edura/expense-summary', [ClientDashboardController::class, 'getExpenseSummary']);
    // ⭐⭐ نهاية المسار الجديد ⭐⭐

    // ... (باقي المسارات المحمية)
    Route::apiResource('/bills', \App\Http\Controllers\BillController::class); // تأكد من وجوده

});
