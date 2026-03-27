<?php

declare(strict_types=1);

namespace App\Services\CarRental;

use App\Models\CarRental\Car;
use App\Models\CarRental\CarType;
use App\Services\RecommendationService;
use App\Services\InventoryService;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Illuminate\Database\Eloquent\Collection;
use Throwable;

/**
 * AICarRecommendationConstructor.
 * Implementation: Layers 7 (AI & Logic Layer).
 * AI-driven vehicle matching based on budget, goals, and capacity.
 * Part of 2026 Canonical vertical implementation.
 */
final readonly class AICarRecommendationConstructor
{
    /**
     * Dependency injection for modular logic.
     */
    public function __construct(
        private \OpenAI\Client $openai, // AI Vision or GigaChat
        private RecommendationService $recommendation,
        private InventoryService $inventory
    ) {}

    /**
     * Core AI Matching Logic.
     * Takes unstructured user goals and returns structured vehicle recommendations.
     */
    public function analyzeAndMatchVehicle(
        string $userGoal, 
        int $budget, 
        int $personCount, 
        bool $isB2B, 
        string $correlationId
    ): array {
        try {
            // 1. Mandatory Audit Log (Canon Rule 2026)
            Log::channel('audit')->info('[CarAI] Starting vehicle matching', [
                'correlation_id' => $correlationId,
                'goal' => $userGoal,
                'budget' => $budget,
                'person_count' => $personCount,
            ]);

            // 2. Mock AI Analysis (Simulating deep neural matching)
            // In a real environment, this sends userGoal to LLM with vehicle catalog.
            $aiSuggestions = $this->simulateAIReasoning($userGoal, $personCount, $isB2B);

            // 3. Filter real fleet inventory for matches
            $matches = $this->matchFleetWithSuggestions($aiSuggestions, $budget, $personCount);

            // 4. Score matches based on UserTasteProfile v2.0 logic
            $scoredMatches = $this->scoreMatches($matches, $userGoal, $correlationId);

            // 5. Final Analytics Log
            Log::channel('recommend')->info('[CarAI] Vehicle matches found', [
                'count' => count($scoredMatches),
                'top_match' => $scoredMatches[0]['brand'] ?? 'none',
                'correlation_id' => $correlationId,
            ]);

            return [
                'success' => true,
                'correlation_id' => $correlationId,
                'ai_reasoning' => $aiSuggestions['logic_description'],
                'recommendations' => $scoredMatches,
            ];

        } catch (Throwable $e) {
            Log::channel('audit')->error('[CarAI] Recommendation Failed Critical Error!', [
                'correlation_id' => $correlationId,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            throw $e;
        }
    }

    /**
     * AI Simulation Logic (Layer 7: Logic Layer).
     */
    private function simulateAIReasoning(string $goal, int $personCount, bool $isB2B): array
    {
        $logic = "Goal analysis: '{$goal}'. Capacity: {$personCount} persons.";
        
        if ($personCount > 5) {
            return ['type' => 'SUV', 'brand_pref' => ['Mercedes', 'BMW', 'Land Rover'], 'logic_description' => "{$logic} Needs large capacity, matching with SUVs/Minivans."];
        }

        if ($isB2B) {
            return ['type' => 'Business', 'brand_pref' => ['Audi', 'Lexus', 'Volvo'], 'logic_description' => "{$logic} Business travel detected, prioritising comfort and status."];
        }

        return ['type' => 'Economy', 'brand_pref' => ['Kia', 'Hyundai', 'Toyota'], 'logic_description' => "{$logic} Standard personal travel, matching best price/reliability."];
    }

    /**
     * Match AI suggestions with physical fleet availability.
     */
    private function matchFleetWithSuggestions(array $ai, int $budget, int $personCount): Collection
    {
        return Car::with('type')
            ->where('status', 'available')
            ->whereHas('type', function ($q) use ($ budget, $personCount) {
                // Ensure daily price is within budget (max 30% of total budget per day)
                $maxDaily = (int) ($budget / 3);
                $q->where('daily_price_base', '<=', $maxDaily)
                  ->where('seats', '>=', $personCount);
            })
            ->limit(10)
            ->get();
    }

    /**
     * Scoring logic for found cars.
     */
    private function scoreMatches(Collection $cars, string $goal, string $correlationId): array
    {
        return $cars->map(function ($car) use ($goal) {
            $score = 0.85; // Base score

            // Hypothetical goal matching logic
            if (str_contains(strtolower($goal), 'eco') && ($car->attributes['fuel_type'] ?? '') === 'hybrid') {
                $score += 0.1;
            }

            return [
                'id' => $car->id,
                'uuid' => $car->uuid,
                'brand' => $car->brand,
                'model' => $car->model,
                'plate' => $car->plate_number,
                'daily_price' => $car->getBasePricePerDay(),
                'score' => min($score, 1.0),
                'confidence' => 'high',
            ];
        })->sortByDesc('score')->values()->toArray();
    }
}
