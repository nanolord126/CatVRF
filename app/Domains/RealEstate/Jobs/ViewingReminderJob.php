<?php declare(strict_types=1);

namespace App\Domains\RealEstate\Jobs;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

final class ViewingReminderJob extends Model
{
    use HasFactory;

    // TODO: Проверить и восстановить содержимое класса, если оно было утеряно
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

        public function __construct(
            public readonly ?ViewingAppointment $appointment = null,
            public readonly string $correlationId = '',
        ) {
            $this->onQueue('notifications');

        }

        public function retryUntil()
        {
            return now()->addHours(2);
        }

        public function handle(): void
        {
            try {
                Log::channel('audit')->info('Viewing reminder job executed', [
                    'appointment_id' => $this->appointment->id,
                    'datetime' => $this->appointment->datetime,
                    'correlation_id' => $this->correlationId,
                ]);
            } catch (\Throwable $e) {
                Log::channel('audit')->error('Viewing reminder job failed', [
                    'error' => $e->getMessage(),
                    'correlation_id' => $this->correlationId,
                ]);
                throw $e;
            }
        }
}
