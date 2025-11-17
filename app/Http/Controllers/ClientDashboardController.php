<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Bill;
use App\Models\Transaction;
use App\Models\Treasury;
use App\Models\Invoice; // <-- استيراد Invoice
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class ClientDashboardController extends Controller
{
    /**
     * جلب ملخص المصروفات والرصيد لـ Edura (للداشبورد)
     */
    public function getExpenseSummary(Request $request)
    {
        $now = Carbon::now();
        $currentYear = $now->year;
        $currentMonth = $now->month;

        // حساب إجمالي المصروفات للشهر الحالي
        $monthlyExpenses = Transaction::where('type', 'expense')
            ->whereYear('payment_date', $currentYear)
            ->whereMonth('payment_date', $currentMonth)
            ->sum('amount');

        // حساب عدد فواتير الموردين غير المدفوعة أو المدفوعة جزئياً ومجموع المتبقي
        $pendingBillsQuery = Bill::withSum(['payments as paid_amount' => function ($query) {
            $query->where('type', 'expense');
        }], 'amount')
            ->whereIn('status', ['unpaid', 'partially_paid']);

        $pendingPaymentsCount = (clone $pendingBillsQuery)->count();
        $pendingPaymentsAmount = (clone $pendingBillsQuery)->get()->sum(function ($bill) {
            $paid = $bill->paid_amount ?? 0;
            return max(($bill->total_amount ?? 0) - $paid, 0);
        });

        // حساب الرصيد الكلي
        $totalBalance = Treasury::sum('balance');

        // --- جلب آخر 5 مصروفات للشهر الحالي ---
        $recentCurrentMonthExpenses = Transaction::where('type', 'expense')
            ->whereYear('payment_date', $currentYear)
            ->whereMonth('payment_date', $currentMonth)
            ->orderBy('payment_date', 'desc')
            ->orderBy('id', 'desc') // Ensure consistent ordering for same date
            ->limit(5)
            // Ensure date format compatibility with Vue/JS
            ->select(['id', 'description', DB::raw("DATE_FORMAT(payment_date, '%Y-%m-%d') as date"), 'amount'])
            ->get();
        // --- نهاية التعديل ---

        return response()->json([
            'monthly_expenses' => $monthlyExpenses,
            'pending_payments_count' => $pendingPaymentsCount,
            'pending_payments_amount' => $pendingPaymentsAmount,
            'total_balance' => $totalBalance,
            'recent_current_month_expenses' => $recentCurrentMonthExpenses,
            'current_month_name' => $now->translatedFormat('F Y'), // اسم الشهر الحالي
        ]);
    }

    /**
     * جلب قائمة فواتير الموردين (Bills) لـ Edura مع Pagination
     */
    public function getBills(Request $request)
    {
         $query = Bill::with('vendor:id,name')
         ->withSum(['payments as paid_amount' => function ($query) {
            // Correct type for bill payments
            $query->where('type', 'expense'); // Assuming 'expense' is the type when paying a Bill
        }], 'amount')
                      ->orderBy('issue_date', 'desc');

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        $bills = $query->paginate($request->input('per_page', 15))->withQueryString();

        $bills->getCollection()->transform(function ($bill) {
            $bill->paid_amount = $bill->paid_amount ?? 0;
            $bill->remaining_amount = $bill->total_amount - $bill->paid_amount;
             if ($bill->paid_amount <= 0 && $bill->total_amount > 0) {
                 $bill->status = 'unpaid';
             } elseif ($bill->paid_amount < $bill->total_amount && $bill->paid_amount > 0) {
                 $bill->status = 'partially_paid';
             } elseif ($bill->paid_amount >= $bill->total_amount) {
                 $bill->status = 'paid';
             } else {
                 $bill->status = 'paid'; // Default for zero amount
             }
            return $bill;
        });

        return response()->json($bills);
    }

     // --- ⭐⭐ دالة جلب المصروفات الشهرية (مُعدلة) ⭐⭐ ---
     /**
      * جلب المصروفات مجمعة حسب الشهر لتقرير Edura
      */
      public function getMonthlyExpenses(Request $request)
      {
          // --- حساب الملخص الشهري (Transaction Summary) ---
          $summaryQuery = Transaction::where('type', 'expense')
                           ->select(
                               DB::raw("DATE_FORMAT(payment_date, '%Y-%m') as month"),
                               DB::raw('SUM(amount) as total_expenses'), // المصروفات المدفوعة
                               DB::raw('COUNT(*) as transaction_count')
                           )
                           ->groupBy('month')
                           ->orderBy('month', 'desc');

          $monthlyData = $summaryQuery->get()->map(function ($item) {
              $item->month_name = Carbon::createFromFormat('Y-m', $item->month)->translatedFormat('F Y');

              // حساب إجمالي الفواتير المستلمة في الشهر
              $yearMonth = explode('-', $item->month);
              $item->total_bills_issued = Bill::whereYear('issue_date', $yearMonth[0])
                                              ->whereMonth('issue_date', $yearMonth[1])
                                              ->sum('total_amount');
              // عدد الفواتير المستلمة
               $item->bills_issued_count = Bill::whereYear('issue_date', $yearMonth[0])
                                              ->whereMonth('issue_date', $yearMonth[1])
                                              ->count();

              return $item;
          });

          // --- جلب التفاصيل للشهر المحدد ---
          $detailedTransactions = null;
          $billsIssuedInMonth = null;

          if ($request->filled('view_month') && preg_match('/^\d{4}-\d{2}$/', $request->view_month)) {
              $yearMonth = explode('-', $request->view_month);

              // 1. جلب حركات الصرف المفصلة (كما في السابق)
              $detailedTransactions = Transaction::where('type', 'expense')
                                      ->whereYear('payment_date', $yearMonth[0])
                                      ->whereMonth('payment_date', $yearMonth[1])
                                      ->with([
                                          'related' => function ($morphTo) {
                                              $morphTo->morphWith([
                                                  Bill::class => ['vendor:id,name']
                                              ]);
                                          }
                                      ])
                                      ->orderBy('payment_date', 'desc')->orderBy('id', 'desc')
                                      ->select(['id', 'description', DB::raw("DATE_FORMAT(payment_date, '%Y-%m-%d') as date"), 'amount', 'related_type', 'related_id'])
                                      ->paginate(20, ['*'], 'transactions_page')
                                      ->withQueryString();

              $detailedTransactions->getCollection()->transform(function ($transaction) {
                  if ($transaction->related_type === Bill::class && $transaction->related?->vendor) {
                      $transaction->vendor_name = $transaction->related->vendor->name;
                  } else {
                      $transaction->vendor_name = null;
                  }
                  return $transaction;
              });

               // --- ⭐ 2. جلب الفواتير التي تم إصدارها في الشهر المحدد (باستخدام paid_amount المباشر) ⭐ ---
               $billsIssuedInMonth = Bill::whereYear('issue_date', $yearMonth[0])
               ->whereMonth('issue_date', $yearMonth[1])
               ->with('vendor:id,name') // جلب المورد
               // --- ⭐ إعادة withSum لحساب المبلغ المدفوع ⭐ ---
               ->withSum(['payments as paid_amount' => function ($q) {
                   $q->where('type', 'expense'); // النوع الصحيح لدفع الفواتير
               }], 'amount')
               ->orderBy('issue_date', 'desc')
               // --- ⭐ إزالة paid_amount من select (لأنه ليس عموداً حقيقياً) ⭐ ---
               ->select(['id', 'vendor_id', 'bill_number', 'issue_date', 'due_date', 'total_amount', 'status'])
               ->paginate(20, ['*'], 'bills_page')
               ->withQueryString();

              // حساب المتبقي وتحديث الحالة للفواتير المعروضة
              $billsIssuedInMonth->getCollection()->transform(function ($bill) {
                  // استخدام paid_amount المباشر من الفاتورة
                  $calculated_paid_amount = $bill->paid_amount ?? 0;

                  // ⭐ سطر محذوف (كان مكرراً)
                  // $bill->paid_amount = $calculated_paid_amount;

                  $bill->remaining_amount = $bill->total_amount - $calculated_paid_amount;

                  // تحديث الحالة
                   $newStatus = 'paid'; // Default
                   if ($calculated_paid_amount <= 0 && $bill->total_amount > 0) { $newStatus = 'unpaid'; }
                   elseif ($calculated_paid_amount < $bill->total_amount && $calculated_paid_amount > 0) { $newStatus = 'partially_paid'; }
                   elseif ($calculated_paid_amount >= $bill->total_amount) { $newStatus = 'paid'; }

                   $bill->status = $newStatus; // تحديث الحالة
                  return $bill;
              });
               // --- نهاية جلب الفواتير ---
          }


          return response()->json([
              'monthly_summary' => $monthlyData,
              'details_for_month' => [
                 'transactions' => $detailedTransactions,
                 'bills_issued' => $billsIssuedInMonth,
              ],
              'selected_month' => $request->view_month,
          ]);
      }

    /**
     * جلب قائمة بجميع حركات الدخل (Income) مع Pagination.
     */
    public function getIncomeTransactions(Request $request)
    {
        $query = Transaction::where('type', 'income')
                            ->with('treasury:id,name') // جلب اسم الخزينة
                            ->orderBy('payment_date', 'desc')
                            ->orderBy('id', 'desc');

        $transactions = $query->paginate($request->input('per_page', 20))
                               ->withQueryString();

        // تحويل نوع الارتباط لاسم مقروء
        $transactions->getCollection()->transform(function ($transaction) {
            $transaction->source_type_display = $this->getTransactionSourceDisplay($transaction->related_type);
            return $transaction;
        });

        return response()->json($transactions);
    }

    /**
     * دالة مساعدة لتحويل related_type إلى نص مقروء
     */
    private function getTransactionSourceDisplay($relatedType)
    {
        if (!$relatedType) {
            return 'إيداع يدوي';
        }

        switch ($relatedType) {
            case 'App\\Models\\Invoice':
                return 'فاتورة قسط دراسي';
            case 'App\\Models\\KitchenBill':
                return 'مبيعات المقصف';
            case 'App\Models\ParentWallet':
                return 'شحن محفظة';
            case 'App\Models\Bill':
                return 'فاتورة مورد (مراجعة)';
            default:
                return 'مصدر آخر';
        }
    }

    /**
     * جلب ملخص تقرير الإيرادات (للداشبورد وصفحة الإيرادات)
     */
    public function getIncomeReportSummary(Request $request)
    {
        $now = Carbon::now();
        // تحديد بداية الأسبوع ونهايته (نفترض أن الأسبوع يبدأ السبت)
        $startOfWeek = $now->copy()->startOfWeek(Carbon::SATURDAY);
        $endOfWeek = $now->copy()->endOfWeek(Carbon::FRIDAY);

        // 1. إجمالي الدخل هذا الأسبوع
        $totalIncomeThisWeek = Transaction::where('type', 'income')
            ->whereBetween('payment_date', [$startOfWeek, $endOfWeek])
            ->sum('amount');

        // 2. إجمالي الدخل هذا الشهر
        $totalIncomeThisMonth = Transaction::where('type', 'income')
            ->whereYear('payment_date', $now->year)
            ->whereMonth('payment_date', $now->month)
            ->sum('amount');

        // 3. الدخل حسب الخزينة (الرصيد الحالي + الدخل الشهري)
        $treasuries = Treasury::select(['id', 'name', 'balance', 'type']) // جلب الرصيد الحالي
                   ->withSum(['transactions as month_income' => function ($query) use ($now) {
                       $query->where('type', 'income')
                             ->whereYear('payment_date', $now->year)
                             ->whereMonth('payment_date', $now->month);
                   }], 'amount')
                   ->get();

        // 4. الدخل حسب اليوم للأسبوع الحالي (للـ Chart)
        $days = [];
        for ($i = 0; $i < 7; $i++) {
            $day = $startOfWeek->copy()->addDays($i);
            $days[$day->format('Y-m-d')] = [
                'day_name' => $day->translatedFormat('D'), // 'سبت', 'أحد' إلخ.
                'total' => 0
            ];
        }

        $incomeByDayData = Transaction::where('type', 'income')
            ->whereBetween('payment_date', [$startOfWeek, $endOfWeek])
            ->select(DB::raw("DATE(payment_date) as date"), DB::raw('SUM(amount) as total'))
            ->groupBy('date')
            ->get()
            ->keyBy(fn($item) => Carbon::parse($item->date)->format('Y-m-d')); // Key by Y-m-d format

        foreach ($incomeByDayData as $date => $data) {
            if (isset($days[$date])) {
                $days[$date]['total'] = (float) $data->total;
            }
        }

        $income_by_day = array_values($days); // إرسال مصفوفة مرتبة

        return response()->json([
            'total_income_this_week' => $totalIncomeThisWeek,
            'total_income_this_month' => $totalIncomeThisMonth,
            'treasuries_summary' => $treasuries,
            'income_by_day_of_week' => $income_by_day,
        ]);
    }
}

