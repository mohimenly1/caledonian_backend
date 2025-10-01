<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
class PermissionEmployee extends Model
{
    use HasFactory,SoftDeletes;

    protected $table = 'permissions_employees';
    protected $fillable = [
        'employee_id', 'permission_type', 'request_date', 'approval_status', 
        'start_date', 'end_date', 'reason', 'remarks', 'approved_by',
        'file_attachment', 'notified_at', 'decision_date'
    ];

    public function employee()
    {
        return $this->belongsTo(Employee::class);
    }

    public function approvedByUser()
    {
        return $this->belongsTo(User::class, 'approved_by');
    }
}
