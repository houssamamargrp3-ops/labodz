<?php

use App\Models\PatientAnswer;
use App\Models\Question;
use App\Models\Reservation;
use App\Models\ReservationAnalysis;
use App\Services\AnalysisEligibilityService;
use Illuminate\Support\Facades\DB;

require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

DB::beginTransaction();

try {
    // 1. Find the FBS analysis in the reservation
    $resAnalysis = ReservationAnalysis::whereHas('analyse', function ($q) {
        $q->where('code', 'FBS');
    })->latest()->first();

    if (! $resAnalysis) {
        throw new Exception('FBS Analysis not found in reservations.');
    }

    $patientId = $resAnalysis->reservation->patient_id;
    $analysisId = $resAnalysis->analysis_id;

    // 2. Find the fasting question
    $question = Question::where('analyse_id', $analysisId)
        ->where('question', 'like', '%صائم%')
        ->first();

    if (! $question) {
        throw new Exception('Fasting question not found.');
    }

    // 3. Find the "No" option (disallowed)
    $noOption = $question->options()->where('text', 'لا')->first();

    // 4. Submit answer (Simulating the user answering "No")
    PatientAnswer::updateOrCreate(
        ['patient_id' => $patientId, 'question_id' => $question->id],
        ['option_id' => $noOption->id]
    );

    // 5. Run eligibility check
    $eligibilityService = app(AnalysisEligibilityService::class);
    $result = $eligibilityService->checkEligibility($patientId, $analysisId);

    echo 'Eligibility Status: '.$result['status']."\n";

    // Update status in DB as the controller would
    $statusMap = [
        'block' => 'blocked',
        'warning' => 'warning',
        'approval' => 'pending_approval',
        'eligible' => 'ready',
    ];
    $newStatus = $statusMap[$result['status']] ?? 'ready';
    $resAnalysis->update(['status' => $newStatus]);

    DB::commit();
    echo 'SUCCESS: FBS Analysis status updated to: '.$resAnalysis->status."\n";
} catch (\Exception $e) {
    DB::rollBack();
    echo 'ERROR: '.$e->getMessage()."\n";
}
