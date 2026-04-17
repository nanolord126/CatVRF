<?php declare(strict_types=1);

namespace App\Domains\Auto\Listeners;

use App\Domains\Auto\Events\CarImportDutiesPaidEvent;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Support\Facades\Log;

final class UpdateImportStatusListener implements ShouldQueue
{
    public function handle(CarImportDutiesPaidEvent $event): void
    {
        Log::channel('audit')->info('car.import.duties.paid.listener', [
            'import_id' => $event->importId,
            'vin' => $event->vin,
            'paid_amount' => $event->paidAmount,
            'correlation_id' => $event->correlationId,
        ]);
    }
}
