<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

class KitchenBillItem extends Model
{
    use HasFactory;

    protected $fillable = ['buyer_id', 'piece_price', 'kitchen_bill_id', 'meal_id', 'quantity', 'meal_price'];
    use LogsActivity;
    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly($this->fillable);
        // Chain fluent methods for configuration options
    }
    public function kitchenBill()
    {
        return $this->belongsTo(KitchenBill::class);
    }

    public function meal()
    {
        return $this->belongsTo(Meal::class);
    }
}
