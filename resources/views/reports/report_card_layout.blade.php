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
    {{-- المرور على كل طالب في مصفوفة البيانات --}}
    @foreach($reportData as $studentReport)
        {{-- استدعاء ملف القالب وتمرير بيانات الطالب الواحد إليه --}}
        @include($viewPath, [
            'studentReport' => $studentReport, 
            'template' => $template,
            'processTemplate' => $processTemplate,
            'arabic' => $arabic
        ])
    @endforeach
</body>
</html>
