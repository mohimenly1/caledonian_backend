<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Meal extends Model
{
    use HasFactory;

    protected $fillable = [
        'category_id',
        'name',
        'price',
        'image'
    ];

   // Relations
   public function category()
   {
       return $this->belongsTo(MealCategory::class, 'category_id');
   }

   public function purchases()
   {
       return $this->hasMany(Purchase::class);
   }

   public function restrictedByStudents()
   {
       return $this->belongsToMany(Student::class, 'student_restricted_meals');
   }

}
