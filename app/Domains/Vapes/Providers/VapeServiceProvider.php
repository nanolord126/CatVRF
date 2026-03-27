<?php

declare(strict_types=1);

namespace App\Domains\Vapes\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\Event;
use App\Domains\Vapes\Events\VapeOrderPaidEvent;
use App\Domains\Vapes\Listeners\TriggerVapeMarkingRegistration;

/**
 * VapeServiceProvider — Production Ready 2026
 * 
 * Регистрация всех компонентов вейп-вертикали.
 * - Привязка событий (Event mapping)
 * - Регистрация маршрутов (не входит в СЛОЙ, но полезно)
 * - Канон 2026: boot() — тяжелые инициализации.
 */
final class VapeServiceProvider extends ServiceProvider
{
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
        \Illuminate\Support\Facades\Log::channel('audit')->info('Vape Domain ServiceProvider booted', [
            'tenant_id' => tenant('id') ?? 'system',
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
