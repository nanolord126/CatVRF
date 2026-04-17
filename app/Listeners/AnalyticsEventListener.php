<?php declare(strict_types=1);

namespace App\Listeners;
use Illuminate\Bus\Queueable;
use Illuminate\Foundation\Bus\Dispatchable;

use App\Events\OrderCompletedEvent;
use App\Events\PaymentProcessedEvent;
use App\Events\ReviewSubmittedEvent;
use App\Events\UserRegisteredEvent;
use App\Services\Analytics\AdvancedAnalyticsService;
use App\Services\Analytics\SegmentationService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;


use Illuminate\Support\Str;
use Illuminate\Log\LogManager;
use Illuminate\Cache\CacheManager;

final class AnalyticsEventListener implements ShouldQueue
{

    public int $tries = 3;
    public int $timeout = 300; // 5 минут

    public function __construct(
        private readonly AdvancedAnalyticsService $analyticsService,
        private readonly SegmentationService $segmentationService,
        private readonly LogManager $logger,
        private readonly CacheManager $cache,
    )
    {
        // Implementation required by canon
    }

    /**
     * Handle the event.
     *
     * @param object $event
     * @return void
     */
    public function handle(object $event): void
    {
        $correlationId = $event->correlationId ?? (string) Str::uuid();
        $this->logger->channel('audit')->info('Analytics event received for handling', [
            'event_class' => get_class($event),
            'correlation_id' => $correlationId,
        ]);

        if ($event instanceof OrderCompletedEvent) {
            $this->handleOrderCompleted($event);
        } elseif ($event instanceof PaymentProcessedEvent) {
            $this->handlePaymentProcessed($event);
        } elseif ($event instanceof UserRegisteredEvent) {
            $this->handleUserRegistered($event);
        } elseif ($event instanceof ReviewSubmittedEvent) {
            $this->handleReviewSubmitted($event);
        }
    }

