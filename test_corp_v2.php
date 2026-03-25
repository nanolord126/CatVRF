<?php declare(strict_types=1);

require_once 'vendor/autoload.php';
use App\Domains\Beauty\Models\Appointment;
use App\Domains\Beauty\Services\CorporateEventService;
use App\Domains\Beauty\Services\AppointmentCancellationService;
use Carbon\Carbon;
use Illuminate\Container\Container;
use Illuminate\Support\Facades\Facade;

$app = new Container();
$app->singleton('db', function() { return new class { public function table($t) { return new class { public function where($k, $v) { return $this; } public function count() { return 0; } }; } }; });
$app->singleton('log', function() { return new class { public function channel($c) { return new class { public function info($m, $v) {} public function error($m, $v) {} }; } }; });
Facade::setFacadeApplication($app);

function runTest(int $participants, int $hoursBefore, bool $aiLook = false) {
    $appointment = new Appointment();
    $appointment->is_corporate_event = true;
    $appointment->participants_count = $participants;
    $appointment->price_cents = 100000;
    $appointment->datetime_start = Carbon::now()->addHours($hoursBefore);
    $appointment->metadata = $aiLook ? ['ai_look_id' => 1] : [];
    
    $corpService = new CorporateEventService();
    $cancelService = new AppointmentCancellationService();
    $fees = $corpService->calculateCorporateFees($appointment, 'cancel');
    $refund = $cancelService->calculateRefund($appointment, Carbon::now());

    echo "\n- TEST Corp $participants px, $hoursBefore h -\n";
    echo "Base %: " . $fees['fee_percent'] . "%\n";
    echo "Multiplier: " . $fees['multiplier'] . "x\n";
    echo "Total Penalty: " . $refund['penalty_percent'] . "%\n";
}

runTest(8, 120); // 5 days (120h)
runTest(15, 40); // 40 hours
runTest(15, 40, true); // 40h + AI
