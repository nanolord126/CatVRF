<?php

declare(strict_types=1);

namespace App\Domains\Garden\SubVerticals\Gardening\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\SerializesModels;
use Carbon\Carbon;
use Illuminate\Support\Str;
use Illuminate\Contracts\Queue\ShouldQueue;
use Psr\Log\LoggerInterface;
use App\Domains\Gardening\Models\GardenPlant;

final class GardeningSeasonalSyncJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public array $backoff = [60, 300, 900];
    public int $tries = 3;
    public int $timeout = 300;

    public function __construct(
        private readonly LoggerInterface $logger,
        private readonly int $tenantId,
        private readonly ?string $correlationId = null,
    ) {
    }

    private function getCorrelationId(): string
    {
        return $this->correlationId ?? (string) Str::uuid();
    }

    public function tags(): array
    {
        return ['gardening', 'seasonal', 'tenant:' . $this->tenantId, 'cid:' . $this->getCorrelationId()];
    }

    public function handle(): void
    {
        $this->logger->info('SeasonalSyncJob STARTED', [
            'tenant_id' => $this->tenantId,
            'cid' => $this->correlationId,
            'time' => Carbon::now()->toDateTimeString(),
            'correlation_id' => $this->correlationId,
        ]);

        try {
            $now = Carbon::now();
            $month = $now->month;

            // 1. Fetch All Active Plants in this Tenant
            $plants = GardenPlant::where('tenant_id', $this->tenantId)
                ->with('product')
                ->get();

            if ($plants->isEmpty()) {
                $this->logger->info('SeasonalSync: No plants found.', ['tid' => $this->tenantId]);
                return;
            }

            $countUpdated = 0;
            $countActive = 0;

            foreach ($plants as $plant) {
                $needsUpdate = false;
                $originalTags = (array) $plant->tags;
                $newTags = $originalTags;
                $actions = (array) ($plant->care_calendar['actions'] ?? []);

                // A) Auto-Tag "In Season" status based on care calendar
                if (isset($actions[$month])) {
                    $newTags[] = 'current_action_' . Str::slug($actions[$month]);
                    $needsUpdate = true;
                }

                // B) Stock Check Logic (Inventory Integration)
                if ($plant->is_seedling && Carbon::parse($plant->sowing_start)->month === $month) {
                    $newTags[] = 'fresh_seedling_stock';
                    $needsUpdate = true;
                }

                // C) Auto-Disable if Out of Season (Extreme Cold Check for outdoor plants)
                if (($month >= 10 || $month <= 3) && $plant->hardiness_zone <= 5) {
                    $newTags[] = 'is_dormant';
                    $needsUpdate = true;
                }

                if ($needsUpdate) {
                    $plant->update([
                        'tags' => array_unique($newTags),
                        'correlation_id' => $this->correlationId,
                    ]);
                    $countUpdated++;
                }

                $countActive++;
            }

            $this->logger->info('SeasonalSyncJob COMPLETED SUCCESSFULLY', [
                'tenant_id' => $this->tenantId,
                'cid' => $this->correlationId,
                'processed' => $countActive,
                'modified' => $countUpdated,
                'execution_time' => defined('LARAVEL_START') ? microtime(true) - LARAVEL_START : 0,
                'correlation_id' => $this->correlationId,
            ]);
        } catch (\Throwable $e) {
            $this->logger->error('SeasonalSyncJob FAILED CRITICALLY', [
                'tenant_id' => $this->tenantId,
                'cid' => $this->correlationId,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'correlation_id' => $this->correlationId,
            ]);

            $this->fail($e);
        }
    }

    public function failed(\Throwable $exception): void
    {
        $this->logger->error('gardening job failed', [
            'error' => $exception->getMessage(),
        ]);
    }
}