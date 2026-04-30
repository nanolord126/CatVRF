<?php

declare(strict_types=1);

namespace Modules\Inventory\Application\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Modules\Inventory\Application\Services\FIFOShelfLifeService;

final class ShelfLifeDailyJob implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    public int $tries = 3;
    public int $timeout = 300;

    public function __construct()
    {
        $this->onQueue('inventory');
    }

    public function handle(FIFOShelfLifeService $service): void
    {
        Log::info('Начало ежедневного обслуживания срока годности', [
            'timestamp' => now()->toIso8601String(),
        ]);

        try {
            $service->dailyMaintenance();

            Log::info('Завершение ежедневного обслуживания срока годности', [
                'timestamp' => now()->toIso8601String(),
            ]);
        } catch (\Exception $e) {
            Log::error('Ошибка при ежедневном обслуживании срока годности', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            $this->release(60); // Retry after 60 seconds
        }
    }
}
