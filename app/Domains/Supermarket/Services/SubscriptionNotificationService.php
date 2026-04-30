<?php

declare(strict_types=1);

namespace App\Domains\Supermarket\Services;

use App\Domains\Shared\Notifications\Services\ABTestService;
use App\Domains\Supermarket\Models\Return as ReturnModel;
use App\Domains\Supermarket\Models\Subscription;
use App\Domains\Supermarket\Models\SupermarketOrder;
use App\Models\User;
use App\Services\Telegram\TelegramBotService;
use App\Services\WhatsApp\WhatsAppService;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Facades\Log;

final readonly class SubscriptionNotificationService
{
    public function __construct(
        private ?TelegramBotService $telegramBotService = null,
        private ?WhatsAppService $whatsAppService = null,
        private ABTestService $abTestService,
    ) {
    }

    public function sendSubscriptionCreated(Subscription $subscription): void
    {
        $variant = $this->abTestService->getVariant('subscription_created', $subscription->buyer);
        $message = $variant['text'] ?? "✅ Подписка оформлена!\n\n" .
            "Частота: " . $this->humanFrequency($subscription->frequency) . "\n" .
            "Следующая доставка: " . $subscription->next_delivery_at->format('d.m.Y') . "\n" .
            "Сумма: {$subscription->total_amount} ₽";

        $this->sendToAllChannels($subscription->buyer, $message, 'subscription_created', [
            'subscription_id' => $subscription->id,
            'actions' => $variant['buttons'] ?? ['track', 'pause', 'cancel'],
            'ab_variant' => $variant['name'] ?? 'default',
        ]);

        Log::info('Subscription created notification sent', [
            'subscription_id' => $subscription->id,
            'buyer_id' => $subscription->buyer_id,
            'ab_variant' => $variant['name'] ?? 'default',
        ]);
    }

    public function sendPreDeliveryReminder(Subscription $subscription): void
    {
        $variant = $this->abTestService->getVariant('pre_delivery_reminder', $subscription->buyer);
        $hoursLeft = now()->diffInHours($subscription->next_delivery_at);

        $message = $variant['text'] ?? "📦 Напоминание о доставке по подписке!\n" .
            "Заказ будет доставлен через {$hoursLeft} часов.\n" .
            "Дата: " . $subscription->next_delivery_at->format('d.m.Y H:i');

        $this->sendToAllChannels($subscription->buyer, $message, 'pre_delivery', [
            'subscription_id' => $subscription->id,
            'actions' => $variant['buttons'] ?? ['track', 'pause', 'cancel'],
            'ab_variant' => $variant['name'] ?? 'default',
        ]);

        Log::info('Pre-delivery reminder sent', [
            'subscription_id' => $subscription->id,
            'buyer_id' => $subscription->buyer_id,
            'hours_left' => $hoursLeft,
            'ab_variant' => $variant['name'] ?? 'default',
        ]);
    }

    public function sendDeliveryStarted(SupermarketOrder $order): void
    {
        $message = "🚚 Курьер выехал с вашим заказом по подписке!\n" .
            "№{$order->id}\n" .
            "ETA: ~{$order->delivery_eta} мин";

        $this->sendInteractiveWhatsApp($order->buyer, $message, $order->id);

        if ($order->buyer->Channelegram_id) {
            $this->telegramBotService->sendMessage($order->buyer->telegram_id, $message);
        }

        Log::info('Delivery started notification sent', [
            'order_id' => $order->id,
            'buyer_id' => $order->buyer_id
        ]);
    }

    public function sendPaymentFailed(array $paymentData): void
    {
        $subscription = Subscription::find($paymentData['subscription_id']);
        if (!$subscription) {
            return;
        }

        $message = "❌ Проблема с оплатой подписки!\n\n" .
            "Подписка #{$subscription->id}\n" .
            "Сумма: {$paymentData['amount']} ₽\n" .
            "Попытка: {$paymentData['attempt']} из 3\n\n" .
            "Пожалуйста, обновите способ оплаты.";

        $this->sendToAllChannels($subscription->buyer, $message, 'payment_failed', [
            'subscription_id' => $subscription->id,
            'actions' => ['update_payment', 'pause']
        ]);

        Log::warning('Payment failed notification sent', [
            'subscription_id' => $subscription->id,
            'attempt' => $paymentData['attempt']
        ]);
    }

    public function sendSubscriptionPaused(Subscription $subscription, string $reason): void
    {
        $message = "⏸️ Ваша подписка приостановлена\n\n" .
            "Причина: {$reason}\n" .
            "Для возобновления перейдите в настройки подписки.";

        $this->sendToAllChannels($subscription->buyer, $message, 'subscription_paused', [
            'subscription_id' => $subscription->id,
            'actions' => ['resume']
        ]);

        Log::info('Subscription paused notification sent', [
            'subscription_id' => $subscription->id,
            'reason' => $reason
        ]);
    }

    public function sendSubscriptionCancelled(Subscription $subscription): void
    {
        $message = "🚫 Ваша подписка отменена\n\n" .
            "Спасибо за использование нашего сервиса!\n" .
            "Вы всегда можете оформить новую подписку.";

        $this->sendToAllChannels($subscription->buyer, $message, 'subscription_cancelled', [
            'subscription_id' => $subscription->id
        ]);

        Log::info('Subscription cancelled notification sent', [
            'subscription_id' => $subscription->id
        ]);
    }

    private function sendToAllChannels(User $user, string $text, string $type, array $meta = []): void
    {
        try {
            $user->notify(new SubscriptionNotification($text, $type, $meta));
        } catch (\Exception $e) {
            Log::error('Failed to send database notification', [
                'user_id' => $user->id,
                'type' => $typeBot,
                'error' => $e->getMessage()
            ]);
        }

        if ($user->telegram_id) {
            try {
                $this->telegramChannelService->sendMessage($user->telegram_id, $text);
            } catch (\Exception $e) {
                Log::error('Failed to send Telegram notification', [
                    'user_id' => $user->id,
                    'type' => $type,
                    'error' => $e->getMessage()
                ]);
            }
        }

        // WhatsApp notification would go here when WhatsApp service is available
        // if ($user->whatsapp_phone) {
        //     try {
        //         $this->whatsAppService->sendInteractive($user->whatsapp_phone, $text, $meta);
        //     } catch (\Exception $e) {
        //         Log::error('Failed to send WhatsApp notification', [
        //             'user_id' => $user->id,
        //             'type' => $type,
        //             'error' => $e->getMessage()
        //         ]);
        //     }
        // }
    }

    private function sendInteractiveWhatsApp(User $user, string $text, int $orderId): void
    {
        $this->whatsAppService->sendInteractive($user->whatsapp_phone, $text, [
            'order_id' => $orderId,
            'actions' => ['track', 'contact_support']
        ]);
    }

    private function humanFrequency(string $frequency): string
    {
        return match($frequency) {
            'weekly' => 'Каждую неделю',
            'biweekly' => 'Раз в 2 недели',
            'monthly' => 'Раз в месяц',
            default => $frequency
        };
    }

    public function sendReturnCreated(ReturnModel $return): void
    {
        $message = "📦 Создана заявка на возврат\n\n" .
            "Заказ #{$return->order_id}\n" .
            "Сумма возврата: {$return->refund_amount} ₽\n" .
            "Статус: {$return->status}";

        $buyer = User::find($return->buyer_id);
        $seller = User::find($return->seller_id);

        if ($buyer) {
            $this->sendToAllChannels($buyer, $message, 'return_created_buyer', [
                'return_id' => $return->id,
                'actions' => ['track', 'contact_support']
            ]);
        }

        if ($seller) {
            $sellerMessage = "📦 Новая заявка на возврат\n\n" .
                "Заказ #{$return->order_id}\n" .
                "Сумма: {$return->refund_amount} ₽\n" .
                "Причина: {$return->reason_type}";

            $this->sendToAllChannels($seller, $sellerMessage, 'return_created_seller', [
                'return_id' => $return->id,
                'actions' => ['review', 'contact_buyer']
            ]);
        }

        Log::info('Return created notifications sent', [
            'return_id' => $return->id,
            'buyer_id' => $return->buyer_id,
            'seller_id' => $return->seller_id
        ]);
    }

    public function sendReturnApproved(ReturnModel $return): void
    {
        $message = "✅ Возврат одобрен\n\n" .
            "Заказ #{$return->order_id}\n" .
            "Сумма возврата: {$return->refund_amount} ₽\n" .
            "Деньги будут возвращены на карту в течение 3-5 рабочих дней";

        $buyer = User::find($return->buyer_id);

        if ($buyer) {
            $this->sendToAllChannels($buyer, $message, 'return_approved', [
                'return_id' => $return->id,
                'actions' => ['track']
            ]);
        }

        Log::info('Return approved notification sent', [
            'return_id' => $return->id,
            'buyer_id' => $return->buyer_id
        ]);
    }

    public function sendReturnRejected(ReturnModel $return): void
    {
        $reason = $return->reject_reason ?? 'Не указана';
        $message = "❌ Возврат отклонён\n\n" .
            "Заказ #{$return->order_id}\n" .
            "Причина: {$reason}\n" .
            "Если вы считаете это ошибкой, свяжитесь с поддержкой";

        $buyer = User::find($return->buyer_id);

        if ($buyer) {
            $this->sendToAllChannels($buyer, $message, 'return_rejected', [
                'return_id' => $return->id,
                'actions' => ['contact_support']
            ]);
        }

        Log::info('Return rejected notification sent', [
            'return_id' => $return->id,
            'buyer_id' => $return->buyer_id
        ]);
    }
}
