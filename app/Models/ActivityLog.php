<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ActivityLog extends Model
{
    use HasFactory;
    protected $fillable = ['user_id', 'action', 'description', 'old_data', 'new_data'];
    public $timestamps = false; // Disable automatic timestamps
    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
