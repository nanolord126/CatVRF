<?php

declare(strict_types=1);

namespace Modules\Restaurant\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;
use Illuminate\Notifications\Messages\MailMessage;
use Modules\Restaurant\Domain\Events\IoTDeviceAlertTriggered;

final class IoTAlertNotification extends Notification
{
    use Queueable;

    public function __construct(
        private readonly IoTDeviceAlertTriggered $event,
    ) {}

    public function via($notifiable): array
    {
        return ['mail', 'database'];
    }

    public function toMail($notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('IoT Device Alert: ' . $this->event->device->name)
            ->greeting('Hello!')
            ->line('An IoT device has triggered an alert:')
            ->line('Device: ' . $this->event->device->name)
            ->line('Alert: ' . $this->event->alertMessage)
            ->action('View Device', url('/iot-devices/' . $this->event->device->id))
            ->line('Please investigate this issue promptly.');
    }

    public function toArray($notifiable): array
    {
        return [
            'device_id' => $this->event->device->id,
            'device_name' => $this->event->device->name,
            'alert_message' => $this->event->alertMessage,
            'telemetry_data' => $this->event->telemetryData,
        ];
    }
}
