<?php declare(strict_types=1);

namespace App\Domains\Auto\Jobs;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

final class CarWashReminderJob extends Model
{
    use HasFactory;

    // TODO: Проверить и восстановить содержимое класса, если оно было утеряно
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

        private readonly string $correlationId;
        private readonly string $type; // '24h' or '2h'

        public function __construct(string $type = '24h')
        {
            $this->correlationId = Str::uuid()->toString();
            $this->type = $type;
            $this->onQueue('notifications');
        }

        public function handle(): void
        {
            Log::channel('audit')->info('Car wash reminder job started', [
                'correlation_id' => $this->correlationId,
                'type' => $this->type,
            ]);

            try {
                $targetTime = $this->type === '24h'
                    ? now()->addHours(24)
                    : now()->addHours(2);

                $bookings = CarWashBooking::where('status', 'confirmed')
                    ->whereBetween('scheduled_at', [
                        $targetTime->subMinutes(30),
                        $targetTime->addMinutes(30),
                    ])
                    ->whereDoesntHave('reminders', function ($query) {
                        $query->where('type', $this->type)
                            ->where('sent_at', '>=', now()->subHours(1));
                    })
                    ->with('user')
                    ->get();

                foreach ($bookings as $booking) {
                    $booking->user->notify(new CarWashReminderNotification($booking, $this->type));
                }

                Log::channel('audit')->info('Car wash reminders sent', [
                    'correlation_id' => $this->correlationId,
                    'type' => $this->type,
                    'sent_count' => $bookings->count(),
                ]);
            } catch (\Throwable $e) {
                Log::channel('audit')->error('Car wash reminder job failed', [
                    'correlation_id' => $this->correlationId,
                    'error' => $e->getMessage(),
                    'trace' => $e->getTraceAsString(),
                ]);

                throw $e;
            }
        }

        public function tags(): array
        {
            return ['auto', 'car-wash', 'reminder', $this->type, $this->correlationId];
        }
}
