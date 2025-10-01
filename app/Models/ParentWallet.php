<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\ActivityLog;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

class ParentWallet extends Model
{
    use HasFactory;
    protected $table = 'parent_wallets'; // Specify the table name

    protected $fillable = [
        'parent_id',
        'user_id',
        'balance',
    ];
    use LogsActivity;
    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly($this->fillable);
        // Chain fluent methods for configuration options
    }

    // Define relationships
    public function parent()
    {
        return $this->belongsTo(ParentInfo::class, 'parent_id'); // Adjust if necessary
    }

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id'); // Adjust if necessary
    }
    public function transactions()
    {
        return $this->morphMany(Transaction::class, 'related');
    }
}
