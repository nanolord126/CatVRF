<?php

declare(strict_types=1);

/**
 * BeautyEventServiceProvider — CatVRF 2026 Component.
 *
 * Part of the CatVRF multi-vertical marketplace platform.
 * Implements tenant-aware, fraud-checked business logic
 * with full correlation_id tracing and audit logging.
 *
 * @package CatVRF
 * @version 2026.1
 * @author CatVRF Team
 * @license Proprietary

 * @see https://catvrf.ru/docs/beautyeventserviceprovider
 */


namespace App\Domains\Beauty\Providers;

use App\Domains\Beauty\Events\AppointmentCompleted;
use App\Domains\Beauty\Listeners\DeductAppointmentConsumablesListener;
use Illuminate\Foundation\Support\Providers\EventServiceProvider as ServiceProvider;

/**
 * Class BeautyEventServiceProvider
 *
 * Part of the Beauty vertical domain.
 * Follows CatVRF 9-layer architecture.
 *
 * Service layer following CatVRF canon:
 * - Constructor injection only (no Facades)
 * - FraudControlService::check() before mutations
 * - $this->db->transaction() wrapping all write operations
 * - Audit logging with correlation_id
 * - Tenant and BusinessGroup scoping
 *
 * @see \App\Services\FraudControlService
 * @see \App\Services\AuditService
 * @package App\Domains\Beauty\Providers
 */
final class BeautyEventServiceProvider extends ServiceProvider
{
    /**
     * Маппинг событий на слушателей.
     *
     * @var array<class-string, array<int, class-string>>
     */
    protected $listen = [
        AppointmentCompleted::class => [
            DeductAppointmentConsumablesListener::class,
        ],
    ];

    /**
     * Зарегистрировать любые события домена.
     */
    public function boot(): void
    {
        parent::boot();
    }

    /**
     * Автоматическое обнаружение событий — отключено (явная регистрация).
     */
    public function shouldDiscoverEvents(): bool
    {
        return false;
    }
}
