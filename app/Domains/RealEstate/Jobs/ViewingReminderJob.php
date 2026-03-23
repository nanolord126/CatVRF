<?php declare(strict_types=1);

namespace App\Domains\RealEstate\Jobs;

use App\Domains\RealEstate\Models\ViewingAppointment;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

/**
 * Job для отправки напоминания о просмотре.
 * Production 2026.
 */
final class ViewingReminderJob implements ShouldQueue
{
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

