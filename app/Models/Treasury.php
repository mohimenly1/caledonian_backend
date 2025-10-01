<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
class Treasury extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'type', // 'bank' or 'manual'
        'bank_name',
        'account_number',
        'routing_number',
        'balance'
    ];

       /**
     * --- العلاقة الجديدة المقترحة ---
     * تعريف علاقة الخزينة بالحركات المالية.
     */
    public function transactions()
    {
        return $this->hasMany(Transaction::class);
    }

    // public function transactions()
    // {
    //     return $this->hasMany(TreasuryTransaction::class);
    // }
}
