<?php

declare(strict_types=1);

namespace App\Domains\RealEstate\Listeners;

use App\Domains\RealEstate\Events\ViewingBookedEvent;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Http;

final class SyncViewingToCRMListener
{
    public function handle(ViewingBookedEvent $event): void
    {
        $viewing = $event->viewing;
        
        try {
            $crmData = [
                'event_type' => 'viewing_booked',
                'viewing_id' => $viewing->id,
                'viewing_uuid' => $viewing->uuid,
                'property_id' => $viewing->property_id,
                'user_id' => $viewing->user_id,
                'agent_id' => $viewing->agent_id,
                'scheduled_at' => $viewing->scheduled_at->toIso8601String(),
                'status' => $viewing->status,
                'is_b2b' => $viewing->is_b2b,
                'webrtc_room_id' => $viewing->webrtc_room_id,
                'tenant_id' => $viewing->tenant_id,
                'business_group_id' => $viewing->business_group_id,
                'correlation_id' => $event->correlationId,
                'metadata' => $viewing->metadata,
            ];

            $response = Http::timeout(5)
                ->retry(3, 100)
                ->post(config('services.crm.endpoint', 'https://api.crm.internal/v1/events'), $crmData);

            if ($response->successful()) {
                Log::channel('audit')->info('Viewing synced to CRM successfully', [
                    'viewing_id' => $viewing->id,
                    'crm_response' => $response->json(),
                    'correlation_id' => $event->correlationId,
                ]);
            } else {
                Log::channel('audit')->warning('CRM sync failed', [
                    'viewing_id' => $viewing->id,
                    'status' => $response->status(),
                    'response' => $response->body(),
                    'correlation_id' => $event->correlationId,
                ]);
            }
        } catch (\Exception $e) {
            Log::channel('audit')->error('CRM sync error', [
                'viewing_id' => $viewing->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'correlation_id' => $event->correlationId,
            ]);
        }
    }

    public function shouldQueue(ViewingBookedEvent $event): bool
    {
        return true;
    }
}
