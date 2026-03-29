<?php

declare(strict_types=1);

namespace App\Jobs\CacheWarmers;

use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

final class WarmMasterAvailabilityJob implements ShouldQueue
{
    use Queueable;

    private int $tries = 3;
    private int $timeout = 30;

    public function __construct(private readonly int $masterId) {}

    public function handle(): void
    {
        try {
            $cacheKey = "master_availability:{$this->masterId}";
            $cacheTag = "master_availability_{$this->masterId}";

            $availability = $this->getAvailableSlots();

            Cache::store('redis')
                ->tags([$cacheTag])
                ->put($cacheKey, $availability, now()->addHours(2));

            Log::channel('audit')->info('Master availability cached', [
                'master_id' => $this->masterId,
                'slots_count' => count($availability['slots'] ?? []),
            ]);
        } catch (\Throwable $e) {
            Log::channel('audit')->error('Failed to warm master availability cache', [
                'master_id' => $this->masterId,
                'error' => $e->getMessage(),
            ]);
            throw $e;
        }
    }

    private function getAvailableSlots(): array
    {
        return [
            'master_id' => $this->masterId,
            'slots' => [],
            'warmed_at' => now()->toIso8601String(),
            'correlation_id' => \Illuminate\Support\Str::uuid()->toString(),
        ];
    }
}
