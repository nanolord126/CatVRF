<?php declare(strict_types=1);

namespace App\Listeners;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

final class AnalyticsEventListener extends Model
{
    use HasFactory;

    // TODO: Проверить и восстановить содержимое класса, если оно было утеряно
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
                Cache::forget("analytics:metrics:{$tenantId}:revenue:*");
                Cache::forget("analytics:metrics:{$tenantId}:orders:*");
                Cache::forget("analytics:metrics:{$tenantId}:aov:*");
                Cache::forget("analytics:metrics:{$tenantId}:conversion:*");

                // Инвалидируем forecast-кэш
                Cache::forget("analytics:forecast:{$tenantId}:*");

                // Инвалидируем dashboard-кэш
                Cache::forget("dashboard:layout:{$tenantId}:*");

                // Инвалидируем widgets
                Cache::forget("revenue_chart:{$tenantId}");
                Cache::forget("stats_overview:{$tenantId}");

                // Пересчитываем сегменты
                $this->segmentationService->segmentCustomers($tenantId);

                Log::channel('audit')->info('Analytics cache invalidated after order completed', [
                    'tenant_id' => $tenantId,
                    'order_id' => $event->order->id,
                    'correlation_id' => $correlationId,
                    'amount' => $event->order->total_price,
                    'timestamp' => now()->toIso8601String()
                ]);

            } catch (\Throwable $e) {
                Log::channel('analytics_errors')->error('Failed to invalidate analytics cache', [
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
                    Cache::forget("analytics:metrics:{$tenantId}:revenue:*");
                    Cache::forget("analytics:metrics:{$tenantId}:conversion:*");
                    Cache::forget("stats_overview:{$tenantId}");
                    Cache::forget("revenue_chart:{$tenantId}");

                    // Пересчитываем сегмент платежеспособности пользователя
                    if ($event->payment->user_id) {
                        Cache::forget("user_segment:{$event->payment->user_id}");
                    }
                }

                Log::channel('audit')->info('Analytics cache invalidated after payment processed', [
                    'tenant_id' => $tenantId,
                    'payment_id' => $event->payment->id,
                    'correlation_id' => $correlationId,
                    'status' => $event->payment->status,
                    'amount' => $event->payment->amount,
                    'timestamp' => now()->toIso8601String()
                ]);

            } catch (\Throwable $e) {
                Log::channel('analytics_errors')->error('Failed to handle payment analytics event', [
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
                    Cache::forget("analytics:metrics:{$tenantId}:new_users:*");
                    Cache::forget("stats_overview:{$tenantId}");

                    // Пересчитываем сегменты
                    $this->segmentationService->segmentCustomers($tenantId);
                }

                Log::channel('audit')->info('Analytics updated after user registration', [
                    'tenant_id' => $tenantId,
                    'user_id' => $event->user->id,
                    'correlation_id' => $correlationId,
                    'email' => $event->user->email,
                    'timestamp' => now()->toIso8601String()
                ]);

            } catch (\Throwable $e) {
                Log::channel('analytics_errors')->error('Failed to handle user registration analytics', [
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
                Cache::forget("ratings:{$tenantId}:product:{$event->review->product_id}");
                Cache::forget("ratings:{$tenantId}:seller:{$event->review->seller_id}");
                Cache::forget("ratings:summary:{$tenantId}");

                // Инвалидируем общие метрики (средний рейтинг может измениться)
                Cache::forget("analytics:metrics:{$tenantId}:ratings:*");

                Log::channel('audit')->info('Analytics cache invalidated after review submitted', [
                    'tenant_id' => $tenantId,
                    'review_id' => $event->review->id,
                    'correlation_id' => $correlationId,
                    'rating' => $event->review->rating,
                    'timestamp' => now()->toIso8601String()
                ]);

            } catch (\Throwable $e) {
                Log::channel('analytics_errors')->error('Failed to handle review analytics event', [
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
            $events->listen(OrderCompletedEvent::class, function (OrderCompletedEvent $event) {
                $listener = app(self::class);
                $listener->handleOrderCompleted($event);
            });

            $events->listen(PaymentProcessedEvent::class, function (PaymentProcessedEvent $event) {
                $listener = app(self::class);
                $listener->handlePaymentProcessed($event);
            });

            $events->listen(UserRegisteredEvent::class, function (UserRegisteredEvent $event) {
                $listener = app(self::class);
                $listener->handleUserRegistered($event);
            });

            $events->listen(ReviewSubmittedEvent::class, function (ReviewSubmittedEvent $event) {
                $listener = app(self::class);
                $listener->handleReviewSubmitted($event);
            });
        }
}
