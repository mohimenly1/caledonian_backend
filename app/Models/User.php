<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Facades\Hash;
use Laravel\Sanctum\HasApiTokens;
use Illuminate\Database\Eloquent\SoftDeletes; // Import SoftDeletes
use Illuminate\Support\Facades\Cache;

// import Storage
use Illuminate\Support\Facades\Storage; // Import Storage for file handling


// Hot wave step :

 use App\Models\Student; // تم تقديمه من قبلك
 use App\Models\ParentInfo; // افترضنا هذا الاسم لجدول parents
 use App\Models\Employee; // تم تقديمه من قبلك
 use App\Models\TeacherCourseAssignment;
 use App\Models\Assessment;
 use App\Models\StudentAssessmentScore;
 use App\Models\CourseMaterial;
 use App\Models\StudentAttendance;
 use App\Models\StudentTermSubjectGrade;
 use App\Models\ClassRoom; // أو SchoolClass إذا كنت ستغير الاسم لاحقًا
class User extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable , SoftDeletes;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'name',
        'phone',
        'username',
        'email',
        'address',
        'password',
        'user_type',
        'last_activity', // Add this line
        'photo', // add this
        'fcm_token', // <-- add this
        'cover_photo', // New
        'bio',         // New
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array<int, string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'last_activity' => 'datetime',  // Add this line
            'is_admin' => 'boolean',
            'is_blocked' => 'boolean',
            'is_online' => 'boolean',
        ];
    }
    public function parentInfo()
    {
        return $this->hasOne(ParentInfo::class, 'user_id');
    }

        // --- العلاقات الحالية التي قدمتها (مع بعض التعديلات الطفيفة إذا لزم الأمر) ---
        public function parentProfile() // إذا كان المستخدم ولي أمر (مستخدمًا النموذج المقترح ParentModel)
        {
            // 'ParentInfo' في نموذجك الحالي. إذا كان اسم النموذج ParentModel كما اقترحنا:
            return $this->hasOne(ParentInfo::class, 'user_id');
            // أو إذا كان اسم النموذج ParentInfo كما هو في نموذجك:
            // return $this->hasOne(ParentInfo::class, 'user_id');
        }

        public function teacherProfile() // إذا كان المستخدم معلمًا وله ملف تعريف في جدول Employees
        {
            // 'Teacher' في نموذجك الحالي، ولكن يبدو أنه يشير إلى Employee
            // إذا كان المعلمون هم موظفون، فالعلاقة مع Employee هي الأصح.
            return $this->hasOne(Employee::class, 'user_id')->where('is_teacher', true);
        }
    
        public function employeeProfile() // إذا كان المستخدم موظفًا (بشكل عام)
        {
            return $this->hasOne(Employee::class, 'user_id');
        }

        public function studentProfile() // إذا كان المستخدم طالبًا
        {
            return $this->hasOne(Student::class, 'user_id');
        }


    public function teacher()
    {
        return $this->hasOne(Teacher::class);
    }

    public function employee()
{
    return $this->hasOne(Employee::class);
}

    public function staff()
    {
        return $this->hasOne(Staff::class);
    }

    public function posts()
    {
        return $this->hasMany(Post::class, 'user_id'); // Assumes 'user_id' is the foreign key in your 'posts' table
    }
    public function student()
    {
        return $this->hasOne(Student::class);
    }

    public function parent()
    {
        return $this->hasOne(ParentInfo::class);
    }

    public function driver()
    {
        return $this->hasOne(Driver::class);
    }

    public function wallet()
    {
        return $this->hasOne(Wallet::class);
    }

    public function financialMatters()
    {
        return $this->hasMany(FinancialMatter::class);
    }


//     public function teachingTimetables()
// {
//     return $this->hasMany(Timetable::class, 'teacher_id');
// }

    public function registrationBills()
    {
        return $this->hasMany(RegistrationBill::class);
    }
    public function setPasswordAttribute($password)
    {
        $this->attributes['password'] = Hash::make($password);
    }

    public function isOnline()
    {
        if (!$this->last_activity) {
            return false;
        }
        
        // For API responses, don't use cache to get real-time status
        if (request()->wantsJson()) {
            return $this->last_activity->gt(now()->subMinutes(5));
        }
        
        // For web views, use cached version
        return Cache::remember("user-{$this->id}-online", now()->addMinutes(1), function() {
            return $this->last_activity->gt(now()->subMinutes(5));
        });
    }


    public function lastSeen()
    {
        if (!$this->last_activity) {
            return 'Never';
        }

        return $this->last_activity->diffForHumans();
    }

    public function badges()
{
    return $this->belongsToMany(Badge::class, 'user_badges');
}

