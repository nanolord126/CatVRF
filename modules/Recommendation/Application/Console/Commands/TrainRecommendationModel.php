<?php

declare(strict_types=1);

namespace Modules\Recommendation\Application\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Modules\Recommendation\Domain\Interfaces\MLInferenceInterface;

final class TrainRecommendationModel extends Command
{
    protected $signature = 'recommendation:train {model_type : Type of model to train (two_tower, collaborative)} {--tenant-id=1 : Tenant ID} {--config={} : Training config as JSON}';
    protected $description = 'Train a recommendation model via ML service';

    public function __construct(
        private readonly MLInferenceInterface $mlInference,
    ) {
        parent::__construct();
    }

    public function handle(): int
    {
        $modelType = $this->argument('model_type');
        $tenantId = (int) $this->option('tenant-id');
        $config = json_decode($this->option('config') ?: '{}', true);

        $this->info("Starting training for model: {$modelType}");

        $result = $this->mlInference->trainModel($modelType, $config);

        if (!$result['success']) {
            $this->error("Training failed: {$result['error']}");
            return self::FAILURE;
        }

        $this->info("Training started successfully");
        $this->info("Job ID: {$result['job_id']}");
        $this->info("Estimated time: {$result['estimated_time_minutes']} minutes");

        return self::SUCCESS;
    }
}
