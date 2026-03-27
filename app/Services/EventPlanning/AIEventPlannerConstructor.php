<?php

declare(strict_types=1);

namespace App\Services\EventPlanning;

use App\Models\EventPlanning\EventService;
use App\Models\EventPlanning\EventVenue;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

/**
 * AIEventPlannerConstructor (Smart Plan Generator).
 * Implementation: Layer 7 (AI/ML Constructor Layer).
 * Simulates AI-based event planning based on budget, guest count, and theme.
 * Mandatory: correlation_id, full plan generation, budget-aware.
 */
final readonly class AIEventPlannerConstructor
{
    /**
     * Analyze and Generate a complex wedding/event plan.
     * Includes logic: AI-matching, budget allocation, and tier-based selection.
     */
    public function generatePlan(
        int $guestCount,
        string $theme,
        int $budgetLimit,
        bool $isB2B = false,
        string $correlationId = null
    ): array {
        $correlationId = $correlationId ?? (string) Str::uuid();

        // 1. Audit Start (Canon 2026: Traceable logic)
        Log::channel('audit')->info('[AIPlanner] Generating Plan', [
            'correlation_id' => $correlationId,
            'guest_count' => $guestCount,
            'theme' => $theme,
            'budget' => $budgetLimit,
            'is_b2b' => $isB2B,
        ]);

        // 2. Budget Allocation Logic (AI Reasoning simulation)
        // Venue 30%, Catering 40%, Decor 15%, Entertainment 15%
        $venueAllocation = (int) ($budgetLimit * 0.30);
        $cateringAllocation = (int) ($budgetLimit * 0.40);
        $decorAllocation = (int) ($budgetLimit * 0.15);
        $entertainmentAllocation = (int) ($budgetLimit * 0.15);

        // 3. Selection of Matching Services (Layer 7: Analysis Layer)
        $matchingVenues = EventVenue::where('capacity_max', '>=', $guestCount)
            ->where('price_per_hour', '<=', $venueAllocation / 6) // Assumes 6h max
            ->limit(3)
            ->get();

        $matchingDecor = EventService::where('category', 'decor')
            ->where('base_price', '<=', $decorAllocation)
            ->where('tags', 'LIKE', "%{$theme}%")
            ->limit(5)
            ->get();

        $matchingEntertainment = EventService::whereIn('category', ['hosting', 'music'])
            ->where('base_price', '<=', $entertainmentAllocation)
            ->limit(5)
            ->get();

        // 4. Recommendation Construction (Simulation of AI logic)
        $recommendationScore = ($matchingVenues->count() > 0 && $matchingDecor->count() > 0) ? 0.95 : 0.60;

        $plan = [
            'title' => "AI Suggested Plan: {$theme} for {$guestCount} guests",
            'confidence_score' => $recommendationScore,
            'budget_summary' => [
                'total_budget' => $budgetLimit,
                'is_b2b_multiplier' => $isB2B,
                'per_guest' => (int)($budgetLimit / $guestCount),
            ],
            'selected_venue' => $matchingVenues->first(), // AI selects best fit
            'selected_decor' => $matchingDecor->first(),
            'selected_entertainment' => $matchingEntertainment->first(),
            'itinerary_draft' => $this->generateItinerary($theme, $guestCount),
            'audit' => [
                'correlation_id' => $correlationId,
                'generated_at' => now()->toIso8601String(),
                'logic_version' => '2026.AI.Event.1.0',
            ],
        ];

        // 5. Final Audit Log
        Log::channel('audit')->info('[AIPlanner] Plan Generated', [
            'correlation_id' => $correlationId,
            'score' => $recommendationScore,
            'venue' => $plan['selected_venue']?->name ?? 'None',
        ]);

        return $plan;
    }

    /**
     * Internal Logic: Itinerary Generation based on theme (Layer 7: Analysis).
     */
    private function generateItinerary(string $theme, int $guestCount): array
    {
        $slots = [];

        if (Str::contains(Str::lower($theme), 'wedding')) {
            $slots = [
                '14:00' => 'Welcome drinks for guests',
                '15:00' => 'Ceremony start',
                '16:30' => 'Reception and group photos',
                '18:30' => 'Dinner starts for ' . $guestCount . ' people',
                '21:00' => 'Party time',
            ];
        } elseif (Str::contains(Str::lower($theme), 'corporate')) {
             $slots = [
                '10:00' => 'Morning coffee and check-in',
                '11:00' => 'Welcome address',
                '13:00' => 'Lunch break',
                '15:00' => 'Team building activity',
                '17:00' => 'Evening drinks',
            ];
        } else {
             $slots = [
                '18:00' => 'Opening',
                '19:00' => 'Main entertainment program',
                '22:00' => 'Closing',
            ];
        }

        return $slots;
    }
}
