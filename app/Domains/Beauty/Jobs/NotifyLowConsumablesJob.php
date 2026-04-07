<?php

declare(strict_types=1);

namespace App\Domains\Beauty\Jobs;

use App\Domains\Beauty\Models\BeautyConsumable;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Psr\Log\LoggerInterface;
use Ramsey\Uuid\Uuid;

/**
 * NotifyLowConsumablesJob — находит расходники Beauty с остатком ≤ порога
 * и логирует предупреждение для каждого.
 */
final class NotifyLowConsumablesJob implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    public int $tries   = 3;
    public int $timeout = 60;

    private string $correlationId;

    public function __construct(string $correlationId = '')
    {
        $this->correlationId = $correlationId !== '' ? $correlationId : Uuid::uuid4()->toString();
    }

    public function handle(LoggerInterface $logger): void
    {
        $lowStock = BeautyConsumable::query()
            ->whereColumn('current_stock', '<=', 'min_stock_threshold')
            ->get();

        if ($lowStock->isEmpty()) {
            return;
        }

        foreach ($lowStock as $item) {
            $logger->warning('Low consumable stock.', [
                'consumable_id'  => $item->id,
                'name'           => $item->name,
                'stock'          => $item->current_stock,
                'threshold'      => $item->min_stock_threshold,
                'correlation_id' => $this->correlationId,
            ]);
        }

        $logger->info('NotifyLowConsumablesJob completed.', [
            'notified_count' => $lowStock->count(),
            'correlation_id' => $this->correlationId,
        ]);
    }

    /** @return array<int, string> */
    public function tags(): array
    {
        return ['beauty', 'job:notify-low-consumables'];
    }
}
