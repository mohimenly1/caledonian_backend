<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Transaction extends Model
{
    use HasFactory;

    protected $fillable = [
        'treasury_id', 
        'payment_date', 
        'amount', 
        'type', 
        'payment_method', 
        'description', 
        'related_id', 
        'related_type'
    ];

    protected $casts = [
        'payment_date' => 'date',
        'amount' => 'decimal:2',
    ];

    public function treasury()
    {
        return $this->belongsTo(Treasury::class);
    }

    /**
     * Get the parent model (invoice or bill).
     */
    public function related()
    {
        return $this->morphTo();
    }
}
