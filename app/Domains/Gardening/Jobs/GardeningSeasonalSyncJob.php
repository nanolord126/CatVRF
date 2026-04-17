<?php
declare(strict_types=1);

namespace App\Domains\Gardening\Jobs;
use Illuminate\Bus\Queueable;
use Illuminate\Queue\InteractsWithQueue;

use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\SerializesModels;




use Carbon\Carbon;
use Illuminate\Support\Str;
use Psr\Log\LoggerInterface;
final class GardeningSeasonalSyncJob
{


    use \Illuminate\Foundation\Bus\Dispatchable, \Illuminate\Queue\InteractsWithQueue, \Illuminate\Bus\Queueable, \Illuminate\Queue\SerializesModels;

        private readonly string $correlationId;

        /**
         * Create a new job instance.
         */
        public function __construct(
            private readonly int $tenantId,
            ?string $correlationId = null,
        ) {
            $this->correlationId = $correlationId ?? (string) Str::uuid();
        }

        /**
         * Execute the job logic across all relevant plant entities.
         */
        public function handle(LoggerInterface $logger): void
        {
            $logger->info('SeasonalSyncJob STARTED', [
                'tenant_id' => $this->tenantId,
                'cid' => $this->correlationId,
                'time' => Carbon::now()->toDateTimeString(),
                'correlation_id' => $this->correlationId,
            ]);

            try {
                $month = Carbon::now()->month;
                $day = Carbon::now()->day;

                // 1. Fetch All Active Plants in this Tenant
                $plants = GardenPlant::where('tenant_id', $this->tenantId)
                    ->with('product') // Product has common stock data
                    ->get();

                if ($plants->isEmpty()) {
                    $logger->info('SeasonalSync: No plants found.', ['tid' => $this->tenantId]);
                    return;
                }

                $countActive = 0;
                $countUpdated = 0;

                foreach ($plants as $plant) {
                    $needsUpdate = false;
                    $originalTags = (array) $plant->tags;
                    $newTags = $originalTags;

                    // A) Auto-Tag "In Season" status based on care calendar
                    // care_calendar is JSONB with "actions" like ['1' => 'Pruning', '3' => 'Sowing']
                    $actions = (array) ($plant->care_calendar['actions'] ?? []);

                    if (isset($actions[(string)$month])) {
                        $newTags[] = 'current_action_' . Str::slug($actions[(string)$month]);
                        $needsUpdate = true;
                    }

                    // B) Stock Check Logic (Inventory Integration)
                    // If seedling and in sowing month, flag as "fresh_seedling"
                    if ($plant->is_seedling && Carbon::parse($plant->sowing_start)->month === $month) {
                        $newTags[] = 'fresh_seedling_stock';
                        $needsUpdate = true;
                    }

                    // C) Auto-Disable if Out of Season (Extreme Cold Check for outdoor plants)
                    // If Month > Oct and < March, mark outdoor zone 1-5 plants as "DORMANT"
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

                $logger->info('SeasonalSyncJob COMPLETED SUCCESSFULLY', [
                    'tenant_id' => $this->tenantId,
                    'cid' => $this->correlationId,
                    'processed' => $countActive,
                    'modified' => $countUpdated,
                    'execution_time' => defined('LARAVEL_START') ? microtime(true) - LARAVEL_START : 0,
                    'correlation_id' => $this->correlationId,
                ]);

            } catch (\Throwable $e) {
                $logger->error('SeasonalSyncJob FAILED CRITICALLY', [
                    'tenant_id' => $this->tenantId,
                    'cid' => $this->correlationId,
                    'error' => $e->getMessage(),
                    'trace' => $e->getTraceAsString(),
                    'correlation_id' => $this->correlationId,
                ]);

                $this->fail($e);
            }
        }

        /**
         * Unique tags for identifying this job in the queue dashboard.
         */
        public function tags(): array
        {
            return ['gardening', 'seasonal', 'tenant:' . $this->tenantId, 'cid:' . $this->correlationId];
        }
}

