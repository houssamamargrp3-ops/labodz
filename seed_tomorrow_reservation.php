<?php

use App\Models\Analyse;
use App\Models\Patient;
use App\Models\Reservation;
use App\Models\ReservationAnalysis;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

DB::beginTransaction();

try {
    $patient = Patient::firstOrCreate(
        ['phone' => '0123456789'],
        ['name' => 'Reminder Test Patient', 'email' => 'reminder@example.com']
    );

    $analyses = Analyse::limit(2)->get();
    if ($analyses->count() < 2) {
        throw new Exception('Not enough analyses in the system to test grouping.');
    }

    $tomorrow = Carbon::tomorrow()->toDateString();

    // Create reservation for tomorrow
    $reservation = Reservation::create([
        'patient_id' => $patient->id,
        'analysis_date' => $tomorrow,
        'time' => '09:00',
        'status' => 'booked',
    ]);

    foreach ($analyses as $analysis) {
        ReservationAnalysis::create([
            'reservation_id' => $reservation->id,
            'analysis_id' => $analysis->id,
            'status' => 'booked',
        ]);
    }

    echo 'Created Reservation ID: '.$reservation->id.' for tomorrow with '.$analyses->count()." analyses.\n";

    DB::commit();
} catch (\Exception $e) {
    DB::rollBack();
    echo 'ERROR: '.$e->getMessage()."\n";
}
