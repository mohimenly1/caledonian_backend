# 🔍 دليل Debug لإشعارات FCM

## 📋 خطوات Debug

### 1. التحقق من FCM Token
```bash
# في Laravel Tinker
php artisan tinker
>>> $user = App\Models\User::find(USER_ID);
>>> $user->fcm_token;
```

### 2. مراقبة Logs في الوقت الفعلي
```bash
# في terminal
tail -f school-app/storage/logs/laravel.log | grep -i "fcm\|notification"
```

### 3. اختبار إرسال إشعار مباشر
```bash
# استخدم FirebaseTestController
curl "http://127.0.0.1:8001/api/firebase/test?fcm_token=YOUR_FCM_TOKEN"
```

### 4. التحقق من إعدادات Firebase
```bash
# في Laravel Tinker
php artisan tinker
>>> config('firebase.projects.app.credentials');
>>> file_exists(config('firebase.projects.app.credentials'));
```

### 5. التحقق من Notification Channel
```bash
# تأكد من أن NotificationChannel مُسجل بشكل صحيح
php artisan route:list | grep -i "fcm\|notification"
```

## 🔎 Logs المهمة

### ✅ Logs عند إرسال رسالة في Chat Group:
1. `[ChatController@sendMessage] 🔍 DEBUG: Checking notification for recipient`
2. `[ChatController@sendMessage] 📤 Attempting to send FCM notification`
3. `[NewGroupMessageNotification] 🔧 Constructing notification`
4. `[User@routeNotificationForFcm] 🔍 Getting FCM token for user`
5. `[NewGroupMessageNotification@toFcm] 🔧 Building FCM message`
6. `[ChatController@sendMessage] ✅ FCM notification sent successfully`

### ❌ Logs عند حدوث خطأ:
- `[ChatController@sendMessage] ❌ Failed to send FCM notification`

## 🛠️ خطوات استكشاف الأخطاء

### المشكلة 1: لا توجد logs لإرسال الإشعارات
**الحل:**
- تحقق من أن المستلمين لديهم `fcm_token` في قاعدة البيانات
- تحقق من أن الكود يصل إلى منطق إرسال الإشعارات

### المشكلة 2: Logs تظهر لكن الإشعارات لا تصل
**الحل:**
- تحقق من FCM token في قاعدة البيانات (قد يكون منتهي الصلاحية)
- تحقق من Firebase credentials path
- جرّب إرسال إشعار مباشر من Firebase Console

### المشكلة 3: خطأ في إرسال الإشعارات
**الحل:**
- تحقق من error message في logs
- تحقق من Firebase credentials file موجود وصحيح
- تحقق من Firebase project ID متطابق

## 📱 التحقق من Flutter App

### 1. تحقق من FCM Token في التطبيق
```dart
// في Flutter app
FirebaseMessaging.instance.getToken().then((token) {
  print('FCM Token: $token');
});
```

### 2. تحقق من استقبال الإشعارات
```dart
// في Flutter app
FirebaseMessaging.onMessage.listen((RemoteMessage message) {
  print('Received message: ${message.messageId}');
  print('Notification: ${message.notification}');
  print('Data: ${message.data}');
});
```

### 3. تحقق من أذونات الإشعارات
```dart
// في Flutter app
final NotificationSettings settings = await FirebaseMessaging.instance.requestPermission();
print('Authorization status: ${settings.authorizationStatus}');
```

## 🔗 روابط مفيدة

- Firebase Console: https://console.firebase.google.com/project/edura-70c46
- Firebase Test Notification: http://127.0.0.1:8001/api/firebase/test
- Laravel Logs: `school-app/storage/logs/laravel.log`

