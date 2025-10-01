<?php

namespace App\Http\Controllers;

use App\Models\Term;
use App\Models\AssessmentType;
use Illuminate\Http\Request;

class AssessmentMetadataController extends Controller
{
    /**
     * جلب البيانات الأساسية اللازمة لنماذج إنشاء التقييم.
     */
    public function index()
    {
        $terms = Term::all(['id', 'name']);
        $assessmentTypes = AssessmentType::all(['id', 'name']);

        return response()->json([
            'terms' => $terms,
            'assessment_types' => $assessmentTypes,
        ]);
    }
}