<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class LostItemMessage extends Model
{
    use HasFactory;
    protected $fillable = ['ticket_id', 'user_id', 'message', 'attachment_path'];

    public function ticket() { return $this->belongsTo(LostItemTicket::class, 'ticket_id'); }
    public function sender() { return $this->belongsTo(User::class, 'user_id'); }
}
