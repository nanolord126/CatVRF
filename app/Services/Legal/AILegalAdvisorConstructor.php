<?php

declare(strict_types=1);

namespace App\Services\Legal;

use App\Models\Legal\Lawyer;
use App\Models\Legal\LegalService;
use App\Services\FraudControlService;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

/**
 * AILegalAdvisorConstructor - AI-Powered Legal Advisor Constructor (CAR 2026)
 * Core AI logic for matching lawyers and services based on case type, budget, and urgency.
 */
final readonly class AILegalAdvisorConstructor
{
    /**
     * Constructor injection for required dependencies.
     */
    public function __construct(
        private readonly FraudControlService $fraudControl,
        private readonly PricingService $pricing,
    ) {}

    /**
     * Recommend lawyers and services based on user case details.
     */
    public function constructAdvisorRecommendation(
        string $caseType,
        int $budgetInCents,
        bool $isUrgent = false,
        string $region = 'Москва',
        string $correlationId = null
    ): array {
        $correlationId = $correlationId ?? (string) Str::uuid();

        Log::channel('audit')->info('AI Advisor Constructor initiated', [
            'case_type' => $caseType,
            'budget' => $budgetInCents,
            'urgent' => $isUrgent,
            'region' => $region,
            'correlation_id' => $correlationId,
        ]);

        // 1. Filter Lawyers by specialization and region
        $recommendedLawyers = Lawyer::active()
            ->whereJsonContains('categories', $caseType)
            ->whereHas('firm', function ($query) use ($region) {
                $query->where('city', $region);
            })
            ->where('consultation_price', '<=', $budgetInCents)
            ->orderBy('experience_years', 'desc')
            ->limit(3)
            ->get();

        // 2. Filter Legal Services by type
        $recommendedServices = LegalService::where('type', 'like', "%{$caseType}%")
            ->where('base_price', '<=', $budgetInCents)
            ->limit(2)
            ->get();

        // 3. AI scoring and matching
        $recommendations = [];
        foreach ($recommendedLawyers as $lawyer) {
            $score = $this->calculateMatchScore($lawyer, $caseType, $isUrgent);
            
            $recommendations[] = [
                'lawyer' => [
                    'uuid' => $lawyer->uuid,
                    'full_name' => $lawyer->full_name,
                    'firm' => $lawyer->firm?->name,
                    'price' => $this->pricing->format($lawyer->consultation_price),
                    'experience' => "{$lawyer->experience_years} лет",
                ],
                'match_score' => $score,
                'urgent_ready' => $isUrgent && $lawyer->is_active,
            ];
        }

        // 4. Wrap with metadata
        $result = [
            'success' => true,
            'correlation_id' => $correlationId,
            'recommendations' => collect($recommendations)->sortByDesc('match_score')->values()->toArray(),
            'suggested_services' => $recommendedServices->map(function ($service) {
                return [
                    'name' => $service->name,
                    'price' => $this->pricing->format($service->base_price),
                    'type' => $service->type,
                ];
            }),
            'disclaimer' => 'AI рекомендации носят информационный характер и соответствуют ФЗ-152.',
        ];

        Log::channel('audit')->info('AI Advisor Constructor completed successfully', [
            'matches_found' => count($recommendations),
            'correlation_id' => $correlationId,
        ]);

        return $result;
    }

    /**
     * Calculate internal match score (0.0 - 1.0) for lawyer.
     */
    private function calculateMatchScore(Lawyer $lawyer, string $caseType, bool $isUrgent): float
    {
        $score = 0.5; // Base score

        // Specialization weight
        if (in_array($caseType, $lawyer->categories ?? [])) {
            $score += 0.3;
        }

        // Experience weight
        $score += min(0.2, ($lawyer->experience_years / 50));

        // Urgency weight
        if ($isUrgent && $lawyer->is_active) {
            $score += 0.1;
        }

        return round($score, 2);
    }

    /**
     * Fetch list of available case categories.
     */
    public function getCategories(): array
    {
        return [
            'civil' => 'Гражданское право',
            'criminal' => 'Уголовное право',
            'corporate' => 'Корпоративное право',
            'notary' => 'Нотариальные услуги',
            'arbitration' => 'Арбитраж',
            'family' => 'Семейное право',
            'real_estate' => 'Недвижимость',
            'labor' => 'Трудовое право',
        ];
    }
}
