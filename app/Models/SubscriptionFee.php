<?php

// app/Models/SubscriptionFee.php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SubscriptionFee extends Model
{
    use HasFactory;

    protected $fillable = ['category', 'sub_category', 'amount'];

    public function students()
    {
        return $this->belongsToMany(Student::class, 'financial_document_subscription_fee')
                    ->withPivot('student_id', 'amount')
                    ->withTimestamps();
    }

    // public function student()
    // {
    //     return $this->belongsTo(Student::class);
    // }
    public function financialDocuments()
    {
        return $this->belongsToMany(FinancialDocument::class, 'financial_document_subscription_fee')
            ->withPivot('amount', 'student_id'); // Ensure you also include student_id here
    }
}
