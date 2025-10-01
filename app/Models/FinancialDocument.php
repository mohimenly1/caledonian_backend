<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class FinancialDocument extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        // 'student_id',
        'parent_id',
        'treasury_id',
        'total_amount',
        'receipt_number', // New column
        'discount',
        'final_amount', // total amount - discount
        'description',
        'value_received',
        'remaining_amount',  // New field
        'payment_method', // Added to store payment method type
        'bank_name',      // Added for financial instrument payment
        'branch_name',    // Added for financial instrument payment
        'account_number', // Added for financial instrument payment
    ];

    public function student()
    {
        return $this->belongsTo(Student::class);
    }


    public function parent()
    {
        return $this->belongsTo(ParentInfo::class, 'parent_id');
    }


    public function subscriptionFees()
    {
        return $this->belongsToMany(SubscriptionFee::class, 'financial_document_subscription_fee')
            ->withPivot('amount', 'student_id', 'subscription_fee_id') // Ensure 'subscription_fee_id' is included
            ->withTimestamps();
    }



    public function students()
    {
        return $this->belongsToMany(Student::class, 'financial_document_subscription_fee')
            ->withPivot('amount', 'student_id');
    }


    public function treasury()
    {
        return $this->belongsTo(Treasury::class);
    }


    protected static function boot()
    {
        parent::boot();

        // Trigger soft delete on related pivot records when a FinancialDocument is soft-deleted
        static::deleting(function ($financialDocument) {
            // Check if it is a soft delete
            if (!$financialDocument->isForceDeleting()) {
                // Soft delete related pivot records
                $financialDocument->subscriptionFees()->each(function ($subscriptionFee) use ($financialDocument) {
                    FinancialDocumentSubscriptionFee::where('financial_document_id', $financialDocument->id)
                        ->where('subscription_fee_id', $subscriptionFee->id)
                        ->update(['deleted_at' => now()]);
                });
            }
        });
    }
}