    /**
     * Обработчик события завершения заказа
     */
    public function handleOrderCompleted(OrderCompletedEvent $event): void
    {
        $tenantId = $event->order->tenant_id;
        $correlationId = $event->correlationId ?? Str::uuid()->toString();

        try {
            $this->cache->forget("analytics:metrics:{$tenantId}:revenue:*");
            $this->cache->forget("analytics:metrics:{$tenantId}:orders:*");
            $this->cache->forget("analytics:metrics:{$tenantId}:aov:*");
            $this->cache->forget("analytics:metrics:{$tenantId}:conversion:*");
            $this->cache->forget("analytics:forecast:{$tenantId}:*");
            $this->cache->forget("dashboard:layout:{$tenantId}:*");
            $this->cache->forget("revenue_chart:{$tenantId}");
            $this->cache->forget("stats_overview:{$tenantId}");

            $this->segmentationService->segmentCustomers($tenantId, ['by_value' => true, 'by_behavior' => true], ['correlation_id' => $correlationId]);

            $this->logger->channel('audit')->info('Analytics cache invalidated after order completed', [
                'tenant_id' => $tenantId,
                'order_id' => $event->order->id,
                'correlation_id' => $correlationId,
                'amount' => $event->order->total_price,
                'timestamp' => now()->toIso8601String()
            ]);
        } catch (\Throwable $e) {
            $this->logger->channel('analytics_errors')->error('Failed to invalidate analytics cache', [
                'tenant_id' => $tenantId,
                'order_id' => $event->order->id,
                'correlation_id' => $correlationId,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            throw $e;
        }
    }

    /**
     * Обработчик события обработки платежа
     */
    public function handlePaymentProcessed(PaymentProcessedEvent $event): void
    {
        $tenantId = $event->payment->tenant_id;
        $correlationId = $event->correlationId ?? Str::uuid()->toString();

        try {
            if ($event->payment->status === 'captured' || $event->payment->status === 'completed') {
                $this->cache->forget("analytics:metrics:{$tenantId}:revenue:*");
                $this->cache->forget("analytics:metrics:{$tenantId}:conversion:*");
                $this->cache->forget("stats_overview:{$tenantId}");
                $this->cache->forget("revenue_chart:{$tenantId}");

                if ($event->payment->user_id) {
                    $this->cache->forget("user_segment:{$event->payment->user_id}");
                }
            }

            $this->logger->channel('audit')->info('Analytics cache invalidated after payment processed', [
                'tenant_id' => $tenantId,
                'payment_id' => $event->payment->id,
                'correlation_id' => $correlationId,
                'status' => $event->payment->status,
                'amount' => $event->payment->amount,
                'timestamp' => now()->toIso8601String()
            ]);
        } catch (\Throwable $e) {
            $this->logger->channel('analytics_errors')->error('Failed to handle payment analytics event', [
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
     */
    public function handleUserRegistered(UserRegisteredEvent $event): void
    {
        $tenantId = $event->user->tenant_id ?? null;
        $correlationId = $event->correlationId ?? Str::uuid()->toString();

        try {
            if ($tenantId) {
                $this->cache->forget("analytics:metrics:{$tenantId}:new_users:*");
                $this->cache->forget("stats_overview:{$tenantId}");
                $this->segmentationService->segmentCustomers($tenantId, ['by_value' => true, 'by_behavior' => true], ['correlation_id' => $correlationId]);
            }

            $this->logger->channel('audit')->info('Analytics updated after user registration', [
                'tenant_id' => $tenantId,
                'user_id' => $event->user->id,
                'correlation_id' => $correlationId,
                'email' => $event->user->email,
                'timestamp' => now()->toIso8601String()
            ]);
        } catch (\Throwable $e) {
            $this->logger->channel('analytics_errors')->error('Failed to handle user registration analytics', [
                'user_id' => $event->user->id,
                'correlation_id' => $correlationId,
                'error' => $e->getMessage()
            ]);
            throw $e;
        }
    }

    /**
     * Обработчик события отправки отзыва
     */
    public function handleReviewSubmitted(ReviewSubmittedEvent $event): void
    {
        $tenantId = $event->review->tenant_id;
        $correlationId = $event->correlationId ?? Str::uuid()->toString();

        try {
            $this->cache->forget("ratings:{$tenantId}:product:{$event->review->product_id}");
            $this->cache->forget("ratings:{$tenantId}:seller:{$event->review->seller_id}");
            $this->cache->forget("ratings:summary:{$tenantId}");
            $this->cache->forget("analytics:metrics:{$tenantId}:ratings:*");

            $this->logger->channel('audit')->info('Analytics cache invalidated after review submitted', [
                'tenant_id' => $tenantId,
                'review_id' => $event->review->id,
                'correlation_id' => $correlationId,
                'rating' => $event->review->rating,
                'timestamp' => now()->toIso8601String()
            ]);
        } catch (\Throwable $e) {
            $this->logger->channel('analytics_errors')->error('Failed to handle review analytics event', [
                'tenant_id' => $tenantId,
                'review_id' => $event->review->id,
                'correlation_id' => $correlationId,
                'error' => $e->getMessage()
            ]);
            throw $e;
        }
    }

    /**
     * Зарегистрировать слушателей события.
     * Этот метод больше не нужен, если вы используете автообнаружение событий Laravel.
     * Оставьте его, если у вас есть особая логика регистрации.
     */
    public static function registerListeners($events): void
    {
        $events->listen(
            OrderCompletedEvent::class,
            [self::class, 'handleOrderCompleted']
        );

        $events->listen(
            PaymentProcessedEvent::class,
            [self::class, 'handlePaymentProcessed']
        );

        $events->listen(
            UserRegisteredEvent::class,
            [self::class, 'handleUserRegistered']
        );

        $events->listen(
            ReviewSubmittedEvent::class,
            [self::class, 'handleReviewSubmitted']
        );
    }
}
