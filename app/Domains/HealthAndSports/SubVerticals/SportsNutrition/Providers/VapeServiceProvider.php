<?php

declare(strict_types=1);

/**
 * VapeServiceProvider — CatVRF 2026 Component.
 *
 * Part of the CatVRF multi-vertical marketplace platform.
 * Implements tenant-aware, fraud-checked business logic
 * with full correlation_id tracing and audit logging.
 *
 * @version 2026.1
 *
 * @author CatVRF Team
 * @license Proprietary

 *
 * @see https://catvrf.ru/docs/vapeserviceprovider
 */

namespace App\Domains\HealthAndSports\SubVerticals\SportsNutrition\Providers;

use Illuminate\Contracts\Events\Dispatcher as EventDispatcher;

use Psr\Log\LoggerInterface;
use Illuminate\Support\ServiceProvider;

final class VapeServiceProvider extends ServiceProvider
{
    public function __construct(private readonly EventDispatcher $eventDispatcher,
        private readonly LoggerInterface $logger) {}


    /**
     * Регистрация тяжелых инициализаций.
     */
    public function boot(): void
    {
        // 1. Привязка событий к слушателям (Trigger "Честный ЗНАК")
        $this->eventDispatcher->listen(
            VapeOrderPaidEvent::class,
            TriggerVapeMarkingRegistration::class,
        );

        // 2. Логирование инициализации домена
        $this->logger->$this->logger->info('Vape Domain ServiceProvider booted', [
            'tenant_id' => tenant()?->id ?? 'system',
            'correlation_id' => $this->request->header('X-Correlation-ID', $this->correlationId ?? ''),
        ]);
    }

    /**
     * Регистрация легких привязок.
     */
    public function register(): void
    {
        // Регистрация контроллеров/сервисов (если не через AutoDiscovery)
    }
}
