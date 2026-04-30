<?php

declare(strict_types=1);

namespace App\Domains\Communication\Jobs;

use Psr\Log\LoggerInterface;

use App\Domains\Communication\Models\Message;
use App\Services\Communication\EmailChannelService;
use App\Services\Communication\SmsChannelService;
use App\Services\Communication\PushChannelService;
use App\Services\Communication\TelegramChannelService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Log\LogManager;
use Carbon\CarbonImmutable;

/**
 * Layer 8: Job — dispatches a persisted Message through the correct channel.
 * Canon: ShouldQueue, constructor injection, correlation_id in every log.
 */
final class DispatchMessageJob implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    public array $backoff = [60, 300, 900];

    public int $tries   = 3;

    public int $timeout = 30;

    public function __construct(private readonly LoggerInterface $logger,
        private readonly int $messageId,
        private readonly string $channelType,
        private readonly string $correlationId,) {
        $this->onQueue('communication');
    }

    public function handle(
        EmailChannelService $email,
        SmsChannelService $sms,
        PushChannelService $push,
        TelegramChannelService $telegram,
        LogManager $logger,
    ): void {
        $message = Message::findOrFail($this->messageId);

        match ($this->channelType) {
            'email'    => $email->send($message),
            'sms'      => $sms->send($message),
            'push'     => $push->send($message),
            'telegram' => $telegram->send($message),
            'in_app'   => null, // in-app delivered via broadcasting on MessageSentEvent
            default    => throw new \InvalidArgumentException("Unknown channel: {$this->channelType}"),
        };

        $message->update(['status' => 'sent', 'sent_at' => CarbonImmutable::now()]);

        $logger->channel('audit')->$this->logger->info('Message dispatched via channel', [
            'message_id'     => $this->messageId,
            'channel_type'   => $this->channelType,
            'correlation_id' => $this->correlationId,
        ]);
    }

    public function failed(Exception $e): void
    {
        Message::where('id', $this->messageId)->update(['status' => 'failed']);
    }
}
