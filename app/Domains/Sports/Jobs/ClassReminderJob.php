<?php declare(strict_types=1);

namespace App\Domains\Sports\Jobs;

use App\Domains\Sports\Models\Studio;
use App\Domains\Sports\Notifications\ClassReminderNotification;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;
use Throwable;

final class ClassReminderJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    private ?string $correlationId;

    public function __construct(string $correlationId = '')
    {
        $this->correlationId = $correlationId;
        $this->onQueue('notifications');

    }

    public function handle(): void
    {
        try {
            $this->log->channel('audit')->info('Running class reminder job', [
                'correlation_id' => $this->correlationId,
            ]);

            $classes = \App\Domains\Sports\Models\Class$this->session->where('starts_at', '>=', now())
                ->where('starts_at', '<=', now()->addHours(24))
                ->where('is_active', true)
                ->get();

            foreach ($classes as $class) {
                try {
                    $bookings = $class->bookings()
                        ->where('status', 'confirmed')
                        ->with('member')
                        ->get();

                    foreach ($bookings as $booking) {
                        try {
                            $booking->member->notify(new ClassReminderNotification($class));
                        } catch (Throwable $e) {
                            $this->log->channel('audit')->error('Failed to send class reminder', [
                                'booking_id' => $booking->id,
                                'class_id' => $class->id,
                                'error' => $e->getMessage(),
                            ]);
                        }
                    }

                    $this->log->channel('audit')->info('Class reminders sent', [
                        'class_id' => $class->id,
                        'booking_count' => $bookings->count(),
                    ]);
                } catch (Throwable $e) {
                    $this->log->channel('audit')->error('Failed to send class reminders', [
                        'class_id' => $class->id,
                        'error' => $e->getMessage(),
                    ]);
                }
            }

            $this->log->channel('audit')->info('Class reminder job completed', [
                'classes_count' => $classes->count(),
                'correlation_id' => $this->correlationId,
            ]);
        } catch (Throwable $e) {
            $this->log->channel('audit')->error('Class reminder job failed', [
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

