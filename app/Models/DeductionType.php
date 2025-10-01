<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class DeductionType extends Model
{
    use HasFactory;

    protected $fillable = ['name'];

    public function deductions()
    {
        return $this->hasMany(Deduction::class);
    }
}
