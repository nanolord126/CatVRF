<?php declare(strict_types=1);

namespace App\Domains\Tickets\Jobs;

use App\Domains\Tickets\Models\Event;
use App\Domains\Tickets\Notifications\EventStartingNotification;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Throwable;
use Carbon\Carbon;

final class EventReminderJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    private string $correlationId;

    public function __construct(string $correlationId = '')
    {
        $this->correlationId = $correlationId;
        $this->onQueue('notifications');
        $this->tags(['tickets', 'reminders', 'events']);
    }

    public function handle(): void
    {
        try {
            Log::channel('audit')->info('Running event reminder job', [
                'correlation_id' => $this->correlationId,
            ]);

            // Find events starting in the next 24 hours
            $events = Event::where('starts_at', '>=', now())
                ->where('starts_at', '<=', now()->addHours(24))
                ->where('status', 'published')
                ->get();

            foreach ($events as $event) {
                try {
                    // Get all ticket buyers
                    $buyers = $event->sales()
                        ->where('payment_status', 'paid')
                        ->distinct('buyer_id')
                        ->pluck('buyer_id');

                    foreach ($buyers as $buyerId) {
                        try {
                            $buyer = \App\Models\User::find($buyerId);
                            if ($buyer) {
                                $buyer->notify(new EventStartingNotification($event));
                            }
                        } catch (Throwable $e) {
                            Log::channel('audit')->error('Failed to send reminder to buyer', [
                                'buyer_id' => $buyerId,
                                'event_id' => $event->id,
                                'error' => $e->getMessage(),
                                'correlation_id' => $this->correlationId,
                            ]);
                        }
                    }

                    Log::channel('audit')->info('Event reminders sent', [
                        'event_id' => $event->id,
                        'buyer_count' => count($buyers),
                        'correlation_id' => $this->correlationId,
                    ]);
                } catch (Throwable $e) {
                    Log::channel('audit')->error('Failed to send event reminders', [
                        'event_id' => $event->id,
                        'error' => $e->getMessage(),
                        'correlation_id' => $this->correlationId,
                    ]);
                }
            }

            Log::channel('audit')->info('Event reminder job completed', [
                'events_count' => $events->count(),
                'correlation_id' => $this->correlationId,
            ]);
        } catch (Throwable $e) {
            Log::channel('audit')->error('Event reminder job failed', [
                'error' => $e->getMessage(),
                'correlation_id' => $this->correlationId,
            ]);
            $this->fail($e);
        }
    }

    public function retryUntil(): \DateTime
    {
        return now()->addHours(6);
    }
}
