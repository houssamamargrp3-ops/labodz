<?php

use App\Models\Reservation;
use App\Models\ReservationAnalysis;
use Illuminate\Http\Request;
use App\Http\Controllers\messagesController;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;

require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

// Mock Mail to avoid sending real emails
Mail::fake();

$reservation = Reservation::where('status', 'booked')->first();

if (!$reservation) {
    echo "No booked reservation found for testing.\n";
    exit;
}

echo "Testing result send for Reservation ID: " . $reservation->id . "\n";
echo "Initial Status: " . $reservation->status . "\n";

$controller = new messagesController();
$request = new Request([
    'reservation_id' => $reservation->id,
    'additional_notes' => 'Test results for all requested analyses.',
]);

// We need to bypass validation or mock it if needed, but since we are calling it directly:
try {
    $response = $controller->sendResult($request);
    
    $updatedReservation = Reservation::find($reservation->id);
    echo "New Reservation Status: " . $updatedReservation->status . "\n";
    echo "Result Notes: " . $updatedReservation->result_notes . "\n";
    
    $analysesStatuses = $updatedReservation->reservationAnalyses->pluck('status')->unique()->toArray();
    echo "Analyses Unique Statuses: " . implode(', ', $analysesStatuses) . "\n";
    
    if ($updatedReservation->status === 'completed' && count($analysesStatuses) === 1 && $analysesStatuses[0] === 'completed') {
        echo "SUCCESS: Reservation and all analyses marked as completed.\n";
    } else {
        echo "FAILURE: Status mismatch.\n";
    }
    
    Mail::assertSent(\Illuminate\Support\Facades\Mail::class); // This might be tricky with Mail::send
    echo "Mail logic executed (as per code path).\n";

} catch (\Exception $e) {
    echo "ERROR: " . $e->getMessage() . "\n";
}
