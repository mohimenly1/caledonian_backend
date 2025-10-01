<?php
// app/Models/Bus.php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Bus extends Model
{
    use HasFactory;
    protected $fillable = ['bus_number', 'plate_number', 'driver_id'];

    public function driver()
    {
        return $this->belongsTo(User::class, 'driver_id');
    }

    public function students()
    {
        return $this->belongsToMany(Student::class, 'bus_student');
    }

    public function locations()
    {
        return $this->hasMany(BusLocation::class);
    }

    public function latestLocation()
    {
        return $this->hasOne(BusLocation::class)->latestOfMany();
    }
}
