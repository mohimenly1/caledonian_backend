<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

class KitchenBill extends Model
{
    use HasFactory;
    protected $fillable = ['user_id', 'buyer_type', 'buyer_id', 'payment_method', 'category_id', 'bill_number'];

    use LogsActivity;

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly($this->fillable);
        // Chain fluent methods for configuration options
    }



    public function items()
    {
        return $this->hasMany(KitchenBillItem::class);
    }

    public function employee()
    {
        return $this->belongsTo(employee::class, 'buyer_id');
    }
    public function student()
    {
        return $this->belongsTo(Student::class, 'buyer_id');
    }

    public function MealCategory()
    {
        return $this->belongsTo(MealCategory::class, 'category_id');
    }
}
