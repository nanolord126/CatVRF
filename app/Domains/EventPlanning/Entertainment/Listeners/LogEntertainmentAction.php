<?php declare(strict_types=1);

namespace App\Domains\EventPlanning\Entertainment\Listeners;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

final class LogEntertainmentAction extends Model
{
    use HasFactory;

    // TODO: Проверить и восстановить содержимое класса, если оно было утеряно
    public function handle(object $event): void
        {
            $payload = [];

            if ($event instanceof BookingCreatedEvent) {
                $payload = [
                    'type' => 'booking_created',
                    'booking_id' => $event->booking->id,
                    'uuid' => $event->booking->uuid,
                    'amount' => $event->booking->total_amount_kopecks,
                ];
            }

            Log::channel('audit')->info('Entertainment action audit', array_merge($payload, [
                'correlation_id' => $event->correlationId ?? 'unknown',
                'timestamp' => now()->toIso8601String(),
            ]));
        }
}
