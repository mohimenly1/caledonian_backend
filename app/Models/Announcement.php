<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Announcement extends Model
{
    use HasFactory;

    protected $fillable = [
        'title',
        'body',
        'image_path',
        'sent_by_user_id',
    ];

    public function sender()
    {
        return $this->belongsTo(User::class, 'sent_by_user_id');
    }
}
