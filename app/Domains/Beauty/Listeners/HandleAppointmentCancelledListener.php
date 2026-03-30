<?php declare(strict_types=1);

namespace App\Domains\Beauty\Listeners;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

final class HandleAppointmentCancelledListener extends Model
{
    use HasFactory;

    // TODO: Проверить и восстановить содержимое класса, если оно было утеряно
    public function handle(AppointmentCancelled $event): void
        {
            $appointment = $event->appointment;

            // Release held consumables (inventory)
            if ($appointment->status === 'cancelled' && $appointment->held_consumables) {
                app(\App\Services\InventoryManagementService::class)->releaseStock(
                    $appointment->id,
                    'appointment',
                    $event->correlationId
                );
            }

            // Notify client about cancellation
            if ($appointment->client) {
                $this->notification->send(
                    $appointment->client,
                    new \App\Notifications\AppointmentCancelledNotification(
                        $appointment,
                        $event->reason
                    )
                );
            }

            Log::channel('audit')->info('AppointmentCancelled event handled', [
                'appointment_id' => $appointment->id,
                'reason' => $event->reason,
                'correlation_id' => $event->correlationId,
            ]);
        }
}
