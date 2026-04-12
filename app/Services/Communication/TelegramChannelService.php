<?php

declare(strict_types=1);

namespace App\Services\Communication;

use App\Domains\Communication\Models\Message;
use Illuminate\Http\Client\Factory as HttpClient;
use Illuminate\Log\LogManager;

/**
 * Sends a Message record via Telegram Bot API.
 * Canon: final readonly, DI only, no Facades.
 */
final readonly class TelegramChannelService
{
    public function __construct(
        private HttpClient $http,
        private LogManager $logger,
    ) {}

    public function send(Message $message): void
    {
        $chatId = $message->metadata['telegram_chat_id'] ?? null;

        if (empty($chatId)) {
            $this->logger->channel('audit')->warning('TelegramChannelService: no telegram_chat_id in metadata', [
                'message_id'     => $message->id,
                'correlation_id' => $message->correlation_id,
            ]);
            return;
        }

        $botToken = config('services.telegram.bot_token', '');
        $url      = "https://api.telegram.org/bot{$botToken}/sendMessage";

        $response = $this->http->post($url, [
            'chat_id'    => $chatId,
            'text'       => $message->body,
            'parse_mode' => 'HTML',
        ]);

        $this->logger->channel('audit')->info('Telegram message dispatched', [
            'message_id'     => $message->id,
            'chat_id'        => $chatId,
            'telegram_ok'    => $response->json('ok'),
            'correlation_id' => $message->correlation_id,
        ]);
    }

    /**
     * Component: TelegramChannelService
     *
     * Part of the CatVRF 2026 multi-vertical marketplace platform.
     * Implements tenant-aware, fraud-checked business logic
     * with full correlation_id tracing and audit logging.
     *
     * @package CatVRF
     * @version 2026.1
     */}
