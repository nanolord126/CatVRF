<?php

declare(strict_types=1);

namespace Modules\Recommendation\Application\Console\Commands;

use Illuminate\Console\Command;
use Modules\Recommendation\Domain\Repositories\RecommendationRepositoryInterface;

final class EvaluateRecommendationModel extends Command
{
    protected $signature = 'recommendation:evaluate {model_version : Model version to evaluate} {--scenario=home_feed : Scenario to evaluate}';
    protected $description = 'Evaluate offline metrics for a recommendation model';

    public function __construct(
        private readonly RecommendationRepositoryInterface $repository,
    ) {
        parent::__construct();
    }

    public function handle(): int
    {
        $modelVersion = $this->argument('model_version');
        $scenario = $this->option('scenario');

        $this->info("Evaluating model: {$modelVersion} for scenario: {$scenario}");

        $metrics = $this->repository->getOfflineMetrics($modelVersion, $scenario);

        if (empty($metrics)) {
            $this->warn("No metrics found for model {$modelVersion}");
            return self::FAILURE;
        }

        $this->table(['Metric', 'Value'], array_map(fn($k, $v) => [$k, round($v, 4)], array_keys($metrics), $metrics));

        $this->info("\nEvaluation completed");

        return self::SUCCESS;
    }
}
