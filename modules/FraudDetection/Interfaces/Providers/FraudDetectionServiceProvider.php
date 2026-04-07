<?php

declare(strict_types=1);

namespace Modules\FraudDetection\Interfaces\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\Event;
use Modules\FraudDetection\Domain\Repositories\FraudAttemptRepositoryInterface;
use Modules\FraudDetection\Infrastructure\Persistence\EloquentFraudAttemptRepository;
use Modules\FraudDetection\Domain\Services\FraudScoringServiceInterface;
use Modules\FraudDetection\Infrastructure\Services\HttpFraudScoringService;
use Modules\FraudDetection\Domain\Events\FraudDetected;
use Modules\FraudDetection\Infrastructure\Listeners\LogFraudAttemptListener;

final class FraudDetectionServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->bind(
            FraudAttemptRepositoryInterface::class,
            EloquentFraudAttemptRepository::class
        );

        $this->app->bind(
            FraudScoringServiceInterface::class,
            HttpFraudScoringService::class
        );

        $this->mergeConfigFrom(
            __DIR__ . '/../../config/frauddetection.php', 'frauddetection'
        );
    }

    public function boot(): void
    {
        $this->loadRoutesFrom(__DIR__ . '/../routes/api.php');
        
        $this->publishes([
            __DIR__.'/../../config/frauddetection.php' => config_path('frauddetection.php'),
        ], 'config');

        Event::listen(
            FraudDetected::class,
            LogFraudAttemptListener::class
        );
    }
}
