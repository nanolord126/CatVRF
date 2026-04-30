<?php

declare(strict_types=1);

namespace Modules\Supermarket\Application\Services;

use App\Traits\WithAuditLogging;
use App\Traits\WithTelemetry;
use App\Services\FraudControlService;
use Modules\Supermarket\Infrastructure\Models\Subscription;
use Modules\Supermarket\Infrastructure\Models\SupermarketOrder;
use App\Models\User;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;

final class SubscriptionNotificationService
{
    use WithAuditLogging;
    use WithTelemetry;

    private readonly FraudControlService $fraudControl;

    public function __construct(FraudControlService $fraudControl)
    {
        $this->fraudControl = $fraudControl;
    }
    public function sendSubscriptionCreated(Subscription $subscription): void
    {
        $this->withSpan(
            'subscription_notification.created',
            function () use ($subscription) {
                // Fraud check before sending notification
                $this->fraudControl->check([
                    'operation_type' => 'subscription_notification_created',
                    'vertical' => 'supermarket',
                    'user_id' => $subscription->buyer_id,
                    'subscription_id' => $subscription->id,
                    'correlation_id' => $this->generateCorrelationId(),
                ]);

                $message = "✅ Подписка оформлена!\n\n" .
                           "Частота: " . $this->humanFrequency($subscription->frequency) . "\n" .
                           "Следующая доставка: " . $subscription->next_delivery_at->format('d.m.Y') . "\n" .
                           "Сумма: {$subscription->total_amount} ₽";

                $this->sendToAllChannels($subscription->buyer, $message, 'subscription_created', [
                    'subscription_id' => $subscription->id,
                ]);

                Log::info('Subscription created notification sent', [
                    'subscription_id' => $subscription->id,
                    'buyer_id' => $subscription->buyer_id,
                ]);

                $this->logAction(
                    action: 'subscription_notification_sent',
                    entityType: 'subscription',
                    entityId: $subscription->id,
                    userId: $subscription->buyer_id,
                    context: [
                        'notification_type' => 'subscription_created',
                    ],
                );
            },
            $this->getStandardAttributes(
                vertical: 'supermarket',
                operation: 'subscription_notification_created',
                userId: (string) $subscription->buyer_id,
            ),
        );
    }

    public function sendPreDeliveryReminder(Subscription $subscription): void
    {
        $this->withSpan(
            'subscription_notification.pre_delivery',
            function () use ($subscription) {
                $this->fraudControl->check([
                    'operation_type' => 'subscription_notification_pre_delivery',
                    'vertical' => 'supermarket',
                    'user_id' => $subscription->buyer_id,
                    'subscription_id' => $subscription->id,
                    'correlation_id' => $this->generateCorrelationId(),
                ]);

                $hoursLeft = now()->diffInHours($subscription->next_delivery_at);
                $daysLeft = now()->diffInDays($subscription->next_delivery_at);

                $timeText = $daysLeft > 0 ? "{$daysLeft} дн." : "{$hoursLeft} ч.";

                $message = "📦 Напоминание о доставке по подписке!\n" .
                           "Заказ будет доставлен через {$timeText}.\n" .
                           "Дата: " . $subscription->next_delivery_at->format('d.m.Y H:i');

                $this->sendToAllChannels($subscription->buyer, $message, 'pre_delivery', [
                    'subscription_id' => $subscription->id,
                    'actions' => ['track', 'pause', 'cancel'],
                ]);

                Log::info('Pre-delivery reminder sent', [
                    'subscription_id' => $subscription->id,
                    'hours_left' => $hoursLeft,
                ]);
            },
            $this->getStandardAttributes(
                vertical: 'supermarket',
                operation: 'subscription_notification_pre_delivery',
                userId: (string) $subscription->buyer_id,
            ),
        );
    }

    public function sendDeliveryStarted(SupermarketOrder $order): void
    {
        $this->withSpan(
            'subscription_notification.delivery_started',
            function () use ($order) {
                $this->fraudControl->check([
                    'operation_type' => 'subscription_notification_delivery_started',
                    'vertical' => 'supermarket',
                    'user_id' => $order->buyer_id,
                    'order_id' => $order->id,
                    'subscription_id' => $order->subscription_id,
                    'correlation_id' => $this->generateCorrelationId(),
                ]);

                $message = "🚚 Курьер выехал с вашим заказом по подписке!\n" .
                           "№{$order->id}\n" .
                           "ETA: ~{$order->delivery_eta ?? 30} мин";

                $this->sendToAllChannels($order->buyer, $message, 'delivery_started', [
                    'order_id' => $order->id,
                    'subscription_id' => $order->subscription_id,
                    'actions' => ['track'],
                ]);

                Log::info('Delivery started notification sent', [
                    'order_id' => $order->id,
                    'subscription_id' => $order->subscription_id,
                ]);
            },
            $this->getStandardAttributes(
                vertical: 'supermarket',
                operation: 'subscription_notification_delivery_started',
                userId: (string) $order->buyer_id,
            ),
        );
    }

