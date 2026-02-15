<?php

use App\Models\Reservation;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

DB::beginTransaction();

try {
    $reservation = Reservation::latest()->first();
    if (!$reservation) {
        throw new Exception("No reservation found.");
    }

    $tomorrow = Carbon::tomorrow()->toDateString();
    $reservation->update(['analysis_date' => $tomorrow]);

    DB::commit();
    echo "SUCCESS: Reservation date updated to " . $tomorrow . "\n";
} catch (\Exception $e) {
    DB::rollBack();
    echo "ERROR: " . $e->getMessage() . "\n";
}
