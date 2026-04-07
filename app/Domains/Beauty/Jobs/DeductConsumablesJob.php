<?php

declare(strict_types=1);

namespace App\Domains\Beauty\Jobs;

use App\Domains\Beauty\Models\Appointment;
use App\Services\InventoryManagementService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Psr\Log\LoggerInterface;
use Ramsey\Uuid\Uuid;
use App\Services\FraudControlService;

/**
 * DeductConsumablesJob — списывает расходники при завершении записи.
 */
final class DeductConsumablesJob implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    public int $tries   = 3;
    public int $timeout = 60;

    private string $correlationId;

    public function __construct(
        private int $appointmentId,
        string $correlationId = '',
    ) {
        $this->correlationId = $correlationId !== '' ? $correlationId : Uuid::uuid4()->toString();
    }

    public function handle(
        InventoryManagementService $inventory,
        LoggerInterface            $logger,
        FraudControlService        $fraud,
        \Illuminate\Database\DatabaseManager $db,
    ): void {
        $appointment = Appointment::with('service')->findOrFail($this->appointmentId);
        $consumables = $appointment->service->consumables_json ?? [];

        if (empty($consumables)) {
            return;
        }

        $db->transaction(function () use ($appointment, $consumables, $inventory, $logger): void {
            foreach ($consumables as $consumable) {
                $inventory->deductStock(
                    (int) $consumable['id'],
                    (int) $consumable['quantity'],
                    'appointment_completed',
                    'appointment',
                    $appointment->id,
                );
            }

            $logger->info('Consumables deducted.', [
                'appointment_id' => $appointment->id,
                'count'          => count($consumables),
                'correlation_id' => $this->correlationId,
            ]);
        });
    }

    /** @return array<int, string> */
    public function tags(): array
    {
        return ['beauty', 'job:deduct-consumables', "appointment:{$this->appointmentId}"];
    }
}