    public function sendSubscriptionPaused(Subscription $subscription, string $reason = null): void
    {
        $this->withSpan(
            'subscription_notification.paused',
            function () use ($subscription, $reason) {
                $this->fraudControl->check([
                    'operation_type' => 'subscription_notification_paused',
                    'vertical' => 'supermarket',
                    'user_id' => $subscription->buyer_id,
                    'subscription_id' => $subscription->id,
                    'correlation_id' => $this->generateCorrelationId(),
                ]);

                $message = "⏸ Ваша подписка приостановлена\n\n" .
                           "Причина: " . ($reason ?? 'По вашему запросу');

                $this->sendToAllChannels($subscription->buyer, $message, 'subscription_paused', [
                    'subscription_id' => $subscription->id,
                    'actions' => ['resume'],
                ]);

                Log::info('Subscription paused notification sent', [
                    'subscription_id' => $subscription->id,
                    'reason' => $reason,
                ]);
            },
            $this->getStandardAttributes(
                vertical: 'supermarket',
                operation: 'subscription_notification_paused',
                userId: (string) $subscription->buyer_id,
            ),
        );
    }

    public function sendPaymentFailed(Subscription $subscription, string $reason): void
    {
        $this->withSpan(
            'subscription_notification.payment_failed',
            function () use ($subscription, $reason) {
                $this->fraudControl->check([
                    'operation_type' => 'subscription_notification_payment_failed',
                    'vertical' => 'supermarket',
                    'user_id' => $subscription->buyer_id,
                    'subscription_id' => $subscription->id,
                    'correlation_id' => $this->generateCorrelationId(),
                ]);

                $message = "❌ Проблема с оплатой подписки\n\n" .
                           "Причина: {$reason}\n" .
                           "Пожалуйста, обновите способ оплаты";

                $this->sendToAllChannels($subscription->buyer, $message, 'payment_failed', [
                    'subscription_id' => $subscription->id,
                    'actions' => ['update_payment', 'pause'],
                ]);

                Log::info('Payment failed notification sent', [
                    'subscription_id' => $subscription->id,
                    'reason' => $reason,
                ]);
            },
            $this->getStandardAttributes(
                vertical: 'supermarket',
                operation: 'subscription_notification_payment_failed',
                userId: (string) $subscription->buyer_id,
            ),
        );
    }

    public function sendSubscriptionCancelled(Subscription $subscription, string $reason = null): void
    {
        $this->withSpan(
            'subscription_notification.cancelled',
            function () use ($subscription, $reason) {
                $this->fraudControl->check([
                    'operation_type' => 'subscription_notification_cancelled',
                    'vertical' => 'supermarket',
                    'user_id' => $subscription->buyer_id,
                    'subscription_id' => $subscription->id,
                    'correlation_id' => $this->generateCorrelationId(),
                ]);

                $message = "🗑 Ваша подписка отменена\n\n" .
                           ($reason ? "Причина: {$reason}" : '');

                $this->sendToAllChannels($subscription->buyer, $message, 'subscription_cancelled', [
                    'subscription_id' => $subscription->id,
                ]);

                Log::info('Subscription cancelled notification sent', [
                    'subscription_id' => $subscription->id,
                    'reason' => $reason,
                ]);
            },
            $this->getStandardAttributes(
                vertical: 'supermarket',
                operation: 'subscription_notification_cancelled',
                userId: (string) $subscription->buyer_id,
            ),
        );
    }

    private function sendToAllChannels(User $user, string $text, string $type, array $meta = []): void
    {
        $user->notify(new \App\Notifications\SubscriptionNotification($text, $type, $meta));

        if ($user->telegram_id) {
            try {
                $telegramService = app(\App\Services\TelegramBotService::class);
                $telegramService->sendMessage($user->telegram_id, $text);
            } catch (\Exception $e) {
                Log::warning('Failed to send Telegram notification', [
                    'user_id' => $user->id,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        if ($user->whatsapp_phone) {
            try {
                $whatsappService = app(\App\Services\WhatsAppService::class);
                $whatsappService->sendInteractive($user->whatsapp_phone, $text, $meta);
            } catch (\Exception $e) {
                Log::warning('Failed to send WhatsApp notification', [
                    'user_id' => $user->id,
                    'error' => $e->getMessage(),
                ]);
            }
        }
    }

    private function humanFrequency(string $frequency): string
    {
        return match ($frequency) {
            'weekly' => 'Еженедельно',
            'biweekly' => 'Раз в 2 недели',
            'monthly' => 'Ежемесячно',
            default => $frequency,
        };
    }
}
