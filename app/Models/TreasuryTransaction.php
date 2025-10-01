<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TreasuryTransaction extends Model
{
    use HasFactory;

    protected $fillable = [
        'treasury_id',
        'transaction_type', // 'deposit' or 'disbursement'
        'amount',
        'description',
        'related_id', // Can be used to reference the employee salary or student subscription
        'related_type', // 'salary' or 'subscription_fee'
    ];

    public function treasury()
    {
        return $this->belongsTo(Treasury::class);
    }
}