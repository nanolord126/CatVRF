<?php

declare(strict_types=1);

namespace Modules\Commissions\Interfaces\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\Event;
use Modules\Commissions\Domain\Repositories\CommissionRuleRepositoryInterface;
use Modules\Commissions\Domain\Repositories\CommissionTransactionRepositoryInterface;
use Modules\Commissions\Infrastructure\Persistence\EloquentCommissionRuleRepository;
use Modules\Commissions\Infrastructure\Persistence\EloquentCommissionTransactionRepository;
use Modules\Commissions\Domain\Events\CommissionCalculated;
use App\Listeners\SendCommissionNotification; // Предполагаемый слушатель

final class CommissionServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->bind(
            CommissionRuleRepositoryInterface::class,
            EloquentCommissionRuleRepository::class
        );

        $this->app->bind(
            CommissionTransactionRepositoryInterface::class,
            EloquentCommissionTransactionRepository::class
        );

        $this->mergeConfigFrom(
            __DIR__ . '/../../config/commissions.php', 'commissions'
        );
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        $this->loadRoutesFrom(__DIR__ . '/../routes/api.php');
        
        $this->publishes([
            __DIR__.'/../../config/commissions.php' => config_path('commissions.php'),
        ], 'config');

        // Регистрация слушателей событий
        Event::listen(
            CommissionCalculated::class,
            // Здесь можно указать несколько слушателей
            // SendCommissionNotification::class,
            // UpdateAnalyticsData::class,
        );
    }
}
