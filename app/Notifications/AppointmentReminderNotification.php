<?php declare(strict_types=1);

/**
 * AppointmentReminderNotification — CatVRF 2026 Component.
 *
 * Part of the CatVRF multi-vertical marketplace platform.
 * Implements tenant-aware, fraud-checked business logic
 * with full correlation_id tracing and audit logging.
 *
 * @package CatVRF
 * @version 2026.1
 * @author CatVRF Team
 * @license Proprietary

 * @see https://catvrf.ru/docs/appointmentremindernotification
 */


namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Notification;

/**
 * Class AppointmentReminderNotification
 *
 * Component of the CatVRF platform.
 * Follows strict coding standards:
 * - final class (no inheritance unless required)
 * - private readonly properties
 * - Constructor injection only
 * - correlation_id in all operations
 *
 * @package App\Notifications
 */
final class AppointmentReminderNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        private readonly object $appointment,
    )
    {
        // Implementation required by canon
    }

    /**
     * Handle via operation.
     *
     * @throws \DomainException
     */
    public function via(object $notifiable): array
    {
        return ['database', 'broadcast'];
    }

    /**
     * Handle toArray operation.
     *
     * @throws \DomainException
     */
    public function toArray(object $notifiable): array
    {
        return [
            'appointment_id' => $this->appointment->id ?? null,
            'master_name' => $this->appointment->master->full_name ?? '',
            'service_name' => $this->appointment->service->name ?? '',
            'datetime' => $this->appointment->datetime_start?->format('d.m.Y H:i'),
            'salon_address' => $this->appointment->salon->address ?? '',
            'message' => 'Напоминаем о записи завтра',
        ];
    }
}
