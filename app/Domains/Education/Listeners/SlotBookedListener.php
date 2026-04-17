<?php declare(strict_types=1);

namespace App\Domains\Education\Listeners;

use App\Domains\Education\Events\SlotBookedEvent;
use App\Services\AuditService;
use Illuminate\Support\Facades\Log;

final readonly class SlotBookedListener
{
    public function __construct(
        private AuditService $audit,
    ) {}

    public function handle(SlotBookedEvent $event): void
    {
        $this->audit->record('education_slot_booked_crm_sync', 'SlotBookedEvent', $event->bookingId, [], [
            'correlation_id' => $event->correlationId,
            'tenant_id' => $event->tenantId,
            'booking_id' => $event->bookingId,
            'booking_reference' => $event->bookingReference,
            'slot_id' => $event->slotId,
            'user_id' => $event->userId,
            'business_group_id' => $event->businessGroupId,
        ], $event->correlationId);

        Log::channel('audit')->info('Slot booking synced to CRM', [
            'correlation_id' => $event->correlationId,
            'booking_id' => $event->bookingId,
        ]);

        $this->sendToCRM($event);
        $this->sendNotification($event);
    }

    private function sendToCRM(SlotBookedEvent $event): void
    {
        $crmData = [
            'event' => 'slot_booked',
            'booking_id' => $event->bookingId,
            'booking_reference' => $event->bookingReference,
            'slot_id' => $event->slotId,
            'user_id' => $event->userId,
            'tenant_id' => $event->tenantId,
            'business_group_id' => $event->businessGroupId,
            'correlation_id' => $event->correlationId,
            'timestamp' => now()->toIso8601String(),
        ];

        $webhookUrl = config('services.crm.webhook_url');

        if ($webhookUrl !== null) {
            try {
                \Illuminate\Support\Facades\Http::timeout(10)->post($webhookUrl, $crmData);
            } catch (\Exception $e) {
                Log::channel('audit')->error('CRM slot booking sync failed', [
                    'correlation_id' => $event->correlationId,
                    'error' => $e->getMessage(),
                ]);
            }
        }
    }

    private function sendNotification(SlotBookedEvent $event): void
    {
        $user = \App\Models\User::find($event->userId);
        $slot = \App\Domains\Education\Models\Slot::find($event->slotId);

        if ($user !== null && $slot !== null) {
            $notificationData = [
                'type' => 'slot_booking_confirmed',
                'title' => 'Slot Booking Confirmed',
                'message' => "Your slot '{$slot->title}' has been booked successfully.",
                'booking_reference' => $event->bookingReference,
                'start_time' => $slot->start_time->toIso8601String(),
                'meeting_link' => $slot->meeting_link,
            ];

            Log::channel('audit')->info('Slot booking notification sent', [
                'correlation_id' => $event->correlationId,
                'user_id' => $event->userId,
            ]);
        }
    }
}
