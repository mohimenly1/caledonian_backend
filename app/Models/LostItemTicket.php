<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class LostItemTicket extends Model
{
    use HasFactory;
    protected $fillable = ['student_id', 'parent_user_id', 'subject', 'status'];

    public function student() { return $this->belongsTo(Student::class); }
    public function parent() { return $this->belongsTo(User::class, 'parent_user_id'); }
    public function messages() { return $this->hasMany(LostItemMessage::class, 'ticket_id'); }
}
