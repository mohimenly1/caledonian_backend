<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\ActivityLog;

class EmployeeWallet extends Model
{
    use HasFactory;





    protected $table = 'employee_wallets'; // Specify the table name

    protected $fillable = [
        'employee_id',
        'user_id',
        'balance',
    ];

    // Define relationships
    public function employee()
    {
        return $this->belongsTo(Employee::class, 'employee_id'); // Adjust if necessary
    }

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id'); // Adjust if necessary
    }
}
