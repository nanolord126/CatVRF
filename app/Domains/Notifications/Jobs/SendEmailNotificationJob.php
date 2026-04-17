<?php declare(strict_types=1);

namespace App\Domains\Notifications\Jobs;

use App\Domains\Notifications\Models\Notification;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Log;

final class SendEmailNotificationJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(
        public readonly int $notificationId,
        public readonly string $correlationId,
    ) {}

    public function onQueue(): string
    {
        return 'notifications';
    }

    public function handle(): void
    {
        $notification = Notification::findOrFail($this->notificationId);

        try {
            Mail::raw($notification->body, function ($message) use ($notification) {
                $message->to($notification->user->email)
                    ->subject($notification->title);
            });

            $notification->update(['delivered_at' => now()]);

            Log::channel('notifications')->info('Email notification sent', [
                'notification_id' => $notification->id,
                'correlation_id' => $this->correlationId,
            ]);
        } catch (\Exception $e) {
            $notification->update([
                'failed_at' => now(),
                'error_message' => $e->getMessage(),
            ]);

            Log::channel('notifications')->error('Failed to send email notification', [
                'notification_id' => $notification->id,
                'error' => $e->getMessage(),
                'correlation_id' => $this->correlationId,
            ]);

            throw $e;
        }
    }
}
