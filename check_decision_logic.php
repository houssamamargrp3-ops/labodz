<?php

use App\Models\Reservation;
use App\Models\Question;
use App\Models\Option;
use App\Models\PatientAnswer;
use App\Services\AnalysisEligibilityService;
use Illuminate\Support\Facades\DB;

require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

function runScenario($reservation, $answers, $scenarioName) {
    echo "\n--- Scenario: $scenarioName ---\n";
    
    DB::beginTransaction();
    try {
        $eligibilityService = app(AnalysisEligibilityService::class);
        $statusMap = [
            'block' => 'blocked',
            'warning' => 'warning',
            'approval' => 'pending_approval',
            'eligible' => 'ready',
        ];

        // 1. Submit answers
        foreach ($answers as $questionId => $optionId) {
            PatientAnswer::updateOrCreate(
                ['patient_id' => $reservation->patient_id, 'question_id' => $questionId],
                ['option_id' => $optionId]
            );
        }

        // 2. Check each analysis
        foreach ($reservation->reservationAnalyses as $resAnalysis) {
            $result = $eligibilityService->checkEligibility($reservation->patient_id, $resAnalysis->analysis_id);
            $newStatus = $statusMap[$result['status']] ?? 'ready';
            $resAnalysis->update(['status' => $newStatus]);
            echo "Analysis: " . $resAnalysis->analyse->name . " -> Result: " . $result['status'] . " -> Status updated to: " . $newStatus . "\n";
        }

        DB::commit();
        echo "Scenario $scenarioName completed successfully.\n";
    } catch (\Exception $e) {
        DB::rollBack();
        echo "ERROR in scenario $scenarioName: " . $e->getMessage() . "\n";
    }
}

// Setup
$reservation = Reservation::with('reservationAnalyses.analyse')->latest()->first();
if (!$reservation) {
    die("No reservation found to test.\n");
}

$cbc = $reservation->reservationAnalyses->where('analyse.code', 'CBC')->first();
$fbs = $reservation->reservationAnalyses->where('analyse.code', 'FBS')->first();

if (!$cbc || !$fbs) {
    die("This test requires a reservation with both CBC and FBS.\n");
}

$cbcQuestion = Question::where('analyse_id', $cbc->analysis_id)->first();
$fbsQuestion = Question::where('analyse_id', $fbs->analysis_id)->first();

$cbcYes = $cbcQuestion->options()->where('text', 'نعم')->first()->id;
$cbcNo = $cbcQuestion->options()->where('text', 'لا')->first()->id;

$fbsYes = $fbsQuestion->options()->where('text', 'نعم')->first()->id;
$fbsNo = $fbsQuestion->options()->where('text', 'لا')->first()->id;

// Scenario 1: Both pass
runScenario($reservation, [
    $cbcQuestion->id => $cbcNo,
    $fbsQuestion->id => $fbsYes
], "Perfect Conditions (All Ready)");

// Scenario 2: CBC Warning (Blood Thinners), FBS Blocked (Not Fasting)
runScenario($reservation, [
    $cbcQuestion->id => $cbcYes,
    $fbsQuestion->id => $fbsNo
], "Mixed Violations (CBC Warning, FBS Blocked)");

// Scenario 3: CBC Pass, FBS Blocked
runScenario($reservation, [
    $cbcQuestion->id => $cbcNo,
    $fbsQuestion->id => $fbsNo
], "Partial Violation (CBC Ready, FBS Blocked)");
