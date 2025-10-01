<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB; // استيراد DB facade للتعامل المباشر مع قاعدة البيانات

class UpdateClassesStudyYearSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        // استخدام DB facade لتحديث جميع السجلات في جدول 'classes'
        // هذا الاستعلام يقوم بتعيين قيمة العمود 'study_year_id' إلى 1
        // لجميع السجلات التي تكون فيها قيمة هذا العمود حاليًا NULL.
        // هذا يضمن أننا لا نعيد الكتابة فوق قيم قد تكون موجودة بالفعل.
        DB::table('classes')
            ->whereNull('study_year_id')
            ->update(['study_year_id' => 1]);

        // طباعة رسالة في واجهة الأوامر لتأكيد اكتمال العملية
        $this->command->info('All classes with null study_year_id have been updated to study_year_id = 1.');
    }
}
