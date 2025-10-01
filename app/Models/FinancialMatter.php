<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class FinancialMatter extends Model
{
    use HasFactory;
    protected $fillable = [
        'user_id',
        'amount',
        'type',
        'description',
    ];

        /**
     * The user that owns the financial matter.
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }
    

    const TYPES = [
        'registration_fee',
        'exam_fee',
        'monthly_fee',
        'transportation_fee',
        'other',
    ];
}
