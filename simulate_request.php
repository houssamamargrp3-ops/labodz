<?php

use App\Models\Analyse;
use App\Models\Request_reservation;
use Illuminate\Support\Facades\DB;

require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

DB::beginTransaction();

try {
    // 1. Find CBC and FBS
    $cbc = Analyse::where('code', 'CBC')->first();
    $fbs = Analyse::where('code', 'FBS')->first();

    if (! $cbc || ! $fbs) {
        throw new Exception('Analyses not found. Did you seed?');
    }

    // 2. Create the request
    $request = Request_reservation::create([
        'name' => 'Simulation Patient',
        'email' => 'sim@example.com',
        'phone' => '0555123456',
        'gender' => 'male',
        'birth_date' => '1985-05-05',
        'preferred_date' => now()->addDays(2)->toDateString(),
        'preferred_time' => '10:30',
        'status' => 'pending',
    ]);

    // 3. Attach analyses (simulating the many-to-many relationship)
    // The link table is 'request_reservation_analyses' based on the model
    $request->analyses()->attach([$cbc->id, $fbs->id]);

    DB::commit();
    echo 'SUCCESS: Created reservation request ID: '.$request->id."\n";
    echo "Analyses attached: CBC and FBS\n";
} catch (\Exception $e) {
    DB::rollBack();
    echo 'ERROR: '.$e->getMessage()."\n";
}
