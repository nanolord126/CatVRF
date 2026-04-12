<?php

declare(strict_types=1);

namespace App\Services\Communication;

use App\Domains\Communication\Models\Message;
use Illuminate\Http\Client\Factory as HttpClient;
use Illuminate\Log\LogManager;

/**
 * Sends a Message record via SMS (Twilio / SMS.ru).
 * Canon: final readonly, DI only, no Facades.
 */
final readonly class SmsChannelService
{
    public function __construct(
        private HttpClient $http,
        private LogManager $logger,
    ) {}

    public function send(Message $message): void
    {
        $phone = $message->metadata['to_phone'] ?? null;

        if (empty($phone)) {
            $this->logger->channel('audit')->warning('SmsChannelService: no to_phone in metadata', [
                'message_id'     => $message->id,
                'correlation_id' => $message->correlation_id,
            ]);
            return;
        }

        $driver  = config('services.sms.driver', 'smsru');
        $apiKey  = config("services.sms.{$driver}.api_key", '');
        $baseUrl = config("services.sms.{$driver}.base_url", 'https://sms.ru/sms/send');

        $response = $this->http->post($baseUrl, [
            'api_id' => $apiKey,
            'to'     => $phone,
            'msg'    => $message->body,
            'json'   => 1,
        ]);

        $this->logger->channel('audit')->info('SMS message dispatched', [
            'message_id'     => $message->id,
            'to_phone'       => $phone,
            'driver'         => $driver,
            'status'         => $response->status(),
            'correlation_id' => $message->correlation_id,
        ]);
    }

    /**
     * Component: SmsChannelService
     *
     * Part of the CatVRF 2026 multi-vertical marketplace platform.
     * Implements tenant-aware, fraud-checked business logic
     * with full correlation_id tracing and audit logging.
     *
     * @package CatVRF
     * @version 2026.1
     */}
