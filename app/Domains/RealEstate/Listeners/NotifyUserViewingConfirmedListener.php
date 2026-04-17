<?php

declare(strict_types=1);

namespace App\Domains\RealEstate\Listeners;

use App\Domains\RealEstate\Events\ViewingConfirmedEvent;
use App\Services\NotificationService;
use Illuminate\Support\Facades\Log;

final class NotifyUserViewingConfirmedListener
{
    public function __construct(
        private readonly NotificationService $notificationService
    ) {}

    public function handle(ViewingConfirmedEvent $event): void
    {
        $viewing = $event->viewing;

        try {
            $this->notificationService->send(
                userId: $viewing->user_id,
                type: 'viewing_confirmed',
                title: 'Просмотр недвижимости подтверждён',
                message: "Ваш просмотр объекта #{$viewing->property_id} подтверждён на {$viewing->scheduled_at->format('d.m.Y H:i')}",
                data: [
                    'viewing_id' => $viewing->id,
                    'viewing_uuid' => $viewing->uuid,
                    'property_id' => $viewing->property_id,
                    'scheduled_at' => $viewing->scheduled_at->toIso8601String(),
                    'webrtc_room_id' => $viewing->webrtc_room_id,
                    'agent_id' => $viewing->agent_id,
                ],
                channels: ['push', 'email', 'sms'],
                correlationId: $event->correlationId
            );

            Log::channel('audit')->info('User notified about viewing confirmation', [
                'viewing_id' => $viewing->id,
                'user_id' => $viewing->user_id,
                'property_id' => $viewing->property_id,
                'correlation_id' => $event->correlationId,
            ]);

        } catch (\Exception $e) {
            Log::channel('audit')->error('Failed to notify user about viewing confirmation', [
                'viewing_id' => $viewing->id,
                'user_id' => $viewing->user_id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'correlation_id' => $event->correlationId,
            ]);
        }
    }

    public function shouldQueue(ViewingConfirmedEvent $event): bool
    {
        return true;
    }
}
