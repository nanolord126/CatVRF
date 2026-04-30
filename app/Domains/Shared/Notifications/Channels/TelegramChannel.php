<?php

declare(strict_types=1);

namespace App\Domains\Shared\Notifications\Channels;

use App\Domains\Shared\Notifications\Services\TelegramBotService;
use Illuminate\Notifications\Notification;

final class TelegramChannel
{
    public function __construct(
        private readonly TelegramBotService $telegramBotService
    ) {}

    public function send($notifiable, Notification $notification): void
    {
        if (!method_exists($notification, 'toTelegram')) {
            return;
        }

        $message = $notification->toTelegram($notifiable);
        $chatId = $this->getChatId($notifiable);

        if (!$chatId) {
            return;
        }

        $keyboard = $message->keyboard ?? null;
        $this->telegramBotService->sendMessage($chatId, $message->content, $keyboard);
    }

    private function getChatId($notifiable): ?int
    {
        // If notifiable has telegram_id directly
        if (isset($notifiable->telegram_id)) {
            return (int) $notifiable->telegram_id;
        }

        // If notifiable has user_telegram_link relationship
        if (method_exists($notifiable, 'telegramLink')) {
            $link = $notifiable->telegramLink;
            if ($link && $link->is_active) {
                return (int) $link->telegram_id;
            }
        }

        // If notifiable is User model
        if (method_exists($notifiable, 'telegramLinks')) {
            $link = $notifiable->telegramLinks()->where('is_active', true)->first();
            if ($link) {
                return (int) $link->telegram_id;
            }
        }

        return null;
    }
}
