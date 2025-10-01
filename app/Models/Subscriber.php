<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Subscriber extends Model
{
    use HasFactory;

    protected $fillable = [
        'student_name',
        'age',
        'school',
        'address',
        'zip',
        'parent_phone',
        'parent_email',
        'first_guardian_name',
        'first_guardian_phone',
        'second_guardian_name',
        'second_guardian_phone',
        'place_of_work',
        'emergency_contact',
        'pickup_person_1_name',
        'pickup_person_1_phone',
        'pickup_person_2_name',
        'pickup_person_2_phone',
        'subscription_fee', 
        'paid', 
        'remaining'
    ];
}
