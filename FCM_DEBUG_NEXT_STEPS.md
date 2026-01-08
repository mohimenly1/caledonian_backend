# 🔍 خطوات Debug التالية

## 📊 من Logs الحالية

من logs في `laravel.log`، أرى أن:
- ✅ الإشعارات يتم إنشاؤها بنجاح
- ✅ FCM tokens موجودة وصحيحة
- ✅ `notify()` يتم استدعاؤه
- ✅ `toFcm()` يتم استدعاؤه
- ✅ `FCM message built successfully`
- ✅ `FCM notification sent successfully`

**لكن الإشعارات لا تصل للجهاز!**

## 🔎 المشكلة المحتملة

`NotificationChannels\Fcm` package يستدعي `sendMulticast()` على Firebase Messaging client، لكن:
- لا يوجد logging للأخطاء المحتملة
- قد يفشل `sendMulticast()` بصمت
- قد تكون Firebase credentials غير صحيحة أو غير مهيأة

## ✅ الإصلاحات المطبقة

### 1. إضافة Event Listener للـ Failed Notifications
تم إضافة event listener في `AppServiceProvider` للتحقق من الأخطاء:
```php
Event::listen(NotificationFailed::class, function (NotificationFailed $event) {
    // Log failed notifications
});
```

### 2. إضافة Logging للـ Result
تم إضافة logging لنتيجة `notify()` للتحقق من ما يتم إرجاعه.

## 🧪 خطوات الاختبار

### 1. أرسل رسالة جديدة في Chat Group
```bash
# راقب logs في الوقت الفعلي
tail -f school-app/storage/logs/laravel.log | grep -i "fcm\|notification\|failed"
```

### 2. ابحث عن Logs التالية:
- `[AppServiceProvider] ❌ FCM Notification Failed` - إذا فشل الإرسال
- `[ChatController@sendMessage] 📊 Notification result details` - تفاصيل النتيجة

### 3. إذا لم ترَ أي logs للأخطاء:
المشكلة قد تكون:
- Firebase credentials غير صحيحة
- FCM tokens غير صالحة (منتهية الصلاحية)
- Firebase project ID غير متطابق

## 🔧 خطوات إضافية

### 1. اختبار إرسال إشعار مباشر:
```bash
curl "http://127.0.0.1:8001/api/firebase/test?fcm_token=YOUR_FCM_TOKEN"
```

### 2. التحقق من Firebase Credentials:
```bash
cd school-app
php artisan tinker
>>> config('firebase.projects.app.credentials');
>>> file_exists(config('firebase.projects.app.credentials'));
```

### 3. التحقق من Firebase Messaging Service:
```bash
php artisan tinker
>>> $messaging = app('firebase.messaging');
>>> echo get_class($messaging);
```

## 📱 التحقق من Flutter App

### 1. تحقق من FCM Token:
```dart
FirebaseMessaging.instance.getToken().then((token) {
  print('Current FCM Token: $token');
});
```

### 2. تحقق من استقبال الإشعارات:
```dart
FirebaseMessaging.onMessage.listen((RemoteMessage message) {
  print('Received message: ${message.messageId}');
  print('Notification: ${message.notification}');
  print('Data: ${message.data}');
});
```

### 3. تحقق من الأذونات:
```dart
final NotificationSettings settings = await FirebaseMessaging.instance.requestPermission();
print('Authorization status: ${settings.authorizationStatus}');
```

## 🎯 النتيجة المتوقعة

بعد إرسال رسالة جديدة، يجب أن ترى إحدى هذه النتائج:

### ✅ إذا نجح الإرسال:
```
[ChatController@sendMessage] ✅ FCM notification sent successfully
[ChatController@sendMessage] 📊 Notification result details
```

### ❌ إذا فشل الإرسال:
```
[AppServiceProvider] ❌ FCM Notification Failed
[error_data] => [...]
[report] => [...]
```

## 🔗 روابط مفيدة

- Firebase Console: https://console.firebase.google.com/project/edura-70c46
- Firebase Test: http://127.0.0.1:8001/api/firebase/test
- Laravel Logs: `school-app/storage/logs/laravel.log`

