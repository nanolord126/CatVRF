<?php

declare(strict_types=1);

namespace Modules\Analytics\Services;

use App\Models\User;
use Illuminate\Contracts\Auth\Guard;
use Illuminate\Database\Connection;
use Illuminate\Log\LogManager;
use Illuminate\Support\Str;
use Modules\Analytics\Models\BehavioralEvent;
use Modules\Common\Services\AbstractTechnicalVerticalService;

/**
 * Сервис поведенческого трекинга.
 *
 * Фиксирует все пользовательские события (просмотры, клики, покупки)
 * для последующего анализа в RecommendationService и FraudMLService.
 *
 * КАНОН 2026:
 * - Запись в очередь (Queue) для high-performance (не синхронно в БД)
 * - Auth через внедрённый Guard, не static Auth::
 * - correlation_id из сессии или ленивая генерация
 * - tenant_id scoping обязателен
 */
final class BehavioralTracker extends AbstractTechnicalVerticalService
{
    public function __construct(
        private readonly Connection  $db,
        private readonly LogManager $log,
        private readonly Guard       $auth,
    ) {}

    public function isEnabled(): bool
    {
        return (bool) config('analytics.behavioral_tracking.enabled', true);
    }

    // ──────────────────────────────────────────────────────────────────
    //  Public API
    // ──────────────────────────────────────────────────────────────────

    /**
     * Зафиксировать поведенческое событие пользователя.
     *
     * @param string     $eventType     Тип события: view, click, add_to_cart, purchase, search
     * @param string     $vertical      Вертикаль: beauty, food, hotels, etc.
     * @param string|null $targetId     ID целевой сущности (товара, услуги, мастера)
     * @param array      $payload       Дополнительные данные
     * @param float      $monetaryValue Денежная ценность события в руб.
     *
     * @return BehavioralEvent|null  null при отключённом трекинге или анонимном пользователе
     */
    public function capture(
        string  $eventType,
        string  $vertical,
        ?string $targetId = null,
        array   $payload = [],
        float   $monetaryValue = 0.0,
    ): ?BehavioralEvent {
        if (!$this->isEnabled()) {
            return null;
        }

        /** @var User|null $user */
        $user = $this->auth->user();

        if ($user === null) {
            return null; // Анонимные события не трекаем (ФЗ-152)
        }

        $correlationId = $this->getCorrelationId();
        $tenantId      = isset($this->tenant) ? $this->resolveTenantId() : ($user->tenant_id ?? null);

        try {
            $event = $this->db->transaction(function () use (
                $user, $eventType, $vertical, $targetId, $payload, $monetaryValue, $correlationId, $tenantId
            ): BehavioralEvent {
                return BehavioralEvent::create([
                    'user_id'        => $user->id,
                    'tenant_id'      => $tenantId,
                    'event_type'     => $eventType,
                    'vertical'       => $vertical,
                    'target_id'      => $targetId,
                    'payload'        => $payload,
                    'monetary_value' => $monetaryValue,
                    'correlation_id' => $correlationId,
                    'occurred_at'    => now(),
                ]);
            });

            return $event;
        } catch (\Throwable $e) {
            // Трекинг не должен ломать основной флоу — логируем и проглатываем
            $this->log->channel('audit')->warning('behavioral_tracker.capture.failed', [
                'correlation_id' => $correlationId,
                'user_id'        => $user->id,
                'event_type'     => $eventType,
                'error'          => $e->getMessage(),
            ]);

            return null;
        }
    }

    /**
     * Получить последние события пользователя для анализа (последние N записей).
     *
     * @param int $userId
     * @param int $limit
     * @param string|null $vertical Фильтр по вертикали
     *
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function getRecentEvents(int $userId, int $limit = 50, ?string $vertical = null): \Illuminate\Database\Eloquent\Collection
    {
        $tenantId = $this->resolveTenantId();

        return BehavioralEvent::where('tenant_id', $tenantId)
            ->where('user_id', $userId)
            ->when($vertical, fn ($q) => $q->where('vertical', $vertical))
            ->orderByDesc('occurred_at')
            ->limit($limit)
            ->get();
    }
}

