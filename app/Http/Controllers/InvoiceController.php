<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\Invoice;
use App\Models\InvoiceItem;
use App\Models\Student;
use App\Models\ParentInfo;
use App\Models\FeeStructure;
use App\Models\SiblingDiscount;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use App\Models\Transaction;
use App\Models\Treasury;

class InvoiceController extends Controller
{
    /**
     * Display a listing of the invoices with search and pagination.
     */
    public function index(Request $request)
    {
        $query = Invoice::with('parent:id,first_name,last_name')
                        ->withSum('payments as paid_amount', 'amount') // Calculate sum of payments
                        ->orderBy('issue_date', 'desc');

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where('invoice_number', 'like', "%{$search}%")
                  ->orWhereHas('parent', function ($q) use ($search) {
                      $q->where('first_name', 'like', "%{$search}%")
                        ->orWhere('last_name', 'like', "%{$search}%");
                  });
        }

        $invoices = $query->paginate($request->input('per_page', 15));

        // Add remaining_amount to each invoice
        $invoices->getCollection()->transform(function ($invoice) {
            $invoice->paid_amount = $invoice->paid_amount ?? 0;
            $invoice->remaining_amount = $invoice->final_amount - $invoice->paid_amount;
            return $invoice;
        });

        return response()->json($invoices);
    }

    /**
     * Store a newly created invoice in storage.
     */
    public function store(Request $request)
    {
        $validatedData = $request->validate([
            'parent_id' => 'required|exists:parents,id',
            'study_year_id' => 'required|exists:study_years,id',
            'issue_date' => 'required|date',
            'due_date' => 'required|date|after_or_equal:issue_date',
            'students' => 'required|array|min:1',
            'students.*.fee_structure_ids' => 'required|array|min:1',
            'students.*.fee_structure_ids.*' => 'required|exists:fee_structures,id',
            'apply_sibling_discount' => 'sometimes|boolean',
            'manual_discount' => 'nullable|numeric|min:0',
            'treasury_id' => 'required|exists:treasuries,id',
            'amount_paid' => 'required|numeric|min:0',
        ]);

        $parent = ParentInfo::findOrFail($validatedData['parent_id']);
        $studyYearId = $validatedData['study_year_id'];
        $totalAmount = 0;
        $invoiceItemsData = [];

        // --- 1. حساب المبلغ الإجمالي وتجهيز بنود الفاتورة ---
        foreach ($validatedData['students'] as $studentId => $studentData) {
            foreach ($studentData['fee_structure_ids'] as $feeStructureId) {
                $feeStructure = FeeStructure::with('feeType')->find($feeStructureId);
                if ($feeStructure) {
                    $totalAmount += $feeStructure->amount;
                    $invoiceItemsData[] = [
                        'student_id' => $studentId,
                        'fee_structure_id' => $feeStructureId,
                        'description' => $feeStructure->feeType->name,
                        'amount' => $feeStructure->amount,
                    ];
                }
            }
        }

        // --- 2. منطق الخصم المحدث ---
        $discount = 0;
        if ($request->filled('manual_discount') && $validatedData['manual_discount'] > 0) {
            $discount = $validatedData['manual_discount'];
        }
        elseif (($validatedData['apply_sibling_discount'] ?? false) && count($validatedData['students']) > 1) {
            $discountPolicy = SiblingDiscount::where('study_year_id', $studyYearId)
                                             ->where('number_of_siblings', count($validatedData['students']))
                                             ->first();

            if ($discountPolicy) {
                $discount = ($totalAmount * $discountPolicy->discount_percentage) / 100;
            }
        }

        $finalAmount = $totalAmount - $discount;

        if ($validatedData['amount_paid'] > $finalAmount) {
            return response()->json(['message' => 'The amount paid cannot be greater than the final amount.'], 422);
        }

        try {
            DB::beginTransaction();

            // --- 3. إنشاء سجل الفاتورة الرئيسي ---
            $invoice = Invoice::create([
                'parent_id' => $parent->id,
                'study_year_id' => $studyYearId,
                'invoice_number' => 'INV-' . time() . '-' . $parent->id,
                'issue_date' => $validatedData['issue_date'],
                'due_date' => $validatedData['due_date'],
                'total_amount' => $totalAmount,
                'discount' => $discount,
                'final_amount' => $finalAmount,
                'status' => 'unpaid', // الحالة الأولية
            ]);

            // --- 4. إنشاء بنود الفاتورة التفصيلية ---
            foreach ($invoiceItemsData as $itemData) {
                $invoice->items()->create($itemData);
            }

            // --- 5. معالجة الدفعة الأولية (إن وجدت) ---
            if ($validatedData['amount_paid'] > 0) {
                $treasury = Treasury::findOrFail($validatedData['treasury_id']);

                // إنشاء حركة مالية
                $invoice->payments()->create([
                    'treasury_id' => $treasury->id,
                    'payment_date' => $validatedData['issue_date'],
                    'amount' => $validatedData['amount_paid'],
                    'type' => 'income',
                    'payment_method' => 'cash', // Or get from request
                    'description' => 'Initial payment for invoice #' . $invoice->invoice_number,
                ]);

                // تحديث رصيد الخزينة
                $treasury->balance += $validatedData['amount_paid'];
                $treasury->save();

                // تحديث حالة الفاتورة
                $invoiceStatus = 'unpaid';
                if ($validatedData['amount_paid'] >= $finalAmount) {
                    $invoiceStatus = 'paid';
                } elseif ($validatedData['amount_paid'] > 0) {
                    $invoiceStatus = 'partially_paid';
                }
                $invoice->update(['status' => $invoiceStatus]);
            }

            DB::commit();

            return response()->json([
                'message' => 'Invoice created successfully.',
                'invoice' => $invoice->load('items.student', 'items.feeStructure.feeType'),
            ], 201);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['message' => 'An error occurred while creating the invoice.', 'error' => $e->getMessage()], 500);
        }
    }
    /**
     * Display the specified invoice.
     */
    public function show(Invoice $invoice)
    {
        $invoice->load([
            'parent',
            'items.student:id,name',
            'items.feeStructure.feeType:id,name',
            'payments.treasury:id,name'
        ]);

        // حساب المبلغ المدفوع والمتبقي
        $paidAmount = $invoice->payments()
            ->where('type', 'income')
            ->sum('amount');

        $remainingAmount = $invoice->final_amount - $paidAmount;

        // تحديد الحالة بناءً على المبلغ المدفوع
        $invoiceStatus = 'unpaid';
        if ($paidAmount >= $invoice->final_amount && $invoice->final_amount > 0) {
            $invoiceStatus = 'paid';
        } elseif ($paidAmount > 0 && $paidAmount < $invoice->final_amount) {
            $invoiceStatus = 'partially_paid';
        } elseif ($paidAmount <= 0 && $invoice->final_amount > 0) {
            $invoiceStatus = 'unpaid';
        } else {
            $invoiceStatus = 'paid';
        }

        // إضافة الحقول المحسوبة إلى الاستجابة
        $invoiceArray = $invoice->toArray();
        $invoiceArray['paid_amount'] = $paidAmount;
        $invoiceArray['remaining_amount'] = $remainingAmount;
        $invoiceArray['status'] = $invoiceStatus;
        $invoiceArray['payments'] = $invoice->payments->map(function ($payment) {
            return [
                'id' => $payment->id,
                'amount' => $payment->amount,
                'payment_date' => $payment->payment_date->format('Y-m-d'),
                'treasury_name' => $payment->treasury->name ?? null,
                'description' => $payment->description,
            ];
        });

        return response()->json($invoiceArray);
    }


    /**
     * Remove the specified invoice from storage.
     */
    public function destroy(Invoice $invoice)
    {
        try {
            // التحقق من وجود مدفوعات من نوع income فقط
            $hasPayments = $invoice->payments()
                ->where('type', 'income')
                ->exists();

            if ($hasPayments) {
                return response()->json([
                    'message' => 'لا يمكن حذف الفاتورة التي تحتوي على مدفوعات.',
                    'error' => 'invoice_has_payments'
                ], 422);
            }

            // حفظ معلومات الفاتورة قبل الحذف
            $invoiceId = $invoice->id;
            $invoiceNumber = $invoice->invoice_number;

            // حذف بنود الفاتورة أولاً (إذا كانت موجودة)
            if ($invoice->items()->exists()) {
                $invoice->items()->delete();
            }

            // حذف الفاتورة
            $invoice->delete();

            return response()->json([
                'message' => 'تم حذف الفاتورة بنجاح.',
                'invoice_id' => $invoiceId,
                'invoice_number' => $invoiceNumber,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'حدث خطأ أثناء حذف الفاتورة.',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * ⭐⭐ إضافة دفعة جديدة للفاتورة ⭐⭐
     * POST /api/invoices/{invoice}/payments
     */
    public function addPayment(Request $request, Invoice $invoice)
    {
        $validatedData = $request->validate([
            'treasury_id' => 'required|exists:treasuries,id',
            'amount' => 'required|numeric|min:0.01',
            'payment_date' => 'required|date',
            'payment_method' => 'nullable|string|in:cash,check,bank_transfer',
            'description' => 'nullable|string',
        ]);

        $treasury = Treasury::findOrFail($validatedData['treasury_id']);

        // حساب المبلغ المدفوع حالياً
        $totalPaid = $invoice->payments()
            ->where('type', 'income')
            ->sum('amount');
        $remainingAmount = $invoice->final_amount - $totalPaid;

        // التحقق من أن المبلغ لا يتجاوز المتبقي
        if ($validatedData['amount'] > $remainingAmount) {
            return response()->json([
                'message' => 'المبلغ المدفوع لا يمكن أن يتجاوز المبلغ المتبقي.',
                'remaining_amount' => $remainingAmount
            ], 422);
        }

        try {
            DB::beginTransaction();

            // 1. إنشاء حركة مالية جديدة
            $invoice->payments()->create([
                'treasury_id' => $treasury->id,
                'payment_date' => $validatedData['payment_date'],
                'amount' => $validatedData['amount'],
                'type' => 'income',
                'payment_method' => $validatedData['payment_method'] ?? 'cash',
                'description' => $validatedData['description'] ?? 'دفعة إضافية للفاتورة #' . $invoice->invoice_number,
            ]);

            // 2. تحديث رصيد الخزينة
            $treasury->balance += $validatedData['amount'];
            $treasury->save();

            // 3. تحديث حالة الفاتورة
            $newTotalPaid = $totalPaid + $validatedData['amount'];
            $invoiceStatus = 'unpaid';
            if ($newTotalPaid >= $invoice->final_amount) {
                $invoiceStatus = 'paid';
            } elseif ($newTotalPaid > 0) {
                $invoiceStatus = 'partially_paid';
            }
            // تحديث status مباشرة
            $invoice->fill(['status' => $invoiceStatus]);
            $invoice->save();

            DB::commit();

            // إعادة تحميل الفاتورة مع العلاقات
            $invoice->load([
                'items.student:id,name',
                'items.feeStructure.feeType:id,name',
                'payments.treasury:id,name'
            ]);

            // حساب المبلغ المدفوع مرة أخرى بعد الحفظ
            $paidAmount = $invoice->payments()
                ->where('type', 'income')
                ->sum('amount');

            // إرجاع بيانات الفاتورة المحدثة مع العلاقات
            $invoiceArray = $invoice->toArray();
            $invoiceArray['paid_amount'] = $paidAmount;
            $invoiceArray['remaining_amount'] = $invoice->final_amount - $paidAmount;
            $invoiceArray['status'] = $invoiceStatus;
            $invoiceArray['payments'] = $invoice->payments->map(function ($payment) {
                return [
                    'id' => $payment->id,
                    'amount' => $payment->amount,
                    'payment_date' => $payment->payment_date->format('Y-m-d'),
                    'treasury_name' => $payment->treasury->name ?? null,
                    'description' => $payment->description,
                ];
            });

            return response()->json([
                'message' => 'تم تسجيل الدفعة بنجاح.',
                'invoice' => $invoiceArray,
                'new_treasury_balance' => $treasury->balance,
            ], 201);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'message' => 'حدث خطأ أثناء تسجيل الدفعة.',
                'error' => $e->getMessage()
            ], 500);
        }
    }

      /**
     * --- الدالة الجديدة هنا ---
     * جلب كل البيانات اللازمة لإنشاء فاتورة جديدة لولي أمر معين.
     * GET /api/invoices/data-for-parent/{parent}
     */
    public function getDataForParent(ParentInfo $parent)
    {
        $parent->load(['students.class', 'students.studyYear', 'students.section']);

        $students = $parent->students;
        if ($students->isEmpty()) {
            return response()->json(['message' => 'This parent has no students enrolled.'], 404);
        }

        // 1. Get all UNIQUE study year IDs from all of the parent's children
        $studyYearIds = $students->pluck('study_year_id')->filter()->unique();

        // 2. Fetch all fee structures for all relevant study years
        $feeStructures = FeeStructure::with('feeType:id,name')
            ->whereIn('study_year_id', $studyYearIds)
            ->get();

        // 3. Fetch sibling discount policy (based on the first student's year as a default)
        $numberOfSiblings = $students->count();
        $firstStudyYearId = $students->first()->study_year_id;
        $discountPolicy = null;
        if ($firstStudyYearId) {
            $discountPolicy = SiblingDiscount::where('study_year_id', $firstStudyYearId)
                                             ->where('number_of_siblings', $numberOfSiblings)
                                             ->first();
        }

        // 4. ⭐⭐ جلب الفواتير الحالية لولي الأمر ⭐⭐
        $invoices = Invoice::where('parent_id', $parent->id)
            ->with([
                'items.student:id,name',
                'items.feeStructure.feeType:id,name',
                'payments.treasury:id,name'
            ])
            ->withSum(['payments as paid_amount' => function ($query) {
                $query->where('type', 'income');
            }], 'amount')
            ->orderBy('issue_date', 'desc')
            ->get()
            ->map(function ($invoice) {
                $paidAmount = $invoice->paid_amount ?? 0;
                $remainingAmount = $invoice->final_amount - $paidAmount;

                // تحديد الحالة بناءً على المبلغ المدفوع
                $invoiceStatus = 'unpaid';
                if ($paidAmount >= $invoice->final_amount && $invoice->final_amount > 0) {
                    $invoiceStatus = 'paid';
                } elseif ($paidAmount > 0 && $paidAmount < $invoice->final_amount) {
                    $invoiceStatus = 'partially_paid';
                } elseif ($paidAmount <= 0 && $invoice->final_amount > 0) {
                    $invoiceStatus = 'unpaid';
                } else {
                    $invoiceStatus = 'paid'; // للفواتير ذات القيمة الصفرية
                }

                return [
                    'id' => $invoice->id,
                    'invoice_number' => $invoice->invoice_number,
                    'issue_date' => $invoice->issue_date->format('Y-m-d'),
                    'due_date' => $invoice->due_date->format('Y-m-d'),
                    'total_amount' => $invoice->total_amount,
                    'discount' => $invoice->discount,
                    'final_amount' => $invoice->final_amount,
                    'paid_amount' => $paidAmount,
                    'remaining_amount' => $remainingAmount,
                    'status' => $invoiceStatus,
                    'study_year_id' => $invoice->study_year_id,
                    'items' => $invoice->items->map(function ($item) {
                        return [
                            'id' => $item->id,
                            'student_id' => $item->student_id,
                            'student_name' => $item->student->name ?? null,
                            'fee_structure_id' => $item->fee_structure_id,
                            'fee_type_name' => $item->feeStructure->feeType->name ?? null,
                            'description' => $item->description,
                            'amount' => $item->amount,
                        ];
                    }),
                    'payments' => $invoice->payments->map(function ($payment) {
                        return [
                            'id' => $payment->id,
                            'amount' => $payment->amount,
                            'payment_date' => $payment->payment_date->format('Y-m-d'),
                            'treasury_name' => $payment->treasury->name ?? null,
                            'description' => $payment->description,
                        ];
                    }),
                ];
            });

        return response()->json([
            'parent' => $parent,
            'students' => $students,
            'feeStructures' => $feeStructures, // Now contains fees for ALL children
            'siblingDiscountPolicy' => $discountPolicy,
            'invoices' => $invoices, // ⭐⭐ الفواتير الحالية ⭐⭐
        ]);
    }
}
