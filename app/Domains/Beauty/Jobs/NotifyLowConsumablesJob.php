<?php

declare(strict_types=1);

namespace App\Domains\Beauty\Jobs;

use App\Domains\Beauty\Models\Consumable;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

final class NotifyLowConsumablesJob implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    public function __construct(
        private readonly string $correlationId,
    ) {}

    public function handle(): void
    {
        $lowStock = Consumable::query()
            ->whereColumn('current_stock', '<=', 'min_stock_threshold')
            ->get();

        foreach ($lowStock as $item) {
            Log::channel('audit')->warning('Low consumable stock', [
                'consumable_id' => $item->id,
                'name' => $item->name,
                'stock' => $item->current_stock,
                'correlation_id' => $this->correlationId,
            ]);
        }
    }
}
