<?php

declare(strict_types=1);

namespace App\Listeners;

use App\Events\OrderCompletedEvent;
use App\Events\PaymentProcessedEvent;
use App\Events\UserRegisteredEvent;
use App\Events\ReviewSubmittedEvent;
use App\Services\Analytics\AdvancedAnalyticsService;
use App\Services\Analytics\SegmentationService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

/**
 * Analytics Event Listener
 * Слушатель событий для автоматического пересчёта аналитики
 * 
 * Отвечает за:
 * - Инвалидацию кэша после значимых событий
 * - Обновление KPI в Redis
 * - Пересчёт сегментов клиентов
 * - Логирование для аудита
 * 
 * @package App\Listeners
 * @category Analytics
 */
final class AnalyticsEventListener implements ShouldQueue
{
    use InteractsWithQueue;

    public int $tries = 3;
    public int $timeout = 300; // 5 минут

    private readonly AdvancedAnalyticsService $analyticsService;
    private readonly SegmentationService $segmentationService;

    public function __construct(
        AdvancedAnalyticsService $analyticsService,
        SegmentationService $segmentationService
    ) {
        $this->analyticsService = $analyticsService;
        $this->segmentationService = $segmentationService;
    }

    /**
     * Обработчик события завершения заказа
     * Инвалидирует KPI и пересчитывает тренды
     * 
     * @param OrderCompletedEvent $event
     * @return void
     */
    public function handleOrderCompleted(OrderCompletedEvent $event): void
    {
        $tenantId = $event->order->tenant_id;
        $correlationId = $event->correlationId ?? Str::uuid()->toString();

        try {
            // Инвалидируем KPI-кэш
            $this->cache->forget("analytics:metrics:{$tenantId}:revenue:*");
            $this->cache->forget("analytics:metrics:{$tenantId}:orders:*");
            $this->cache->forget("analytics:metrics:{$tenantId}:aov:*");
            $this->cache->forget("analytics:metrics:{$tenantId}:conversion:*");

            // Инвалидируем forecast-кэш
            $this->cache->forget("analytics:forecast:{$tenantId}:*");

            // Инвалидируем dashboard-кэш
            $this->cache->forget("dashboard:layout:{$tenantId}:*");

            // Инвалидируем widgets
            $this->cache->forget("revenue_chart:{$tenantId}");
            $this->cache->forget("stats_overview:{$tenantId}");

            // Пересчитываем сегменты
            $this->segmentationService->segmentCustomers($tenantId);

            $this->log->channel('audit')->info('Analytics cache invalidated after order completed', [
                'tenant_id' => $tenantId,
                'order_id' => $event->order->id,
                'correlation_id' => $correlationId,
                'amount' => $event->order->total_price,
                'timestamp' => now()->toIso8601String()
            ]);

        } catch (\Throwable $e) {
            $this->log->channel('analytics_errors')->error('Failed to invalidate analytics cache', [
                'tenant_id' => $tenantId,
                'order_id' => $event->order->id,
                'correlation_id' => $correlationId,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            throw $e; // Перебрасываем для retry
        }
    }

    /**
     * Обработчик события обработки платежа
     * Инвалидирует KPI и пересчитывает тренды платежей
     * 
     * @param PaymentProcessedEvent $event
     * @return void
     */
    public function handlePaymentProcessed(PaymentProcessedEvent $event): void
    {
        $tenantId = $event->payment->tenant_id;
        $correlationId = $event->correlationId ?? Str::uuid()->toString();

        try {
            // Инвалидируем только если платёж успешен
            if ($event->payment->status === 'captured' || $event->payment->status === 'completed') {
                $this->cache->forget("analytics:metrics:{$tenantId}:revenue:*");
                $this->cache->forget("analytics:metrics:{$tenantId}:conversion:*");
                $this->cache->forget("stats_overview:{$tenantId}");
                $this->cache->forget("revenue_chart:{$tenantId}");

                // Пересчитываем сегмент платежеспособности пользователя
                if ($event->payment->user_id) {
                    $this->cache->forget("user_segment:{$event->payment->user_id}");
                }
            }

            $this->log->channel('audit')->info('Analytics cache invalidated after payment processed', [
                'tenant_id' => $tenantId,
                'payment_id' => $event->payment->id,
                'correlation_id' => $correlationId,
                'status' => $event->payment->status,
                'amount' => $event->payment->amount,
                'timestamp' => now()->toIso8601String()
            ]);

        } catch (\Throwable $e) {
            $this->log->channel('analytics_errors')->error('Failed to handle payment analytics event', [
                'tenant_id' => $tenantId,
                'payment_id' => $event->payment->id,
                'correlation_id' => $correlationId,
                'error' => $e->getMessage()
            ]);
            throw $e;
        }
    }

    /**
     * Обработчик события регистрации пользователя
     * Пересчитывает сегменты и инвалидирует KPI новых пользователей
     * 
     * @param UserRegisteredEvent $event
     * @return void
     */
    public function handleUserRegistered(UserRegisteredEvent $event): void
    {
        $tenantId = $event->user->tenant_id ?? null;
        $correlationId = $event->correlationId ?? Str::uuid()->toString();

        try {
            if ($tenantId) {
                // Инвалидируем метрику "новых пользователей"
                $this->cache->forget("analytics:metrics:{$tenantId}:new_users:*");
                $this->cache->forget("stats_overview:{$tenantId}");

                // Пересчитываем сегменты
                $this->segmentationService->segmentCustomers($tenantId);
            }

            $this->log->channel('audit')->info('Analytics updated after user registration', [
                'tenant_id' => $tenantId,
                'user_id' => $event->user->id,
                'correlation_id' => $correlationId,
                'email' => $event->user->email,
                'timestamp' => now()->toIso8601String()
            ]);

        } catch (\Throwable $e) {
            $this->log->channel('analytics_errors')->error('Failed to handle user registration analytics', [
                'user_id' => $event->user->id,
                'correlation_id' => $correlationId,
                'error' => $e->getMessage()
            ]);
            throw $e;
        }
    }

    /**
     * Обработчик события отправки отзыва
     * Инвалидирует рейтинги и пересчитывает тренды
     * 
     * @param ReviewSubmittedEvent $event
     * @return void
     */
    public function handleReviewSubmitted(ReviewSubmittedEvent $event): void
    {
        $tenantId = $event->review->tenant_id;
        $correlationId = $event->correlationId ?? Str::uuid()->toString();

        try {
            // Инвалидируем кэш рейтингов
            $this->cache->forget("ratings:{$tenantId}:product:{$event->review->product_id}");
            $this->cache->forget("ratings:{$tenantId}:seller:{$event->review->seller_id}");
            $this->cache->forget("ratings:summary:{$tenantId}");

            // Инвалидируем общие метрики (средний рейтинг может измениться)
            $this->cache->forget("analytics:metrics:{$tenantId}:ratings:*");

            $this->log->channel('audit')->info('Analytics cache invalidated after review submitted', [
                'tenant_id' => $tenantId,
                'review_id' => $event->review->id,
                'correlation_id' => $correlationId,
                'rating' => $event->review->rating,
                'timestamp' => now()->toIso8601String()
            ]);

        } catch (\Throwable $e) {
            $this->log->channel('analytics_errors')->error('Failed to handle review analytics event', [
                'tenant_id' => $tenantId,
                'review_id' => $event->review->id,
                'correlation_id' => $correlationId,
                'error' => $e->getMessage()
            ]);
            throw $e;
        }
    }

    /**
     * Зарегистрировать слушателей события
     * Вызывается из EventServiceProvider
     * 
     * @param \Illuminate\Events\Dispatcher $events
     * @return void
     */
    public static function registerListeners($events): void
    {
        $events->listen(OrderCompleted$this->event->class, function (OrderCompletedEvent $event) {
            $listener = app(self::class);
            $listener->handleOrderCompleted($event);
        });

        $events->listen(PaymentProcessed$this->event->class, function (PaymentProcessedEvent $event) {
            $listener = app(self::class);
            $listener->handlePaymentProcessed($event);
        });

        $events->listen(UserRegistered$this->event->class, function (UserRegisteredEvent $event) {
            $listener = app(self::class);
            $listener->handleUserRegistered($event);
        });

        $events->listen(ReviewSubmitted$this->event->class, function (ReviewSubmittedEvent $event) {
            $listener = app(self::class);
            $listener->handleReviewSubmitted($event);
        });
    }
}
