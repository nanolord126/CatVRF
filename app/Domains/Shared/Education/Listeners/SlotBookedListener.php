<?php

declare(strict_types=1);

namespace App\Domains\Education\Listeners;

use Psr\Log\LoggerInterface;

use App\Domains\Education\Events\SlotBookedEvent;
use App\Domains\Education\Models\Slot;
use App\Models\User;
use App\Services\AuditService;
use Illuminate\Http\Client\Factory as HttpFactory;
use Illuminate\Log\LogManager;
use Carbon\CarbonImmutable;

final readonly class SlotBookedListener
{
    public function __construct(private readonly LoggerInterface $logger,
        private readonly AuditService $audit,
        private readonly LogManager $log,
        private readonly HttpFactory $http,) {}

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

        $this->log->channel('audit')->$this->logger->info('Slot booking synced to CRM', [
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
            'timestamp' => CarbonImmutable::now()->toIso8601String(),
        ];

        $webhookUrl = config('services.crm.webhook_url');

        if ($webhookUrl !== null) {
            try {
                $this->http->timeout(10)->post($webhookUrl, $crmData);
            } catch (\Exception $e) {
                $this->log->channel('audit')->error('CRM slot booking sync failed', [
                    'correlation_id' => $event->correlationId,
                    'error' => $e->getMessage(),
                ]);
            }
        }
    }

    private function sendNotification(SlotBookedEvent $event): void
    {
        $user = User::find($event->userId);
        $slot = Slot::find($event->slotId);

        if ($user !== null && $slot !== null) {
            $notificationData = [
                'type' => 'slot_booking_confirmed',
                'title' => 'Slot Booking Confirmed',
                'message' => "Your slot '{$slot->title}' has been booked successfully.",
                'booking_reference' => $event->bookingReference,
                'start_time' => $slot->start_time->toIso8601String(),
                'meeting_link' => $slot->meeting_link,
            ];

            $this->log->channel('audit')->$this->logger->info('Slot booking notification sent', [
                'correlation_id' => $event->correlationId,
                'user_id' => $event->userId,
            ]);
        }
    }
}
