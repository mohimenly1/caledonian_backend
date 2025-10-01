<!DOCTYPE html>
<html>
<head>
    <title>Modern Report Card - {{ $student->name }}</title>
    <style>
        @page { margin: 20px; }
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background-color: #f8f9fa;
            color: #343a40;
        }
        .header {
            background: linear-gradient(135deg, #6a11cb 0%, #2575fc 100%);
            color: white;
            padding: 20px;
            text-align: center;
            border-radius: 8px;
            margin-bottom: 20px;
        }
        .student-info {
            background-color: white;
            border-radius: 8px;
            padding: 20px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            margin-bottom: 20px;
        }
        .grades-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 20px;
        }
        .grades-table th {
            background-color: #2575fc;
            color: white;
            padding: 12px;
            text-align: left;
        }
        .grades-table td {
            padding: 12px;
            border-bottom: 1px solid #e9ecef;
        }
        .grades-table tr:nth-child(even) {
            background-color: #f8f9fa;
        }
        .performance-summary {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 15px;
            margin-bottom: 20px;
        }
        .performance-card {
            background-color: white;
            border-radius: 8px;
            padding: 15px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            text-align: center;
        }
        .performance-card h4 {
            margin-top: 0;
            color: #2575fc;
        }
        .performance-card p {
            font-size: 24px;
            font-weight: bold;
            margin: 10px 0;
        }
    </style>
</head>
<body>
    <div class="header">
        <h1>Modern Report Card</h1>
        <h3>{{ $term->name }} - {{ $term->studyYear->year_study }}</h3>
    </div>

    <div class="student-info">
        <h2>{{ $student->name }}</h2>
        <p>Class: {{ $student->class->name }} | Section: {{ $student->section->name ?? 'N/A' }}</p>
    </div>

    <table class="grades-table">
        <thead>
            <tr>
                <th>Subject</th>
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
                <td>{{ $grade->score }}</td>
                <td>{{ $gradeScale->grade ?? 'N/A' }}</td>
                <td>{{ $gradeScale->remarks ?? 'N/A' }}</td>
            </tr>
            @endforeach
        </tbody>
    </table>

    <div class="performance-summary">
        <div class="performance-card">
            <h4>Average Score</h4>
            <p>{{ number_format($performance['average_score'], 2) }}</p>
        </div>
        <div class="performance-card">
            <h4>Class Rank</h4>
            <p>{{ $performance['rank_in_class'] }}</p>
        </div>
        <div class="performance-card">
            <h4>Overall Grade</h4>
            <p>{{ $performance['overall_grade']->grade ?? 'N/A' }}</p>
        </div>
    </div>
</body>
</html>