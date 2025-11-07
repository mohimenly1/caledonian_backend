<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class ParentInfo extends Model
{
    protected $table = 'parents';
    use HasFactory,SoftDeletes;


    protected $fillable = [
        'user_id',
        'first_name',
        'last_name',
        'phone_number_one',
        'phone_number_two',
        'city',
        'address',
        'id_image',
        'passport_image',
        'images_info',
        'national_number', // Add this line
        'passport_num',
        'email',
        'pin_code', // Add this if not already present
        'note', // Add this line
        'discount', // Add this line
    ];


        // ⭐ إضافة accessor للحصول على الاسم الكامل
        public function getNameAttribute()
        {
            return trim($this->first_name . ' ' . $this->last_name);
        }
    
        // ⭐ إضافة هذا السطر لجعل الاسم متاحاً في الاستعلامات
        protected $appends = ['name'];
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function students()
    {
        return $this->hasMany(Student::class, 'parent_id');
    }
    public function wallet()
    {
        return $this->hasOne(ParentWallet::class, 'parent_id');
    }

    public function purchases()
    {
        return $this->hasMany(Purchase::class, 'parent_id');
    }

public function financialDocuments()
{
    return $this->hasMany(FinancialDocument::class, 'parent_id')
                ->with('subscriptionFees'); // Include subscription fees with each financial document
}

public function studentRelationships()
{
    return $this->hasMany(StudentParentRelationship::class, 'parent_id');
}

public function invoices()
{
    return $this->hasMany(Invoice::class, 'parent_id');
}

// public function students()
// {
//     return $this->belongsToMany(Student::class, 'student_parent_relationships', 'parent_id', 'student_id')
//                 ->withPivot('relationship_type')
//                 ->withTimestamps();
// }

}
