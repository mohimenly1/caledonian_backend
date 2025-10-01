<!DOCTYPE html>
<html>
<head>
    <title>Report Card - {{ $student->name }}</title>
    <style>
        @page { margin: 40px; }
        body {
            font-family: 'Comic Sans MS', cursive, sans-serif;
            position: relative;
            background-image: url('{{ public_path("logo-school-one.png") }}');
            background-repeat: no-repeat;
            background-position: center;
            background-size: 400px;
            opacity: 0.95;
            border: 15px solid #f7b733;
            padding: 30px;
        }

        .header {
            text-align: center;
            margin-bottom: 20px;
            position: relative;
        }

        .logo {
            width: 80px;
            position: absolute;
            top: 0;
            left: 30px;
        }

        .school-name {
            font-size: 30px;
            font-weight: bold;
            color: #2b7a78;
        }

        .report-card-title {
            font-size: 22px;
            margin: 10px 0;
            color: #17252a;
            letter-spacing: 1px;
        }

        .term-info {
            font-size: 16px;
            color: #3a3a3a;
        }

        .student-info, .performance-summary {
            margin-top: 20px;
        }

        .info-table, .grades-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 20px;
            background: #fffaf0;
        }

        .info-table td, .grades-table th, .grades-table td {
            padding: 8px;
            border: 1px solid #999;
        }

        .grades-table th {
            background-color: #ffeaa7;
        }

        h3 {
            color: #e17055;
            margin-bottom: 10px;
        }

        .footer {
            margin-top: 40px;
            text-align: center;
            position: relative;
        }

        .signature-line {
            border-top: 2px solid #000;
            width: 200px;
            margin: 0 auto;
            margin-top: 10px;
        }

        .stamp {
            position: absolute;
            right: 40px;
            bottom: 100px;
            opacity: 0.5;
            width: 120px;
        }

        .date {
            font-style: italic;
            margin-bottom: 40px;
        }

        .label {
            font-weight: bold;
            color: #444;
        }
    </style>
</head>
<body>

    <div class="header">
        <img src="{{ public_path('logo-school-one.png') }}" class="logo" />
        <div class="school-name">Caledonian International School</div>
        <div class="report-card-title">
            <img src="{{ public_path('icons/graduation.png') }}" style="height: 20px;" />
            Student Report Card
            <img src="{{ public_path('icons/graduation.png') }}" style="height: 20px;" />
        </div>
        <div class="term-info">Term: {{ $term->name }} - {{ $term->studyYear->year_study }}</div>
    </div>

    <div class="student-info">
        <table class="info-table">
            <tr>
                <td class="label" width="25%">Student Name:</td>
                <td width="25%">{{ $student->name }}</td>
                <td class="label" width="25%">Class:</td>
                <td width="25%">{{ $student->class->name }}</td>
            </tr>
            <tr>
                <td class="label">Student ID:</td>
                <td>{{ $student->id }}</td>
                <td class="label">Section:</td>
                <td>{{ $student->section->name ?? 'N/A' }}</td>
            </tr>
        </table>
    </div>

    <table class="grades-table">
        <thead>
            <tr>
                <th>Subject</th>
                <th>Exam</th>
                <th>Score</th>
                <th>Grade</th>
                <th>Remarks</th>
            </tr>
        </thead>
        <tbody>
            @foreach($student->grades->sortBy('subject.name') as $grade)
            @php
                $gradeScale = optional($gradingScale->firstWhere('min_score', '<=', $grade->score))
                    ->firstWhere('max_score', '>=', $grade->score);
            @endphp
            <tr>
                <td>{{ $grade->subject->name }}</td>
                <td>{{ $grade->exam->name ?? 'N/A' }}</td>
                <td>{{ $grade->score }}</td>
                <td>{{ $gradeScale->grade ?? 'N/A' }}</td>
                <td>{{ $gradeScale->remarks ?? 'N/A' }}</td>
            </tr>
            @endforeach
        </tbody>
    </table>

    <div class="performance-summary">
        <h3>Performance Summary</h3>
        <table class="info-table">
            <tr>
                <td class="label">Total Subjects:</td>
                <td>{{ $performance['total_subjects'] }}</td>
                <td class="label">Average Score:</td>
                <td>{{ number_format($performance['average_score'], 2) }}</td>
            </tr>
            <tr>
                <td class="label">Highest Score:</td>
                <td>{{ $performance['highest_score'] }}</td>
                <td class="label">Lowest Score:</td>
                <td>{{ $performance['lowest_score'] }}</td>
            </tr>
            <tr>
                <td class="label">Class Rank:</td>
                <td>{{ $performance['rank_in_class'] }}</td>
                <td class="label">Overall Grade:</td>
                <td>{{ $performance['overall_grade']->grade ?? 'N/A' }}</td>
            </tr>
        </table>
    </div>

    <div class="footer">
        <div class="date">Date: {{ date('d/m/Y') }}</div>
        <div style="display: flex; justify-content: space-around;">
            <div>
                <div>Class Teacher</div>
                <div class="signature-line"></div>
            </div>
            <div>
                <div>Principal</div>
                <div class="signature-line"></div>
            </div>
        </div>
        <img src="{{ public_path('logo-school-one.png') }}" class="stamp" />
    </div>

</body>
</html>
