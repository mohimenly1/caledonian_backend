<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class StudentPurchaseCeiling extends Model
{
    use HasFactory;
    protected $table = 'student_purchase_ceiling';
    protected $fillable = [
        'student_id', 
        'purchase_ceiling'
    ];

    // Relations
    public function student()
    {
        return $this->belongsTo(Student::class, 'student_id');
    }
}
