<?php

declare(strict_types=1);

namespace App\Domains\Shared\Notifications\Services;

use Illuminate\Support\Facades\Log;

final readonly class NotificationHub
{
    public function __construct(
        private TelegramBotService $telegramService,
        private WhatsAppService $whatsappService,
        private EmailService $emailService,
        private PushService $pushService,
        private iMessageService $iMessageService,
        private ViberService $viberService,
        private KakaoTalkService $kakaoTalkService
    ) {}

    /**
     * Centralized notification dispatch for all verticals
     */
    public function dispatchOrderNotification(
        string $vertical,
        int $orderId,
        int $buyerId,
        int $sellerId,
        string $eventType,
        array $data = []
    ): void {
        // Telegram notifications
        $telegramConfig = config("verticals.{$vertical}.notifications.telegram", []);

        if ($telegramConfig['enabled'] ?? false) {
            $enabledEvents = $telegramConfig['events'] ?? ['created', 'confirmed', 'delivered'];

            if (in_array($eventType, $enabledEvents, true)) {
                try {
                    $this->telegramService->sendOrderNotification(
                        $orderId,
                        $buyerId,
                        $sellerId,
                        $eventType,
                        $data
                    );

                    Log::info('Telegram notification sent', [
                        'vertical' => $vertical,
                        'order_id' => $orderId,
                        'event' => $eventType,
                    ]);
                } catch (\Throwable $e) {
                    Log::error('Failed to send Telegram notification', [
                        'vertical' => $vertical,
                        'order_id' => $orderId,
                        'event' => $eventType,
                        'error' => $e->getMessage(),
                    ]);
                }
            }
        }

        // WhatsApp notifications
        $whatsappConfig = config("verticals.{$vertical}.notifications.whatsapp", []);

        if ($whatsappConfig['enabled'] ?? false) {
            $enabledEvents = $whatsappConfig['events'] ?? ['created', 'ready_for_delivery', 'in_delivery', 'delivered'];
            $useInteractive = $whatsappConfig['interactive'] ?? false;

            if (in_array($eventType, $enabledEvents, true)) {
                try {
                    if ($useInteractive && in_array($eventType, ['created', 'ready_for_delivery', 'in_delivery'], true)) {
                        $this->whatsappService->sendInteractive(
                            $orderId,
                            $buyerId,
                            $sellerId,
                            $eventType,
                            $data
                        );
                    } else {
                        $this->whatsappService->sendOrderNotification(
                            $orderId,
                            $buyerId,
                            $sellerId,
                            $eventType,
                            $data
                        );
                    }

                    Log::info('WhatsApp notification sent', [
                        'vertical' => $vertical,
                        'order_id' => $orderId,
                        'event' => $eventType,
                        'interactive' => $useInteractive,
                    ]);
                } catch (\Throwable $e) {
                    Log::error('Failed to send WhatsApp notification', [
                        'vertical' => $vertical,
                        'order_id' => $orderId,
                        'event' => $eventType,
                        'error' => $e->getMessage(),
                    ]);
                }
            }
        }

        // Email notifications
        $emailConfig = config("verticals.{$vertical}.notifications.email", []);

        if ($emailConfig['enabled'] ?? false) {
            $enabledEvents = $emailConfig['events'] ?? ['created', 'confirmed', 'in_delivery', 'delivered'];

            if (in_array($eventType, $enabledEvents, true)) {
                try {
                    $this->emailService->sendOrderNotification(
                        $orderId,
                        $buyerId,
                        $sellerId,
                        $eventType,
                        $data
                    );

                    Log::info('Email notification sent', [
                        'vertical' => $vertical,
                        'order_id' => $orderId,
                        'event' => $eventType,
                    ]);
                } catch (\Throwable $e) {
                    Log::error('Failed to send email notification', [
                        'vertical' => $vertical,
                        'order_id' => $orderId,
                        'event' => $eventType,
                        'error' => $e->getMessage(),
                    ]);
                }
            }
        }

        // Push notifications
        $pushConfig = config("verticals.{$vertical}.notifications.push", []);

        if ($pushConfig['enabled'] ?? false) {
            $enabledEvents = $pushConfig['events'] ?? ['created', 'confirmed', 'in_delivery', 'delivered'];

            if (in_array($eventType, $enabledEvents, true)) {
                try {
                    $this->pushService->sendOrderNotification(
                        $orderId,
                        $buyerId,
                        $sellerId,
                        $eventType,
                        $data
                    );

                    Log::info('Push notification sent', [
                        'vertical' => $vertical,
                        'order_id' => $orderId,
                        'event' => $eventType,
                    ]);
                } catch (\Throwable $e) {
                    Log::error('Failed to send push notification', [
                        'vertical' => $vertical,
                        'order_id' => $orderId,
                        'event' => $eventType,
                        'error' => $e->getMessage(),
                    ]);
                }
            }
        }

        // iMessage notifications
        $imessageConfig = config("verticals.{$vertical}.notifications.imessage", []);

        if ($imessageConfig['enabled'] ?? false) {
            $enabledEvents = $imessageConfig['events'] ?? ['created', 'confirmed', 'in_delivery', 'delivered'];

            if (in_array($eventType, $enabledEvents, true)) {
                try {
                    $this->iMessageService->sendOrderNotification(
                        $orderId,
                        $buyerId,
                        $sellerId,
                        $eventType,
                        $data
                    );

                    Log::info('iMessage notification sent', [
                        'vertical' => $vertical,
                        'order_id' => $orderId,
                        'event' => $eventType,
                    ]);
                } catch (\Throwable $e) {
                    Log::error('Failed to send iMessage notification', [
                        'vertical' => $vertical,
                        'order_id' => $orderId,
                        'event' => $eventType,
                        'error' => $e->getMessage(),
                    ]);
                }
            }
        }

        // Viber notifications
        $viberConfig = config("verticals.{$vertical}.notifications.viber", []);

        if ($viberConfig['enabled'] ?? false) {
            $enabledEvents = $viberConfig['events'] ?? ['created', 'ready_for_delivery', 'in_delivery', 'delivered'];
            $useInteractive = $viberConfig['interactive'] ?? false;

            if (in_array($eventType, $enabledEvents, true)) {
                try {
                    if ($useInteractive && in_array($eventType, ['created', 'ready_for_delivery', 'in_delivery'], true)) {
                        $this->viberService->sendInteractive(
                            $orderId,
                            $buyerId,
                            $sellerId,
                            $eventType,
                            $data
                        );
                    } else {
                        $this->viberService->sendOrderNotification(
                            $orderId,
                            $buyerId,
                            $sellerId,
                            $eventType,
                            $data
                        );
                    }

                    Log::info('Viber notification sent', [
                        'vertical' => $vertical,
                        'order_id' => $orderId,
                        'event' => $eventType,
                        'interactive' => $useInteractive,
                    ]);
                } catch (\Throwable $e) {
                    Log::error('Failed to send Viber notification', [
                        'vertical' => $vertical,
                        'order_id' => $orderId,
                        'event' => $eventType,
                        'error' => $e->getMessage(),
                    ]);
                }
            }
        }

        // KakaoTalk notifications
        $kakaoConfig = config("verticals.{$vertical}.notifications.kakaotalk", []);

        if ($kakaoConfig['enabled'] ?? false) {
            $enabledEvents = $kakaoConfig['events'] ?? ['created', 'ready_for_delivery', 'in_delivery', 'delivered'];

            if (in_array($eventType, $enabledEvents, true)) {
                try {
                    $this->kakaoTalkService->sendOrderNotification(
                        $orderId,
                        $buyerId,
                        $sellerId,
                        $eventType,
                        $data
                    );

                    Log::info('KakaoTalk notification sent', [
                        'vertical' => $vertical,
                        'order_id' => $orderId,
                        'event' => $eventType,
                    ]);
                } catch (\Throwable $e) {
                    Log::error('Failed to send KakaoTalk notification', [
                        'vertical' => $vertical,
                        'order_id' => $orderId,
                        'event' => $eventType,
                        'error' => $e->getMessage(),
                    ]);
                }
            }
        }

        // Signal notifications
        $signalConfig = config("verticals.{$vertical}.notifications.signal", []);

        if ($signalConfig['enabled'] ?? false) {
            $enabledEvents = $signalConfig['events'] ?? ['created', 'ready_for_delivery', 'in_delivery', 'delivered'];

            if (in_array($eventType, $enabledEvents, true)) {
                try {
                    $this->signalService->sendOrderNotification(
                        $orderId,
                        $buyerId,
                        $sellerId,
                        $eventType,
                        $data
                    );

                    Log::info('Signal notification sent', [
                        'vertical' => $vertical,
                        'order_id' => $orderId,
                        'event' => $eventType,
                    ]);
                } catch (\Throwable $e) {
                    Log::error('Failed to send Signal notification', [
                        'vertical' => $vertical,
                        'order_id' => $orderId,
                        'event' => $eventType,
                        'error' => $e->getMessage(),
                    ]);
                }
            }
        }

        // WeChat notifications
        $wechatConfig = config("verticals.{$vertical}.notifications.wechat", []);

        if ($wechatConfig['enabled'] ?? false) {
            $enabledEvents = $wechatConfig['events'] ?? ['created', 'confirmed', 'in_delivery', 'delivered'];

            if (in_array($eventType, $enabledEvents, true)) {
                try {
                    $this->weChatService->sendOrderNotification(
                        $orderId,
                        $buyerId,
                        $sellerId,
                        $eventType,
                        $data
                    );

                    Log::info('WeChat notification sent', [
                        'vertical' => $vertical,
                        'order_id' => $orderId,
                        'event' => $eventType,
                    ]);
                } catch (\Throwable $e) {
                    Log::error('Failed to send WeChat notification', [
                        'vertical' => $vertical,
                        'order_id' => $orderId,
                        'event' => $eventType,
                        'error' => $e->getMessage(),
                    ]);
                }
            }
        }

        // VK notifications
        $vkConfig = config("verticals.{$vertical}.notifications.vk", []);

        if ($vkConfig['enabled'] ?? false) {
            $enabledEvents = $vkConfig['events'] ?? ['created', 'ready_for_delivery', 'in_delivery', 'delivered'];
            $useInteractive = $vkConfig['interactive'] ?? false;

            if (in_array($eventType, $enabledEvents, true)) {
                try {
                    if ($useInteractive && in_array($eventType, ['created', 'ready_for_delivery', 'in_delivery'], true)) {
                        $this->vkService->sendInteractive(
                            $orderId,
                            $buyerId,
                            $sellerId,
                            $eventType,
                            $data
                        );
                    } else {
                        $this->vkService->sendOrderNotification(
                            $orderId,
                            $buyerId,
                            $sellerId,
                            $eventType,
                            $data
                        );
                    }

                    Log::info('VK notification sent', [
                        'vertical' => $vertical,
                        'order_id' => $orderId,
                        'event' => $eventType,
                        'interactive' => $useInteractive,
                    ]);
                } catch (\Throwable $e) {
                    Log::error('Failed to send VK notification', [
                        'vertical' => $vertical,
                        'order_id' => $orderId,
                        'event' => $eventType,
                        'error' => $e->getMessage(),
                    ]);
                }
            }
        }

        // Odnoklassniki notifications
        $okConfig = config("verticals.{$vertical}.notifications.odnoklassniki", []);

        if ($okConfig['enabled'] ?? false) {
            $enabledEvents = $okConfig['events'] ?? ['created', 'ready_for_delivery', 'in_delivery', 'delivered'];

            if (in_array($eventType, $enabledEvents, true)) {
                try {
                    $this->odnoklassnikiService->sendOrderNotification(
                        $orderId,
                        $buyerId,
                        $sellerId,
                        $eventType,
                        $data
                    );

                    Log::info('Odnoklassniki notification sent', [
                        'vertical' => $vertical,
                        'order_id' => $orderId,
                        'event' => $eventType,
                    ]);
                } catch (\Throwable $e) {
                    Log::error('Failed to send Odnoklassniki notification', [
                        'vertical' => $vertical,
                        'order_id' => $orderId,
                        'event' => $eventType,
                        'error' => $e->getMessage(),
                    ]);
                }
            }
        }
    }

    /**
     * Send custom notification to user
     */
    public function notifyUser(
        int $userId,
        string $message,
        array $keyboard = null
    ): bool {
        try {
            return $this->telegramService->sendToUser($userId, $message, $keyboard);
        } catch (\Throwable $e) {
            Log::error('Failed to notify user via Telegram', [
                'user_id' => $userId,
                'error' => $e->getMessage(),
            ]);
            return false;
        }
    }

    /**
     * Send photo notification
     */
    public function sendPhotoToUser(int $userId, string $photoUrl, string $caption = ''): bool
    {
        try {
            $link = \App\Models\UserTelegramLink::where('user_id', $userId)
                ->where('is_active', true)
                ->first();

            if (!$link) {
                return false;
            }

            return $this->telegramService->sendPhoto($link->telegram_id, $photoUrl, $caption);
        } catch (\Throwable $e) {
            Log::error('Failed to send photo via Telegram', [
                'user_id' => $userId,
                'error' => $e->getMessage(),
            ]);
            return false;
        }
    }
}
