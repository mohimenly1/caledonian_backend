<?php

namespace App\Http\Controllers;
use App\Models\Bill;
use Illuminate\Support\Facades\DB;

use Illuminate\Http\Request;

class BillController extends Controller
{
    public function index(Request $request)
    {
        $query = Bill::with('vendor:id,name')
                     ->withSum('payments as paid_amount', 'amount')
                     ->orderBy('issue_date', 'desc');

        $bills = $query->paginate($request->input('per_page', 15));

        $bills->getCollection()->transform(function ($bill) {
            $paidAmount = $bill->paid_amount ?? 0;
            $bill->paid_amount = $paidAmount;
            $bill->remaining_amount = $bill->total_amount - $paidAmount;
            
            // ✅ حساب status بناءً على paid_amount و total_amount
            $currentStatus = $bill->status;
            if ($paidAmount == 0) {
                $newStatus = 'unpaid';
            } elseif ($paidAmount >= $bill->total_amount) {
                $newStatus = 'paid';
            } else {
                $newStatus = 'partially_paid';
            }
            
            // ✅ تحديث status في قاعدة البيانات إذا تغير
            if ($currentStatus != $newStatus) {
                $bill->update(['status' => $newStatus]);
                $bill->refresh();
            }
            $bill->status = $newStatus;
            
            return $bill;
        });

        return response()->json($bills);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'vendor_id' => 'required|exists:vendors,id',
            'bill_number' => 'nullable|string|max:255',
            'issue_date' => 'required|date',
            'due_date' => 'nullable|date|after_or_equal:issue_date',
            'items' => 'required|array|min:1',
            'items.*.expense_account_id' => 'required|exists:accounts,id',
            'items.*.description' => 'required|string|max:255',
            'items.*.amount' => 'required|numeric|min:0.01',
        ]);

        $totalAmount = collect($validated['items'])->sum('amount');

        try {
            DB::beginTransaction();

            $bill = Bill::create([
                'vendor_id' => $validated['vendor_id'],
                'bill_number' => $validated['bill_number'],
                'issue_date' => $validated['issue_date'],
                'due_date' => $validated['due_date'],
                'total_amount' => $totalAmount,
                'status' => 'unpaid',
            ]);

            $bill->items()->createMany($validated['items']);

            DB::commit();
            return response()->json($bill->load('items'), 201);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['message' => 'Failed to create bill.', 'error' => $e->getMessage()], 500);
        }
    }

    public function show(Bill $bill)
    {
        $bill->load(['vendor', 'items.expenseAccount', 'payments.treasury']);
        $bill->loadSum('payments as paid_amount', 'amount');
        $paidAmount = $bill->paid_amount ?? 0;
        $bill->paid_amount = $paidAmount;
        $bill->remaining_amount = $bill->total_amount - $paidAmount;
        
        // ✅ حساب status بناءً على paid_amount و total_amount
        if ($paidAmount == 0) {
            $calculatedStatus = 'unpaid';
        } elseif ($paidAmount >= $bill->total_amount) {
            $calculatedStatus = 'paid';
        } else {
            $calculatedStatus = 'partially_paid';
        }
        
        // ✅ تحديث status في قاعدة البيانات إذا تغير
        if ($bill->status != $calculatedStatus) {
            try {
                $bill->update(['status' => $calculatedStatus]);
                $bill->refresh();
            } catch (\Exception $e) {
                // ✅ في حالة الخطأ، فقط نستخدم القيمة المحسوبة للعرض
                $bill->status = $calculatedStatus;
            }
        } else {
            $bill->status = $calculatedStatus;
        }
        
        return response()->json($bill);
    }

    public function update(Request $request, Bill $bill)
    {
        // ✅ تم تفعيل التعديل في جميع الحالات (حتى مع وجود مدفوعات)
        // ✅ السماح للمسؤول في نظام edura بتعديل جميع الفواتير
        
        $validated = $request->validate([
            'vendor_id' => 'required|exists:vendors,id',
            'bill_number' => 'nullable|string|max:255',
            'issue_date' => 'required|date',
            'due_date' => 'nullable|date|after_or_equal:issue_date',
            'items' => 'required|array|min:1',
            'items.*.expense_account_id' => 'required|exists:accounts,id',
            'items.*.description' => 'required|string|max:255',
            'items.*.amount' => 'required|numeric|min:0.01',
        ]);

        $totalAmount = collect($validated['items'])->sum('amount');

        try {
            DB::beginTransaction();

            // ✅ الحفاظ على paid_amount الحالي (من المدفوعات الموجودة)
            $bill->loadSum('payments as paid_amount', 'amount');
            $paidAmount = $bill->paid_amount ?? 0;
            
            // ✅ حساب status بناءً على paid_amount و total_amount الجديد
            if ($paidAmount == 0) {
                $status = 'unpaid';
            } elseif ($paidAmount >= $totalAmount) {
                $status = 'paid';
            } else {
                $status = 'partially_paid';
            }

            // Update bill مع status
            $bill->update([
                'vendor_id' => $validated['vendor_id'],
                'bill_number' => $validated['bill_number'],
                'issue_date' => $validated['issue_date'],
                'due_date' => $validated['due_date'],
                'total_amount' => $totalAmount,
                'status' => $status, // ✅ تحديث status ضمن نفس الـ transaction
            ]);

            // Delete existing items and create new ones
            $bill->items()->delete();
            $bill->items()->createMany($validated['items']);

            DB::commit();
            
            // ✅ إعادة تحميل الفاتورة مع العلاقات والحسابات
            $bill->refresh();
            $bill->load(['vendor', 'items.expenseAccount', 'payments.treasury']);
            $bill->loadSum('payments as paid_amount', 'amount');
            $paidAmount = $bill->paid_amount ?? 0;
            $bill->paid_amount = $paidAmount;
            $bill->remaining_amount = $bill->total_amount - $paidAmount;
            
            // ✅ التأكد من أن status محدث
            $bill->status = $status;
            
            return response()->json($bill);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['message' => 'Failed to update bill.', 'error' => $e->getMessage()], 500);
        }
    }

    public function update(Request $request, Bill $bill)
    {
        $validated = $request->validate([
            'vendor_id' => 'required|exists:vendors,id',
            'bill_number' => 'nullable|string|max:255',
            'issue_date' => 'required|date',
            'due_date' => 'nullable|date|after_or_equal:issue_date',
            'items' => 'required|array|min:1',
            'items.*.expense_account_id' => 'required|exists:accounts,id',
            'items.*.description' => 'required|string|max:255',
            'items.*.amount' => 'required|numeric|min:0.01',
        ]);

        $totalAmount = collect($validated['items'])->sum('amount');

        try {
            DB::beginTransaction();

            // Update bill
            $bill->update([
                'vendor_id' => $validated['vendor_id'],
                'bill_number' => $validated['bill_number'],
                'issue_date' => $validated['issue_date'],
                'due_date' => $validated['due_date'],
                'total_amount' => $totalAmount,
            ]);

            // Delete existing items and create new ones
            $bill->items()->delete();
            $bill->items()->createMany($validated['items']);

            DB::commit();
            return response()->json($bill->load(['vendor', 'items.expenseAccount', 'payments.treasury']));
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['message' => 'Failed to update bill.', 'error' => $e->getMessage()], 500);
        }
    }

    public function destroy(Bill $bill)
    {
        // ✅ تم تفعيل الحذف في جميع الحالات (حتى مع وجود مدفوعات)
        // ✅ السماح للمسؤول في نظام edura بحذف جميع الفواتير
        
        $bill->delete();
        return response()->json(['message' => 'Bill deleted successfully.']);
    }
}
