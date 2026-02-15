<?php

use App\Models\Request_reservation;
use App\Models\Reservation;
use App\Models\ReservationAnalysis;
use App\Models\Patient;
use App\Models\History;
use App\Models\Reminder;
use Illuminate\Support\Facades\DB;

require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

DB::beginTransaction();

try {
    // Find the pending request (created by previous script)
    $reservationRequest = Request_reservation::with('analyses')->where('status', 'pending')->first();
    
    if (!$reservationRequest) {
        throw new Exception("No pending reservation request found.");
    }
    
    $analysis_date = now()->addDays(2)->toDateString();
    $time = '09:00';

    // 1. Create patient
    $patient = Patient::create([
        'name' => $reservationRequest->name,
        'email' => $reservationRequest->email,
        'phone' => $reservationRequest->phone,
        'gender' => $reservationRequest->gender,
        'birth_date' => $reservationRequest->birth_date
    ]);

    // 2. Create parent reservation
    $reservation = Reservation::create([
        'patient_id' => $patient->id,
        'analysis_date' => $analysis_date,
        'time' => $time,
        'status' => 'booked'
    ]);

    // 3. Create linked reservation analyses
    foreach ($reservationRequest->analyses as $analyse) {
        $resAnalysis = ReservationAnalysis::create([
            'reservation_id' => $reservation->id,
            'analysis_id' => $analyse->id,
            'status' => 'booked'
        ]);

        // Legacy history
        $history = History::create([
            'patient_id' => $patient->id,
            'analyse_id' => $analyse->id,
            'analysis_date' => $analysis_date,
            'time' => $time,
            'status' => 'booked'
        ]);

        // Reminder correctly linked to history and reservation
        Reminder::create([
            'reservation_id' => $reservation->id,
            'patient_id' => $patient->id,
            'analyse_id' => $analyse->id,
            'scheduled_for' => \Carbon\Carbon::parse($analysis_date)->subDay(),
            'is_sent' => false,
            'history_id' => $history->id
        ]);
    }

    // 4. Update request
    $reservationRequest->update([
        'status' => 'confirmed',
        'patient_id' => $patient->id,
        'reservation_id' => $reservation->id
    ]);

    DB::commit();
    echo "SUCCESS: Reservation confirmed.\n";
    echo "Reservation ID: " . $reservation->id . "\n";
    echo "Analyses count: " . $reservation->reservationAnalyses->count() . "\n";
    
    // Quick verify
    $check = Reservation::with('reservationAnalyses.analyse')->find($reservation->id);
    foreach($check->reservationAnalyses as $ra) {
        echo "- Analysis: " . $ra->analyse->name . " (Status: " . $ra->status . ")\n";
    }

} catch (\Exception $e) {
    DB::rollBack();
    echo "ERROR: " . $e->getMessage() . "\n";
}
