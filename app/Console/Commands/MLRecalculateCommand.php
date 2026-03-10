<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Services\AI\MLHelperService;
use App\Models\Tenant;
use App\Models\AuditLog;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Log;
use Throwable;
use Exception;

class MLRecalculateCommand extends Command
{
    protected $signature = 'ml:recalculate {--tenant= : Specific tenant ID to recalculate}';
    protected $description = 'Recalculate demand and fraud scores per tenant (Production 2026)';

    public function handle(MLHelperService $ml): int
    {
        try {
            $correlationId = Str::uuid()->toString();
            $startTime = microtime(true);
            $specificTenant = $this->option('tenant');

            Log::channel('commands')->info('MLRecalculateCommand started', [
                'correlation_id' => $correlationId,
                'specific_tenant' => $specificTenant,
                'timestamp' => now()->toIso8601String(),
            ]);

            $query = Tenant::query();
            if ($specificTenant) {
                $query->where('id', $specificTenant);
            }
            $tenants = $query->get();

            $this->info("Recalculating ML models for {$tenants->count()} tenant(s)...");

            $successCount = 0;
            $failedCount = 0;
            $predictions = [];

            foreach ($tenants as $tenant) {
                try {
                    $prediction = $ml->predictDemand($tenant->type, $tenant->id);
                    $score = ($prediction['prediction_score'] ?? 0) * 100;

                    // Логирование предсказания
                    AuditLog::create([
                        'action' => 'ml.demand_prediction_calculated',
                        'description' => "Пересчитан ML скор спроса для тенанта {$tenant->id}",
                        'model_type' => 'Tenant',
                        'model_id' => $tenant->id,
                        'correlation_id' => $correlationId,
                        'metadata' => [
                            'tenant_type' => $tenant->type,
                            'demand_score' => round($score, 2),
                            'prediction_raw' => $prediction,
                        ],
                    ]);

                    $predictions[] = [
                        'tenant_id' => $tenant->id,
                        'score' => $score,
                    ];

                    $this->line("▪ Tenant: {$tenant->id} ({$tenant->type}) | Demand Score: " . round($score, 2) . "%");
                    $successCount++;

                } catch (Exception $e) {
                    $failedCount++;
                    Log::channel('commands')->error('ML prediction failed for tenant', [
                        'tenant_id' => $tenant->id,
                        'error' => $e->getMessage(),
                        'correlation_id' => $correlationId,
                    ]);

                    $this->error("✗ Tenant {$tenant->id}: {$e->getMessage()}");
                }
            }

            // Финальное логирование
            AuditLog::create([
                'action' => 'ml.recalculation_completed',
                'description' => 'Пересчет ML моделей по всем тенантам завершен',
                'correlation_id' => $correlationId,
                'metadata' => [
                    'total_tenants' => $tenants->count(),
                    'successful' => $successCount,
                    'failed' => $failedCount,
                    'predictions' => $predictions,
                ],
            ]);

            $duration = round(microtime(true) - $startTime, 2);
            $avgScore = !empty($predictions) 
                ? round(array_sum(array_column($predictions, 'score')) / count($predictions), 2)
                : 0;

            $this->info("━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━");
            $this->info("✓ Successful: {$successCount}/{$tenants->count()}");
            $this->error("✗ Failed: {$failedCount}");
            $this->comment("📊 Average Demand Score: {$avgScore}%");
            $this->comment("⏱ Duration: {$duration}s");
            $this->info("━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━");

            Log::channel('commands')->info('MLRecalculateCommand completed', [
                'correlation_id' => $correlationId,
                'total_tenants' => $tenants->count(),
                'successful' => $successCount,
                'failed' => $failedCount,
                'average_score' => $avgScore,
                'duration_seconds' => $duration,
            ]);

            return self::SUCCESS;

        } catch (Throwable $e) {
            Log::channel('commands')->critical('MLRecalculateCommand failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            \Sentry\captureException($e);

            $this->error("Command failed: {$e->getMessage()}");
            return self::FAILURE;
        }
    }
}