public function createdGroups()
{
    return $this->hasMany(ChatGroup::class, 'creator_id');
}

public function groups()
{
    return $this->belongsToMany(ChatGroup::class, 'group_members', 'user_id', 'group_id');
}

public function sentMessages()
{
    return $this->hasMany(Message::class, 'sender_id');
}

public function stories()
{
    return $this->hasMany(Story::class);
}

public function storyViews()
{
    return $this->hasManyThrough(StoryView::class, Story::class);
}
public function receivedMessages()
{
    return $this->hasMany(Message::class, 'recipient_id');
}

public function followers()
{
    return $this->belongsToMany(User::class, 'user_follows', 'followed_id', 'follower_id');
}

public function following()
{
    return $this->belongsToMany(User::class, 'user_follows', 'follower_id', 'followed_id');
}

public function blockedUsers()
{
    return $this->belongsToMany(User::class, 'user_blocks', 'blocker_id', 'blocked_id');
}

public function blockedByUsers()
{
    return $this->belongsToMany(User::class, 'user_blocks', 'blocked_id', 'blocker_id');
}

public function hasBadge($badgeName)
{
    return $this->badges()->where('name', $badgeName)->exists();
}

// Add this method to handle notifications
public function routeNotificationForFcm()
{
    \Illuminate\Support\Facades\Log::info('[User@routeNotificationForFcm] 🔍 Getting FCM token for user', [
        'user_id' => $this->id,
        'has_fcm_token' => !empty($this->fcm_token),
        'fcm_token_preview' => $this->fcm_token ? substr($this->fcm_token, 0, 50) . '...' : 'NULL',
    ]);
    
    return $this->fcm_token;
}

public function receivesBroadcastNotificationsOn()
{
    return 'App.User.'.$this->id;
}
// In your User model
protected $appends = ['is_online','photo_url', 'cover_photo_url'];

public function getPhotoUrlAttribute()
{
    if ($this->photo) {
        // Ensure your 'public' disk is correctly linked (php artisan storage:link)
        // and APP_URL is set in .env for Storage::url to work correctly.
        return Storage::disk('public')->url($this->photo);
    }
    // You can return a default placeholder URL or null
    return null; // Example: 'https://ui-avatars.com/api/?name=' . urlencode($this->name) . '&color=7F9CF5&background=EBF4FF';
}

public function getCoverPhotoUrlAttribute()
{
    if ($this->cover_photo) {
        return Storage::disk('public')->url($this->cover_photo);
    }
    return null; // Or a default cover photo URL
}
// public function getIsOnlineAttribute()
// {
//     if (!$this->last_activity) {
//         return false;
//     }
    
//     return Cache::remember("user-{$this->id}-online", now()->addMinutes(1), function() {
//         return $this->last_activity->gt(now()->subMinutes(5));
//     });
// }

public function getIsOnlineAttribute() // Corrected accessor name from your previous isOnline() method
{
    if (!$this->last_activity) {
        return false;
    }
    // For API responses, calculate directly (cache might be fine too, but direct is often preferred for "live" status)
    return $this->last_activity->gt(now()->subMinutes(5));

    // Your original Cache logic for reference:
    // return Cache::remember("user-{$this->id}-online", now()->addMinutes(1), function() {
    //     return $this->last_activity->gt(now()->subMinutes(5));
    // });
}

public function caledonianNotifications()
{
    return $this->hasMany(CaledonianNotification::class);
}

/**
 * Get the user's unread caledonian notifications.
 */
public function unreadCaledonianNotifications()
{
    return $this->caledonianNotifications()->where('read', false);
}
public function teacherSubjects()
{
    return $this->hasMany(TeacherSubject::class, 'teacher_id');
}
public function likedPosts()
{
    return $this->belongsToMany(Post::class, 'post_likes')
        ->withTimestamps();
}

