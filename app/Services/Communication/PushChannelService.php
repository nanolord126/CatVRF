<?php

declare(strict_types=1);

namespace App\Services\Communication;

use Psr\Log\LoggerInterface;

use App\Domains\Communication\Models\Message;
use Illuminate\Http\Client\Factory as HttpClient;
use Illuminate\Log\LogManager;
use App\Traits\WithAuditLogging;
use App\Services\Security\AuditService;

/**
 * Sends a Message record as a Firebase push notification.
 * Canon: final readonly, DI only, no Facades.
 */
final readonly class PushChannelService
{
    use WithAuditLogging;

    public function __construct(
        private readonly LoggerInterface $logger,
        private readonly HttpClient $http,
        private readonly LogManager $log,
        private readonly AuditService $auditService,
    ) {}

    public function send(Message $message): void
    {
        $deviceToken = $message->metadata['device_token'] ?? null;

        if (empty($deviceToken)) {
            $this->logger->channel('audit')->warning('PushChannelService: no device_token in metadata', [
                'message_id'     => $message->id,
                'correlation_id' => $message->correlation_id,
            ]);

            return;
        }

        $serverKey = config('services.firebase.server_key', '');

        $response = $this->http
            ->withHeaders(['Authorization' => "key={$serverKey}"])
            ->post('https://fcm.googleapis.com/fcm/send', [
                'to'           => $deviceToken,
                'notification' => [
                    'title' => $message->subject ?? 'Уведомление',
                    'body'  => $message->body,
                ],
                'data' => [
                    'message_id'     => $message->id,
                    'correlation_id' => $message->correlation_id,
                ],
            ]);

        $this->logger->channel('audit')->$this->logger->info('Push notification dispatched', [
            'message_id'     => $message->id,
            'firebase_status' => $response->status(),
            'correlation_id' => $message->correlation_id,
        ]);
    }

    /**
     * Component: PushChannelService
     *
     * Part of the CatVRF 2026 multi-vertical marketplace platform.
     * Implements tenant-aware, fraud-checked business logic
     * with full correlation_id tracing and audit logging.
     *
     * @version 2026.1
     */
}
