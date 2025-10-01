<?php

namespace App\Notifications;

use App\Models\Assessment;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;
use NotificationChannels\Fcm\FcmChannel;
use NotificationChannels\Fcm\FcmMessage;
use NotificationChannels\Fcm\Resources\Notification as FcmNotification;

class NewAssessmentNotification extends Notification
{
    use Queueable;

    protected $assessment;
    protected $studentId; // ✨ إضافة جديدة

    public function __construct(Assessment $assessment, $studentId)
    {
        $this->assessment = $assessment;
        $this->studentId = $studentId; // ✨ إضافة جديدة
    }

    public function via($notifiable)
    {
        return [FcmChannel::class]; // سنرسل عبر FCM فقط، والحفظ في قاعد البيانات سيكون يدوياً
    }

    public function toFcm($notifiable): FcmMessage
    {
        // 1. تحديد طريقة التسليم بناءً على نوع التقييم (صحيح)
        $deliveryMethod = $this->assessment->is_online_quiz ? 'إلكترونياً عبر التطبيق' : 'في المدرسة';
        
        // ✨ 2. بناء نص الإشعار بشكل صحيح وموجز (التعديل الرئيسي) ✨
        $notificationBody = "تم نشر تقييم '{$this->assessment->title}'. طريقة التسليم: {$deliveryMethod}.";
    
        // 3. بناء كائن الإشعار
        $notification = new FcmNotification(
            title: "تقييم جديد في مادة {$this->assessment->courseOffering->subject->name}",
            body: $notificationBody
        );
    
        // 4. إرسال البيانات الإضافية مع الإشعار
        return (new FcmMessage(notification: $notification))
            ->data([
                'type' => 'new_assessment',
                'assessment_id' => (string) $this->assessment->id,
                'student_id' => (string) $this->studentId,
                // ✨ إضافة الوصف هنا ليتم عرضه داخل التطبيق عند الحاجة ✨
                'description' => $this->assessment->description ?? '',
            ]);
    }
}