public function mentionedPosts()
{
    return $this->belongsToMany(Post::class, 'post_mentions')
        ->withTimestamps();
}


//  HOT WAVE START HERE : //





public function gradedAssessmentScores() // Scores graded by this teacher
{
//  النتائج التي تم تقييمها من قبل هذا المعلم
    return $this->hasMany(StudentAssessmentScore::class, 'graded_by_teacher_id');
}







public function recordedAttendance()
{
    return $this->hasMany(StudentAttendance::class, 'recorded_by_teacher_id');
}



    // --- العلاقات الجديدة المقترحة للنظام الأكاديمي المطور ---

    /**
     * المقررات الدراسية المسندة لهذا المستخدم كمعلم.
     */
    public function teacherCourseAssignments()
    {
        return $this->hasMany(TeacherCourseAssignment::class, 'teacher_id');
    }

    /**
     * التقييمات التي أنشأها هذا المستخدم (إذا كان معلمًا).
     */
    public function createdAssessments()
    {
        return $this->hasMany(Assessment::class, 'created_by_teacher_id');
    }

    /**
     * درجات الطلاب التي قام هذا المستخدم برصدها (إذا كان معلمًا).
     */
    public function gradedStudentAssessmentScores()
    {
        return $this->hasMany(StudentAssessmentScore::class, 'graded_by_teacher_id');
    }

    /**
     * المواد التعليمية التي رفعها هذا المستخدم (إذا كان معلمًا).
     */
    public function uploadedCourseMaterials()
    {
        return $this->hasMany(CourseMaterial::class, 'uploader_teacher_id');
    }

    /**
     * سجلات الحضور التي قام هذا المستخدم بتسجيلها (إذا كان معلمًا أو موظفًا مخولًا).
     */
    public function recordedStudentAttendances()
    {
        return $this->hasMany(StudentAttendance::class, 'recorded_by_user_id');
    }

    /**
     * الدرجات النهائية للفصول الدراسية التي قام هذا المستخدم باعتمادها.
     */
    public function finalizedTermGrades()
    {
        return $this->hasMany(StudentTermSubjectGrade::class, 'finalized_by_user_id');
    }

    /**
     * الدرجات النهائية للسنوات الدراسية التي قام هذا المستخدم باعتمادها.
     */
    public function finalizedYearGrades()
    {
        return $this->hasMany(StudentFinalYearGrade::class, 'finalized_by_user_id');
    }

    /**
     * الصفوف التي يكون هذا المستخدم مربيًا لها (Homeroom Teacher).
     * اسم النموذج للصفوف هو 'Classes' بناءً على طلبك للاحتفاظ بالاسم القديم.
     */
    public function managedClassesAsHomeroomTeacher()
    {
        return $this->hasMany(ClassRoom::class, 'class_teacher_id');
    }

    /**
     * التعليقات العامة على صحائف الطلاب التي كتبها هذا المستخدم.
     */
    public function writtenStudentReportComments()
    {
        return $this->hasMany(StudentReportComment::class, 'comment_by_user_id');
    }

    /**
     * تقييمات الطلاب غير الأكاديمية التي قام بها هذا المستخدم.
     */
    public function conductedStudentEvaluations()
    {
        return $this->hasMany(StudentEvaluation::class, 'evaluated_by_user_id');
    }

    /**
     * الصحائف التي تم إنشاؤها بواسطة هذا المستخدم.
     */
    public function generatedReportCards()
    {
        return $this->hasMany(GeneratedReportCard::class, 'generated_by_user_id');
    }

    /**
     * الحصص الدراسية المسندة لهذا المستخدم كمعلم في الجدول الزمني.
     * (هذه العلاقة موجودة في نموذجك الحالي باسم teachingTimetables)
     */
    public function teachingTimetables() // Kept from your model
    {
        return $this->hasMany(Timetable::class, 'teacher_id');
    }

    public function courses()
{
    // هذه هي علاقة many-to-many بين المستخدمين (المعلمين) والمقررات
    return $this->belongsToMany(CourseOffering::class, 'teacher_course_assignments', 'teacher_id', 'course_offering_id')
                ->withPivot('role') // إذا أردت الوصول إلى حقل role
                ->withTimestamps();
}

}
