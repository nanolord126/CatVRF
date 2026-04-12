<?php

declare(strict_types=1);

namespace App\Domains\Communication\Jobs;


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

/**
 * Layer 8: Job — dispatches a persisted Message through the correct channel.
 * Canon: ShouldQueue, constructor injection, correlation_id in every log.
 */
final class DispatchMessageJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries   = 3;
    public int $timeout = 30;

    public function __construct(
        private readonly int    $messageId,
        private readonly string $channelType,
        private readonly string $correlationId,
    ) {
        $this->onQueue('communication');
    }

    public function handle(
        EmailChannelService    $email,
        SmsChannelService      $sms,
        PushChannelService     $push,
        TelegramChannelService $telegram,
        LogManager             $logger,
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

        $message->update(['status' => 'sent', 'sent_at' => now()]);

        $logger->channel('audit')->info('Message dispatched via channel', [
            'message_id'     => $this->messageId,
            'channel_type'   => $this->channelType,
            'correlation_id' => $this->correlationId,
        ]);
    }

    public function failed(\Throwable $e): void
    {
        Message::where('id', $this->messageId)->update(['status' => 'failed']);
    }
}
