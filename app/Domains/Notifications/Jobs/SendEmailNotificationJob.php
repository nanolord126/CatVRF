<?php

declare(strict_types=1);

namespace App\Domains\Notifications\Jobs;

use Illuminate\Notifications\ChannelManager;

use Psr\Log\LoggerInterface;

use Carbon\CarbonImmutable;

use App\Domains\Notifications\Models\Notification;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Contracts\Mail\Mailer;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Log\LogManager;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

final class SendEmailNotificationJob implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    public function __construct(private readonly ChannelManager $notificationManager,
        private readonly LoggerInterface $logger,
        public readonly int $notificationId,
        public readonly string $correlationId,
        private readonly Mailer $mailer,
        private readonly LogManager $log,) {}

    public function onQueue(): string
    {
        return 'notifications';
    }

    public function handle(): void
    {
        $notification = $this->notificationManager->findOrFail($this->notificationId);

        try {
            $this->mailer->raw($notification->body, function ($message) use ($notification) {
                $message->to($notification->user->email)
                    ->subject($notification->title);
            });

            $notification->update(['delivered_at' => CarbonImmutable::now()]);

            $this->log->channel('notifications')->$this->logger->info('Email notification sent', [
                'notification_id' => $notification->id,
                'correlation_id' => $this->correlationId,
            ]);
        } catch (\Exception $e) {
            $notification->update([
                'failed_at' => CarbonImmutable::now(),
                'error_message' => $e->getMessage(),
            ]);

            $this->log->channel('notifications')->error('Failed to send email notification', [
                'notification_id' => $notification->id,
                'error' => $e->getMessage(),
                'correlation_id' => $this->correlationId,
            ]);

            throw $e;
        }
    }
}
