<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class FinancialDocumentSubscriptionFee extends Model
{
    use HasFactory,SoftDeletes;
    protected $table = 'financial_document_subscription_fee';

    protected $fillable = [
        'financial_document_id',
        'subscription_fee_id',
        'student_id',
        'amount',
    ];

    public function financialDocument()
    {
        return $this->belongsTo(FinancialDocument::class);
    }

    public function subscriptionFee()
    {
        return $this->belongsTo(SubscriptionFee::class);
    }

    public function student()
    {
        return $this->belongsTo(Student::class);
    }
}
