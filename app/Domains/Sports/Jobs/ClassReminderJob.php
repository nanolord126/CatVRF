<?php declare(strict_types=1);

namespace App\Domains\Sports\Jobs;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

final class ClassReminderJob extends Model
{
    use HasFactory;

    // TODO: Проверить и восстановить содержимое класса, если оно было утеряно
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
                Log::channel('audit')->info('Running class reminder job', [
                    'correlation_id' => $this->correlationId,
                ]);

                $classes = \App\Domains\Sports\Models\ClassSession::where('starts_at', '>=', now())
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
                                Log::channel('audit')->error('Failed to send class reminder', [
                                    'booking_id' => $booking->id,
                                    'class_id' => $class->id,
                                    'error' => $e->getMessage(),
                                ]);
                            }
                        }

                        Log::channel('audit')->info('Class reminders sent', [
                            'class_id' => $class->id,
                            'booking_count' => $bookings->count(),
                        ]);
                    } catch (Throwable $e) {
                        Log::channel('audit')->error('Failed to send class reminders', [
                            'class_id' => $class->id,
                            'error' => $e->getMessage(),
                        ]);
                    }
                }

                Log::channel('audit')->info('Class reminder job completed', [
                    'classes_count' => $classes->count(),
                    'correlation_id' => $this->correlationId,
                ]);
            } catch (Throwable $e) {
                Log::channel('audit')->error('Class reminder job failed', [
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
