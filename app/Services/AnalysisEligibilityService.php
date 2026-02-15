<?php

namespace App\Services;

use App\Models\AnalysisRule;
use App\Models\PatientAnswer;

class AnalysisEligibilityService
{
    /**
     * Check if a patient is eligible for a specific analysis.
     *
     * @param int $patient_id
     * @param int $analysis_id
     * @return string
     */
    public function checkEligibility($patient_id, $analysis_id)
    {
        // 1) Get rules: AnalysisRule::where('analysis_id', $analysisId)->get()
        $rules = AnalysisRule::where('analysis_id', $analysis_id)->get();

        // 2) Get patient answers: PatientAnswer::where('patient_id', $patientId)->pluck('option_id', 'question_id')
        $patientAnswers = PatientAnswer::where('patient_id', $patient_id)
            ->pluck('option_id', 'question_id');

        // 3) Loop through rules:
        foreach ($rules as $rule) {
            // If patient answer for rule.question_id equals rule.disallowed_option_id:
            if (isset($patientAnswers[$rule->question_id]) && $patientAnswers[$rule->question_id] == $rule->disallowed_option_id) {
                return [
                    'status' => $rule->action,
                    'reason' => "Rule violated"
                ];
            }
        }

        // 4) If no violations: return status = eligible
        return [
            'status' => 'eligible'
        ];
    }
}
