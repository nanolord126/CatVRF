<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\OfflineSync;
use Illuminate\Support\Str;

/**
 * Robust Offline-to-Cloud Sync for 2026 Fleet Devices.
 * Final production-ready service with strict types and readonly support.
 */
final readonly class OfflineSyncService
{
    public function stage(string $model, array $payload, ?int $userId = null): OfflineSync
    {
        return OfflineSync::create([
            'model_type' => $model,
            'payload' => $payload,
            'user_id' => $userId,
            'correlation_id' => (string) Str::uuid(),
            'status' => 'pending'
        ]);
    }

    public function sync(OfflineSync $sync): void
    {
        try {
            $class = $this->resolveModelClass($sync->model_type);
            $class::create($sync->payload);
            $sync->update(['status' => 'synced']);
        } catch (\Throwable $e) {
            $sync->update(['status' => 'failed', 'error' => $e->getMessage()]);
            throw $e;
        }
    }

    private function resolveModelClass(string $type): string
    {
        $m = "App\\Models\\Marketplace" . Str::studly($type);
        return class_exists($m) ? $m : "App\\Models\\" . Str::studly($type);
    }
}
