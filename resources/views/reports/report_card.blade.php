<!DOCTYPE html>
<html dir="rtl" lang="ar">
<head>
    <meta charset="UTF-8">
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>
    <title>{{ $arabic->utf8Glyphs('شهادات الطلاب') }}</title>
    <style>
        /* تعريف الخطوط والأنماط الأساسية للطباعة */
        body { 
            font-family: 'dejavu sans', sans-serif;
            font-size: 12px;
        }
        .report-card-page {
            page-break-after: always;
            width: 100%;
        }
        .report-card-page:last-child {
            page-break-after: avoid;
        }
    </style>
</head>
<body>
    @foreach($reportData as $studentReport)
        <div class="report-card-page">
            
            @if(isset($template['header_content']))
                {{-- عرض الهيدر المعالج بالكامل --}}
                {!! $processTemplate($template['header_content'], $studentReport) !!}
            @endif

            <main class="report-body">
                <!-- جدول الدرجات: سيتم بناؤه يدويًا لضمان التوافق الكامل -->
                <table style="width: 100%; border-collapse: collapse; text-align: center; margin-top: 20px; direction: rtl;">
                    <thead style="background-color: #4a5568; color: white;">
                        <tr>
                            <th style="border: 1px solid #666; padding: 8px;">{{ $arabic->utf8Glyphs('المادة') }}</th>
                            <th style="border: 1px solid #666; padding: 8px;">{{ $arabic->utf8Glyphs('الدرجة النهائية (%)') }}</th>
                            <th style="border: 1px solid #666; padding: 8px;">{{ $arabic->utf8Glyphs('التقدير') }}</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach(data_get($studentReport, 'grades', []) as $grade)
                        <tr>
                            <td style="border: 1px solid #666; padding: 8px;">{{ data_get($grade, 'course_offering.subject.name', '') }}</td>
                            <td style="border: 1px solid #666; padding: 8px;">{{ data_get($grade, 'weighted_average_score_percentage', 'N/A') }}</td>
                            <td style="border: 1px solid #666; padding: 8px;">{{ data_get($grade, 'grading_scale_entry.grade_label', 'N/A') }}</td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>

                <div class="additional-sections" style="direction: rtl; margin-top: 20px;">
                    @if(data_get($template, 'layout_options.show_attendance', false))
                        <div class="section">
                            <h4>{{ $arabic->utf8Glyphs('ملخص الحضور والغياب') }}</h4>
                            <p>
                                {{ $arabic->utf8Glyphs('أيام الغياب') }}: {{ data_get($studentReport, 'attendance.absent_days', 0) }} | 
                                {{ $arabic->utf8Glyphs('أيام التأخر') }}: {{ data_get($studentReport, 'attendance.late_days', 0) }}
                            </p>
                        </div>
                    @endif
                </div>
            </main>

            @if(isset($template['footer_content']))
                 <footer style="position: absolute; bottom: 50px; width: 100%; text-align: center;">
                    {{-- عرض التذييل المعالج بالكامل --}}
                    {!! $processTemplate($template['footer_content'], $studentReport) !!}
                 </footer>
            @endif
        </div>
    @endforeach
</body>
</html>
