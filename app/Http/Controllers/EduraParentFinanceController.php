<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\ParentInfo;
use App\Models\Invoice;
use App\Models\Transaction;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;

class EduraParentFinanceController extends Controller
{
    /**
     * جلب قائمة أولياء الأمور مع ملخصاتهم المالية وفواتيرهم
     */
  // في EduraParentFinanceController
// في EduraParentFinanceController
// في EduraParentFinanceController
// في EduraParentFinanceController
public function index(Request $request)
{
    // استعلام لجلب الإحصائيات الكلية (جميع السجلات)
    $statsQuery = ParentInfo::query()
        ->with(['invoices' => function($query) {
            $query->select('id', 'parent_id', 'final_amount')
                  ->with(['payments' => function($q) {
                      $q->where('type', 'income');
                  }]);
        }]);

    // تطبيق نفس شروط البحث على الإحصائيات
    if ($request->filled('search')) {
        $search = $request->search;
        $statsQuery->where(function($q) use ($search) {
            $q->where(DB::raw("CONCAT(first_name, ' ', last_name)"), 'like', "%{$search}%")
              ->orWhere('phone_number_one', 'like', "%{$search}%")
              ->orWhere('email', 'like', "%{$search}%")
              ->orWhereHas('students', function($studentQuery) use ($search) {
                  $studentQuery->where('name', 'like', "%{$search}%");
              });
        });
    }

    // تطبيق نفس شروط الفلترة على الإحصائيات
    if ($request->filled('status') && $request->status !== 'all') {
        $status = $request->status;

        if ($status === 'no_invoices') {
            $statsQuery->whereDoesntHave('invoices');
        } else {
            $statsQuery->whereHas('invoices', function($invoiceQuery) use ($status) {
                if ($status === 'paid') {
                    $invoiceQuery->whereHas('payments', function($paymentQuery) {
                        $paymentQuery->selectRaw('related_id, SUM(amount) as total_paid')
                                   ->where('type', 'income')
                                   ->where('related_type', Invoice::class)
                                   ->groupBy('related_id')
                                   ->havingRaw('SUM(amount) >= invoices.final_amount');
                    });
                } elseif ($status === 'unpaid') {
                    $invoiceQuery->where(function($q) {
                        $q->whereDoesntHave('payments', function($paymentQuery) {
                            $paymentQuery->where('type', 'income')
                                       ->where('related_type', Invoice::class);
                        })->orWhereHas('payments', function($paymentQuery) {
                            $paymentQuery->selectRaw('related_id, SUM(amount) as total_paid')
                                       ->where('type', 'income')
                                       ->where('related_type', Invoice::class)
                                       ->groupBy('related_id')
                                       ->havingRaw('SUM(amount) = 0');
                        });
                    })->where('final_amount', '>', 0);
                } elseif ($status === 'partially_paid') {
                    $invoiceQuery->whereHas('payments', function($paymentQuery) {
                        $paymentQuery->selectRaw('related_id, SUM(amount) as total_paid')
                                   ->where('type', 'income')
                                   ->where('related_type', Invoice::class)
                                   ->groupBy('related_id')
                                   ->havingRaw('SUM(amount) > 0 AND SUM(amount) < invoices.final_amount');
                    });
                }
            });
        }
    }

    // حساب الإحصائيات الكلية
    $allParents = $statsQuery->get();

    $totalStats = [
        'total_invoiced' => 0,
        'total_paid' => 0,
        'total_remaining' => 0
    ];

    $paymentStatusStats = [
        'paid' => 0,
        'partially_paid' => 0,
        'unpaid' => 0,
        'no_invoices' => 0
    ];

    foreach ($allParents as $parent) {
        $parentInvoiced = $parent->invoices->sum('final_amount');
        $parentPaid = $parent->invoices->sum(function($invoice) {
            return $invoice->payments->where('type', 'income')->sum('amount');
        });
        $parentRemaining = $parentInvoiced - $parentPaid;

        // ⭐ حساب الإحصائيات المالية
        $totalStats['total_invoiced'] += $parentInvoiced;
        $totalStats['total_paid'] += $parentPaid;
        $totalStats['total_remaining'] += $parentRemaining;

        // حساب إحصائيات الحالات
        if ($parent->invoices->count() === 0) {
            $paymentStatusStats['no_invoices']++;
        } elseif ($parentRemaining === 0 && $parentInvoiced > 0) {
            $paymentStatusStats['paid']++;
        } elseif ($parentRemaining > 0 && $parentPaid > 0) {
            $paymentStatusStats['partially_paid']++;
        } elseif ($parentRemaining > 0 && $parentPaid === 0) {
            $paymentStatusStats['unpaid']++;
        } else {
            $paymentStatusStats['no_invoices']++;
        }
    }


    // استعلام للبيانات المعروضة (مع Pagination)
    $displayQuery = ParentInfo::query()
        ->select('id', 'user_id', 'first_name', 'last_name', 'email', 'phone_number_one')
        ->with([
            'user:id,email,phone', // ✅ إضافة علاقة User للحصول على user_id
            'students:id,name,parent_id,class_id',
            'students.class:id,name',
            'invoices' => function ($query) {
                $query->select('id', 'parent_id', 'invoice_number', 'issue_date', 'due_date', 'final_amount', 'status')
                      ->with(['payments' => function($q) {
                          $q->where('type', 'income');
                      }])
                      ->with(['invoiceItems' => function ($q) {
                          $q->select('id', 'invoice_id', 'student_id', 'fee_structure_id', 'description', 'amount')
                            ->with(['student:id,name', 'feeStructure:id,fee_type_id', 'feeStructure.feeType:id,name']);
                      }])
                      ->orderBy('issue_date', 'desc');
            }
        ]);

    // تطبيق نفس شروط البحث على البيانات المعروضة
    if ($request->filled('search')) {
        $search = $request->search;
        $displayQuery->where(function($q) use ($search) {
            $q->where(DB::raw("CONCAT(first_name, ' ', last_name)"), 'like', "%{$search}%")
              ->orWhere('phone_number_one', 'like', "%{$search}%")
              ->orWhere('email', 'like', "%{$search}%")
              ->orWhereHas('students', function($studentQuery) use ($search) {
                  $studentQuery->where('name', 'like', "%{$search}%");
              });
        });
    }

    // تطبيق نفس شروط الفلترة على البيانات المعروضة
    if ($request->filled('status') && $request->status !== 'all') {
        $status = $request->status;

        if ($status === 'no_invoices') {
            $displayQuery->whereDoesntHave('invoices');
        } else {
            $displayQuery->whereHas('invoices', function($invoiceQuery) use ($status) {
                if ($status === 'paid') {
                    $invoiceQuery->whereHas('payments', function($paymentQuery) {
                        $paymentQuery->selectRaw('related_id, SUM(amount) as total_paid')
                                   ->where('type', 'income')
                                   ->where('related_type', Invoice::class)
                                   ->groupBy('related_id')
                                   ->havingRaw('SUM(amount) >= invoices.final_amount');
                    });
                } elseif ($status === 'unpaid') {
                    $invoiceQuery->where(function($q) {
                        $q->whereDoesntHave('payments', function($paymentQuery) {
                            $paymentQuery->where('type', 'income')
                                       ->where('related_type', Invoice::class);
                        })->orWhereHas('payments', function($paymentQuery) {
                            $paymentQuery->selectRaw('related_id, SUM(amount) as total_paid')
                                       ->where('type', 'income')
                                       ->where('related_type', Invoice::class)
                                       ->groupBy('related_id')
                                       ->havingRaw('SUM(amount) = 0');
                        });
                    })->where('final_amount', '>', 0);
                } elseif ($status === 'partially_paid') {
                    $invoiceQuery->whereHas('payments', function($paymentQuery) {
                        $paymentQuery->selectRaw('related_id, SUM(amount) as total_paid')
                                   ->where('type', 'income')
                                   ->where('related_type', Invoice::class)
                                   ->groupBy('related_id')
                                   ->havingRaw('SUM(amount) > 0 AND SUM(amount) < invoices.final_amount');
                    });
                }
            });
        }
    }

    $parents = $displayQuery->paginate($request->input('per_page', 10))->withQueryString();

    // حساب المبالغ المتبقية وتحديث الحالة للبيانات المعروضة
    $parents->getCollection()->transform(function ($parent) {

        // حساب الإجماليات لولي الأمر
        $parent->total_invoiced = $parent->invoices->sum('final_amount');
        $parent->total_paid = $parent->invoices->sum(function($invoice) {
            return $invoice->payments->where('type', 'income')->sum('amount');
        });
        $parent->total_remaining = $parent->total_invoiced - $parent->total_paid;

        // حساب حالة كل فاتورة على حدة
        $parent->invoices->transform(function ($invoice) {
            $invoice->paid_amount = $invoice->payments->where('type', 'income')->sum('amount');
            $invoice->remaining_amount = $invoice->final_amount - $invoice->paid_amount;

            // تحديث حالة الفاتورة بناءً على المدفوعات
            if ($invoice->paid_amount <= 0 && $invoice->final_amount > 0) {
                $invoice->status = 'unpaid';
            } elseif ($invoice->paid_amount < $invoice->final_amount && $invoice->paid_amount > 0) {
                $invoice->status = 'partially_paid';
            } elseif ($invoice->paid_amount >= $invoice->final_amount) {
                $invoice->status = 'paid';
            } else {
                $invoice->status = 'paid';
            }

            // تحسين بيانات عناصر الفاتورة
            if ($invoice->relationLoaded('invoiceItems')) {
                $invoice->invoice_items = $invoice->invoiceItems->map(function ($item) {
                    return [
                        'id' => $item->id,
                        'description' => $item->description,
                        'amount' => $item->amount,
                        'student_name' => $item->student->name ?? 'غير محدد',
                        'fee_type' => $item->feeStructure->feeType->name ?? 'رسوم عامة',
                        'fee_structure_id' => $item->fee_structure_id
                    ];
                });
            } else {
                $invoice->invoice_items = [];
            }

            // إزالة العلاقات الأصلية لتقليل حجم البيانات
            unset($invoice->invoiceItems);
            unset($invoice->payments);
            unset($invoice->relationships);

            return $invoice->only([
                'id', 'invoice_number', 'issue_date', 'due_date',
                'final_amount', 'paid_amount', 'remaining_amount',
                'status', 'invoice_items'
            ]);
        });

        // ✅ إضافة user_id إلى البيانات المرجعة
        // التأكد من وجود user_id (من parentInfo مباشرة أو من علاقة user)
        $parentUserId = $parent->user_id ?? ($parent->user->id ?? null);

        // ✅ تسجيل user_id للتحقق
        Log::debug('[EduraParentFinanceController] Setting user_id for parent', [
            'parent_id' => $parent->id,
            'user_id_from_parent' => $parent->user_id,
            'user_id_from_relation' => $parent->user->id ?? null,
            'final_user_id' => $parentUserId,
        ]);

        // ✅ إضافة user_id إلى attributes لجعله جزءاً من البيانات المرجعة
        $parent->setAttribute('user_id', $parentUserId);

        // ✅ جعل user_id مرئياً في toArray()
        $parent->makeVisible(['user_id']);

        return $parent;
    });

    // إضافة الإحصائيات الكلية للاستجابة
    $response = $parents->toArray();
    $response['total_stats'] = $totalStats;
    $response['payment_status_stats'] = $paymentStatusStats; // ⭐ إضافة إحصائيات الحالات


    return response()->json($response);
}
}
