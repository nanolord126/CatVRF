<?php

namespace App\Services\Infrastructure;

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;

/**
 * Service for optimizing DB Performance for 2026 Scale.
 * @version 1.0 (Zero Trust & Performance Edition)
 */
final readonly class DatabaseOptimizationService
{
    /**
     * Enforce strict indexes on critical cross-vertical fields.
     */
    public function ensureHighPerformanceIndexes(): void
    {
        $tables = ['properties', 'action_audits', 'b2b_orders', 'taxi_trips'];

        foreach ($tables as $table) {
            if (Schema::hasTable($table)) {
                Schema::table($table, function (Blueprint $table) {
                    // Composite index for fast tenant scoped correlation lookups
                    $table->index(['tenant_id', 'correlation_id'], 'idx_tenant_correlation_2026');
                });
            }
        }
        
        DB::statement('ANALYZE');
    }

    /**
     * ML Embedding Cache strategy
     */
    public function optimizeSearchIndices(): void
    {
        // Обновить индексы в Typesense для гибридного поиска
        try {
            // Переиндексировать все активные marketplace entities
            \Artisan::call('scout:import', [
                'model' => 'App\\Models\\MarketplaceVerticals',
            ]);

            // Переиндексировать B2B товары
            \Artisan::call('scout:import', [
                'model' => 'App\\Models\\B2B\\B2BProduct',
            ]);

            // Обновить embedding cache в Redis для быстрых рекомендаций
            $this->refreshEmbeddingCache();

            \Log::info('Search indices optimized successfully');
        } catch (\Exception $e) {
            \Log::error('Failed to optimize search indices', [
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Обновить embedding cache для рекомендаций.
     */
    private function refreshEmbeddingCache(): void
    {
        $job = new \App\Jobs\AI\DailyB2BEmbeddingUpdateJob();
        $job->dispatch();
    }
}
