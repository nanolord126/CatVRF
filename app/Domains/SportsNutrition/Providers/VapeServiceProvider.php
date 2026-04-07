<?php declare(strict_types=1);

/**
 * VapeServiceProvider — CatVRF 2026 Component.
 *
 * Part of the CatVRF multi-vertical marketplace platform.
 * Implements tenant-aware, fraud-checked business logic
 * with full correlation_id tracing and audit logging.
 *
 * @package CatVRF
 * @version 2026.1
 * @author CatVRF Team
 * @license Proprietary

 * @see https://catvrf.ru/docs/vapeserviceprovider
 */


namespace App\Domains\SportsNutrition\Providers;


use Psr\Log\LoggerInterface;
use Illuminate\Http\Request;

use Illuminate\Support\ServiceProvider;

final class VapeServiceProvider extends ServiceProvider
{
    public function __construct(
        private readonly LoggerInterface $logger) {}


    /**
         * Регистрация тяжелых инициализаций.
         */
        public function boot(): void
        {
            // 1. Привязка событий к слушателям (Trigger "Честный ЗНАК")
            Event::listen(
                VapeOrderPaidEvent::class,
                TriggerVapeMarkingRegistration::class,
            );

            // 2. Логирование инициализации домена
            $this->logger->info('Vape Domain ServiceProvider booted', [
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
