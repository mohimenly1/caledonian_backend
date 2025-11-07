<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Invoice;
use App\Models\Transaction;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class EduraTuitionController extends Controller
{
    /**
     * جلب ملخص فواتير الأقساط المستحقة والمدفوعة لـ Edura
     */
    public function getTuitionSummary(Request $request)
    {
        // 1. حساب عدد الفواتير غير المدفوعة أو المدفوعة جزئياً
        $pendingTuitionCount = Invoice::whereIn('status', ['unpaid', 'partially_paid'])->count();

        // 2. حساب إجمالي المبالغ المتبقية
        $pendingTuitionAmount = 0;
        Invoice::whereIn('status', ['unpaid', 'partially_paid'])
                ->withSum(['payments' => function ($query) {
                    // تأكد من مطابقة النوع - بياناتك السابقة أظهرت 'income' لمدفوعات الفواتير
                    $query->where('type', 'income'); 
                }], 'amount')
                ->chunk(100, function ($invoices) use (&$pendingTuitionAmount) {
                    foreach ($invoices as $invoice) {
                        $paid = $invoice->payments_sum_amount ?? 0; // استخدم الاسم المستعار من withSum
                        $remaining = $invoice->final_amount - $paid;
                        if ($remaining > 0) {
                            $pendingTuitionAmount += $remaining;
                        }
                    }
                });

        // --- ⭐ 3. حساب إجمالي الأقساط المدفوعة (الإيرادات الحقيقية من الأقساط) ⭐ ---
        // هذا يجمع كل الحركات المالية التي هي 'income' ومرتبطة بـ 'App\Models\Invoice'
        $totalTuitionPaid = Transaction::where('related_type', \App\Models\Invoice::class)
                                ->where('type', 'income') // تأكد أن 'income' هو النوع الصحيح للدفع
                                ->sum('amount');
        // --- نهاية الإضافة ---


        return response()->json([
            'pending_tuition_count' => $pendingTuitionCount,
            'pending_tuition_amount' => $pendingTuitionAmount,
            'total_tuition_paid' => $totalTuitionPaid, // <-- ⭐ إرجاع القيمة الجديدة
        ]);
    }

     /**
      * جلب قائمة فواتير الأقساط (Invoices) لـ Edura مع Pagination
      */
     public function getTuitionInvoices(Request $request)
     {
         $query = Invoice::with('parent:id,first_name,last_name')
                         ->withSum(['payments' => function ($query) { // <-- التأكد من مطابقة النوع
                             $query->where('type', 'income');
                         }], 'amount')
                         ->orderBy('issue_date', 'desc');

         // تطبيق فلتر الحالة
         if ($request->filled('status') && in_array($request->status, ['unpaid', 'partially_paid', 'paid'])) {
             $query->where('status', $request->status);
         }

         // تطبيق فلتر البحث
         if ($request->filled('search')) {
             $search = $request->search;
             $query->where(function ($q) use ($search) {
                 $q->where('invoice_number', 'like', "%{$search}%")
                   ->orWhereHas('parent', function ($subQ) use ($search) {
                       $subQ->where('first_name', 'like', "%{$search}%")
                            ->orWhere('last_name', 'like', "%{$search}%");
                   });
             });
         }


         $invoices = $query->paginate($request->input('per_page', 15))->withQueryString();

         // إضافة المتبقي وتحديث الحالة
         $invoices->getCollection()->transform(function ($invoice) {
             $invoice->paid_amount = $invoice->payments_sum_amount ?? 0; // استخدام الاسم المستعار
             $invoice->remaining_amount = $invoice->final_amount - $invoice->paid_amount;
             
             // تحديث الحالة بناءً على المبالغ
             if ($invoice->paid_amount <= 0 && $invoice->final_amount > 0) {
                 $invoice->status = 'unpaid';
             } elseif ($invoice->paid_amount < $invoice->final_amount && $invoice->paid_amount > 0) {
                 $invoice->status = 'partially_paid';
             } elseif ($invoice->paid_amount >= $invoice->final_amount) {
                 $invoice->status = 'paid';
             } else {
                 $invoice->status = 'paid'; // للفواتير ذات القيمة الصفرية
             }
             return $invoice;
         });


         return response()->json($invoices);
     }
}
