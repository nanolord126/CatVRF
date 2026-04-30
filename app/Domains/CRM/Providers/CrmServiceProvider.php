<?php

declare(strict_types=1);

namespace App\Domains\CRM\Providers;

use App\Domains\CRM\Events\OrderCreatedForCrm;
use App\Domains\CRM\Listeners\CreateDealFromOrder;
use Illuminate\Foundation\Support\Providers\EventServiceProvider as ServiceProvider;

/**
 * CrmServiceProvider — сервис-провайдер CRM модуля.
 * Регистрирует события и слушатели для интеграции.
 * Канон CatVRF 2026 — PRODUCTION MANDATORY.
 */
final class CrmServiceProvider extends ServiceProvider
{
    protected $listen = [
        OrderCreatedForCrm::class => [
            CreateDealFromOrder::class,
        ],
    ];

    public function register(): void
    {
        // Регистрация сервисов через IoC контейнер
        $this->app->singleton(\App\Domains\CRM\Services\PipelineService::class);
        $this->app->singleton(\App\Domains\CRM\Services\DealService::class);
        $this->app->singleton(\App\Domains\CRM\Services\TaskService::class);
        $this->app->singleton(\App\Domains\CRM\Services\OrderToCrmIntegrationService::class);
    }

    public function boot(): void
    {
        // Загрузка миграций, роутов и т.д. если необходимо
        $this->loadMigrationsFrom(database_path('migrations'));
    }
}
