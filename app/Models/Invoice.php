<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Invoice extends Model
{
    use HasFactory;

    protected $fillable = [
        'parent_id', 'study_year_id', 'invoice_number', 'issue_date', 
        'due_date', 'total_amount', 'discount', 'final_amount', 'status'
    ];

    protected $casts = [
        'issue_date' => 'date',
        'due_date' => 'date',
        'total_amount' => 'decimal:2',
        'discount' => 'decimal:2',
        'final_amount' => 'decimal:2',
    ];

    public function parent()
    {
        return $this->belongsTo(ParentInfo::class, 'parent_id');
    }

    public function items()
    {
        return $this->hasMany(InvoiceItem::class);
    }

    public function invoiceItems()
    {
        return $this->hasMany(InvoiceItem::class, 'invoice_id');
    }

    public function payments()
    {
        return $this->morphMany(Transaction::class, 'related');
    }

    // ⭐ دالة جديدة لحساب المبلغ المدفوع
    public function getPaidAmountAttribute()
    {
        return $this->payments()
            ->where('type', 'income')
            ->sum('amount');
    }

    // ⭐ دالة جديدة لحساب المبلغ المتبقي
    public function getRemainingAmountAttribute()
    {
        return $this->final_amount - $this->paid_amount;
    }

    // ⭐ دالة جديدة لتحديد الحالة
    public function getStatusAttribute($value)
    {
        // إذا كانت هناك قيمة مخزنة، نستخدمها
        if ($value) {
            return $value;
        }
        
        // وإلا نحسب الحالة بناءً على المدفوعات
        $paidAmount = $this->paid_amount;
        $finalAmount = $this->final_amount;
        
        if ($finalAmount == 0) {
            return 'paid';
        } elseif ($paidAmount <= 0) {
            return 'unpaid';
        } elseif ($paidAmount >= $finalAmount) {
            return 'paid';
        } else {
            return 'partially_paid';
        }
    }
}
