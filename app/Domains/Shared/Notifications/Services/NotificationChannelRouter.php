<?php

declare(strict_types=1);

namespace App\Domains\Shared\Notifications\Services;

use Illuminate\Support\Facades\Config;

final readonly class NotificationChannelRouter
{
    public function getChannels(string $vertical, string $recipientType): array
    {
        $config = Config::get("notifications.{$vertical}.{$recipientType}", [
            'database', 'mail'
        ]);

        // Filter out channels that aren't configured
        return array_filter($config, function ($channel) {
            return $this->isChannelConfigured($channel);
        });
    }

    private function isChannelConfigured(string $channel): bool
    {
        return match($channel) {
            'database' => true,
            'mail' => !empty(config('mail.mailers.smtp.host')),
            'fcm' => !empty(config('services.fcm.key')),
            'telegram' => !empty(config('services.telegram.bot_token')),
            'sms' => !empty(config('services.sms.key')),
            default => false,
        };
    }
}
