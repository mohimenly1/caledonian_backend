<?php

namespace App\Models;

use Carbon\Carbon;
use Clue\Redis\Protocol\Model\Request;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Student extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'name',
        'user_id',
        'parent_id',
        'class_id',
        'arabic_name',
        'bus_id',
        'date_of_birth',
        'section_id', // Add this line
        'address',
        'national_number', // Add this line
        'year',
        'passport_num',
        'has_books',
        'daily_allowed_purchase_value',
        'gender', // Add this line for gender
        'photo',
        'srn',
        'study_year_id', // Add this line
        'note', // Add this line
        'subscriptions', // Add this line
        'missing', // Add this line
    ];

    

    public function student_restricted_meal()
    {
        return $this->hasMany(StudentRestrictedMeal::class);
    }

    public function student_attendance_records()
    {

        return $this->hasMany(StudentAttendanceRecord::class);
    }
    public function kitchen_bills()
    {
        return $this->hasMany(KitchenBill::class, 'buyer_id', 'id')
            ->where('buyer_type', 'student');
    }

    public function kitchen_bills_for_today()
    {
        return $this->hasMany(KitchenBill::class, 'buyer_id', 'id')
            ->where('created_at', '>=', Carbon::today())
            ->where('buyer_type', 'student');
    }
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    // Define relation to financial documents
    public function financialDocuments()
    {
        return $this->belongsToMany(FinancialDocument::class, 'financial_document_subscription_fee')
            ->withPivot('amount', 'student_id');
    }

    public function parent()
    {
        return $this->belongsTo(ParentInfo::class, 'parent_id');
       
    }

    public function class()
    {
        return $this->belongsTo(ClassRoom::class, 'class_id');
    }

    public function bus()
    {
        // A student can only be on one bus at a time, so we use hasOne through a pivot.
        return $this->belongsToMany(Bus::class, 'bus_student')->limit(1);
    }

      // You can also define a boolean accessor for convenience
      public function getIsAssignedToBusAttribute()
      {
          return $this->bus()->exists();
      }
    public function subscriptionFee()
    {
        return $this->belongsTo(SubscriptionFee::class);
    }

    public function subscriptionFees()
    {
        return $this->belongsToMany(SubscriptionFee::class, 'financial_document_subscription_fee')
            ->withPivot('student_id', 'amount')
            ->withTimestamps();
    }


    public function classSubscriptionFees()
    {
        return $this->belongsToMany(SubscriptionFee::class, 'financial_document_subscription_fee');
    }



    public function mealFees()
    {
        // return $this->belongsToMany(SubscriptionFee::class, 'financial_document_subscription_fee');

        return $this->belongsToMany(SubscriptionFee::class, 'financial_document_subscription_fee')
            ->where('subscription_fee_id', '=', "100")
            ->orWhere('subscription_fee_id', '=', '200');
    }


    public function breakFastSubscriptionFees()
    {
        return $this->belongsToMany(SubscriptionFee::class, 'financial_document_subscription_fee')
            ->withPivot('student_id', 'amount')
            ->withTimestamps();
    }





    public function grades()
    {
        return $this->hasMany(Grade::class);
    }

    public function section()
    {
        return $this->belongsTo(Section::class, 'section_id');
    }
    public function healthFile()
    {
        return $this->hasOne(HealthFile::class);
    }


    // Purchases relations :

    public function parentWallet()
    {
        return $this->parent->wallet();  // A student shares the parent's wallet
    }

    public function purchases()
    {
        return $this->hasMany(Purchase::class);
    }

    public function purchaseCeiling()
    {
        return $this->hasOne(StudentPurchaseCeiling::class);
    }

    public function restrictedMeals()
    {
        return $this->belongsToMany(Meal::class, 'student_restricted_meals');
    }

    public function studyYear() // The academic year they are enrolled in for this record
    {
        return $this->belongsTo(StudyYear::class);
    }

    public function parentRelationships()
    {
        return $this->hasMany(StudentParentRelationship::class, 'student_id');
    }

    // public function parents()
    // {
    //     return $this->belongsToMany(ParentInfo::class, 'student_parent_relationships', 'student_id', 'parent_id')
    //                 ->withPivot('relationship_type')
    //                 ->withTimestamps();
    // }

    public function assessmentScores()
    {
        return $this->hasMany(StudentAssessmentScore::class);
    }

    public function termSubjectGrades()
    {
        return $this->hasMany(StudentTermSubjectGrade::class);
    }

    public function finalYearGrades()
    {
        return $this->hasMany(StudentFinalYearGrade::class);
    }

    public function attendanceRecords()
    {
        return $this->hasMany(StudentAttendance::class);
    }

    public function reportComments()
    {
        return $this->hasMany(StudentReportComment::class);
    }

    public function evaluations()
    {
        return $this->hasMany(StudentEvaluation::class);
    }

}
