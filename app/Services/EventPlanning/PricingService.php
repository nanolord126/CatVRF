<?php

declare(strict_types=1);

namespace App\Services\EventPlanning;

use App\Models\EventPlanning\EventProject;
use App\Models\EventPlanning\EventService;
use App\Models\EventPlanning\EventVenue;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

/**
 * PricingService (EventPlanning).
 * Implementation: Layer 4 (Business Rules Layer).
 * Dynamic pricing calculation based on B2B multipliers, guest count, and seasonality.
 * Uses: Total services cost, venue cost, and guest modifiers.
 */
final readonly class PricingService
{
    /**
     * Standard calculation for event total cost.
     * Incorporates B2B multipliers, service tiers, and peak modifiers.
     */
    public function calculateTotal(EventProject $event, array $serviceIds = [], ?int $venueId = null): array
    {
        $correlationId = $event->correlation_id ?? (string) Str::uuid();

        // 1. Base Services Calculation
        $servicesTotal = 0;
        $services = EventService::whereIn('id', $serviceIds)->get();
        foreach ($services as $service) {
            $servicesTotal += (int) $service->base_price;
        }

        // 2. Venue Calculation
        $venueTotal = 0;
        if ($venueId) {
            $venue = EventVenue::find($venueId);
            // Default 6 hours rental if venue selected
            $venueTotal = (int) ($venue->price_per_hour * 6);
        }

        // 3. Guest Modifier (Layer 4: Business Rules)
        // If guests > 100, add 10% operational surcharge
        $guestSurcharge = ($event->guest_count > 100) ? 1.10 : 1.0;
        $subtotal = (int) (($servicesTotal + $venueTotal) * $guestSurcharge);

        // 4. B2B / Corporate Rules (Canon 2026: Multi-day/Corporate logic)
        // B2B gets fixed 20% discount but +15% operational surcharge
        $businessMultiplier = ($event->type === 'b2b') ? 0.80 * 1.15 : 1.0;
        $finalTotal = (int) ($subtotal * $businessMultiplier);

        // 5. Prepayment Rules (Canon 2026: Mandatory 30% for B2C)
        $prepaymentRequired = ($event->type === 'b2b') ? (int)($finalTotal * 0.20) : (int)($finalTotal * 0.30);

        // 6. Audit Log (Canon Rule 2026: Traceable calculations)
        Log::channel('audit')->info('[EventPricing] Complete Calculation', [
            'event_uuid' => $event->uuid,
            'correlation_id' => $correlationId,
            'base_services' => $servicesTotal,
            'venue' => $venueTotal,
            'guest_surcharge' => $guestSurcharge,
            'type' => $event->type,
            'final_total' => $finalTotal,
            'prepayment_required' => $prepaymentRequired,
        ]);

        return [
            'total' => $finalTotal,
            'prepayment' => $prepaymentRequired,
            'currency' => 'RUB',
            'breakdown' => [
                'services_sum' => $servicesTotal,
                'venue_sum' => $venueTotal,
                'operational_charge' => $subtotal - ($servicesTotal + $venueTotal),
                'b2b_adjustment' => $finalTotal - $subtotal,
            ],
            'correlation_id' => $correlationId,
        ];
    }

    /**
     * Logic: Seasonal Modifier (Tiering Rule).
     */
    public function getSeasonalModifier(\DateTime $date): float
    {
        $month = (int) $date->format('m');
        // Peak season (December, June, August) has +20% surcharge
        if (in_array($month, [12, 6, 8])) {
            return 1.20;
        }

        return 1.0;
    }
}
