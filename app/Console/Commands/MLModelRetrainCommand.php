<?php declare(strict_types=1);

namespace App\Console\Commands;

use App\Domains\FraudML\Jobs\MLModelRetrainJob;
use App\Domains\FraudML\Jobs\PromoteShadowModelJob;
use Illuminate\Console\Command;
use Ramsey\Uuid\Uuid;

/**
 * MLModelRetrainCommand — manual trigger for ML model retraining
 * 
 * Usage:
 * php artisan ml:retrain              - Start new model retrain
 * php artisan ml:retrain --promote    - Promote shadow model to active
 * php artisan ml:retrain --force      - Force retrain (skip quota check)
 * 
 * @author CatVRF Team
 * @version 2026.04.17
 */
final class MLModelRetrainCommand extends Command
{
    protected $signature = 'ml:retrain {--promote : Promote shadow model to active} {--force : Force retrain without quota check}';
    protected $description = 'Train or promote FraudML models with shadow mode and validation';

    public function handle(): int
    {
        $correlationId = Uuid::uuid4()->toString();

        if ($this->option('promote')) {
            return $this->promoteShadowModel($correlationId);
        }

        return $this->startRetrain($correlationId);
    }

    private function startRetrain(string $correlationId): int
    {
        $this->info('Starting ML model retrain...');
        $this->info("Correlation ID: {$correlationId}");

        if ($this->option('force')) {
            $this->warn('Force mode enabled - quota check will be skipped');
        }

        // Dispatch job to dedicated queue
        MLModelRetrainJob::dispatch($correlationId)
            ->onQueue('ml-retrain-high-priority');

        $this->info('ML Model Retrain Job dispatched to queue: ml-retrain-high-priority');
        $this->info('Monitor progress with: php artisan queue:work --queue=ml-retrain-high-priority');
        $this->info('Or check Horizon dashboard');

        return self::SUCCESS;
    }

    private function promoteShadowModel(string $correlationId): int
    {
        $this->info('Promoting shadow model to active...');
        $this->info("Correlation ID: {$correlationId}");

        PromoteShadowModelJob::dispatch($correlationId)
            ->onQueue('ml-retrain-high-priority');

        $this->info('Promote Shadow Model Job dispatched to queue: ml-retrain-high-priority');

        return self::SUCCESS;
    }
}
