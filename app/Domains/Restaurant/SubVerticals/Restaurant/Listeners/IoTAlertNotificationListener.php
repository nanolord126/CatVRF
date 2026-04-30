<?php

declare(strict_types=1);

namespace Modules\Restaurant\Listeners;

use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Notification;
use Modules\Restaurant\Domain\Events\IoTDeviceAlertTriggered;
use Modules\Restaurant\Notifications\IoTAlertNotification;

final class IoTAlertNotificationListener
{
    public function handle(IoTDeviceAlertTriggered $event): void
    {
        Log::warning('IoT device alert triggered', [
            'device_id' => $event->device->id,
            'device_name' => $event->device->name,
            'alert_message' => $event->alertMessage,
        ]);

        // Send notification to restaurant manager
        // In production, this would send to the appropriate users
        // $users = $this->getRestaurantManagers($event->device->tenantId);
        // Notification::send($users, new IoTAlertNotification($event));
    }
}
