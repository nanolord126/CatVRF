<?php

namespace App\Services\Common\AI;

use App\Models\HRJobVacancy;
use App\Models\HRResume;
use App\Models\HRVacancyMatch;
use App\Models\B2BManufacturer;
use App\Services\AI\EcosystemAIService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use App\Traits\HasEcosystemTracing;

/**
 * B2B And HR Matching Service - 2026 Canon.
 * Implements semantic matching, skill overlap, and geo-proximity logic.
 */
class B2BAndHRMatchingService
{
    use HasEcosystemTracing;

    protected EcosystemAIService $aiService;
    private string $correlationId;

    public function __construct(EcosystemAIService $aiService)
    {
        $this->aiService = $aiService;
        $this->correlationId = Str::uuid()->toString();
    }

    /**
     * Set correlation ID для трейсинга операции.
     */
    private function setCorrelationId(string $id): void
    {
        $this->correlationId = $id;
    }

    /**
     * Generate new correlation ID.
     */
    private function generateCorrelationId(): string
    {
        return Str::uuid()->toString();
    }

    /**
     * Calculate AI Match Score for HR Job Vacancy.
     * Weights: 60% Semantic, 30% Skill Overlap, 10% Geo-proximity.
     */
    public function calculateHRMatch(HRJobVacancy $vacancy, HRResume $resume): array
    {
        $this->setCorrelationId($vacancy->correlation_id ?? $this->generateCorrelationId());

        // 1. Semantic Score (Vector Similarity)
        $vacancyText = $vacancy->title . ' ' . $vacancy->description;
        $resumeText = json_encode($resume->experience_history) . ' ' . ($resume->ai_skills_analysis['summary'] ?? '');
        
        $semanticScore = $this->getVectorSimilarity($vacancyText, $resumeText);

        // 2. Skill Overlap
        $vacancySkills = collect($vacancy->skills ?? []);
        $resumeSkills = collect($resume->skills ?? []);
        $intersectCount = $vacancySkills->intersect($resumeSkills)->count();
        $skillScore = $vacancySkills->isNotEmpty() ? $intersectCount / $vacancySkills->count() : 1.0;

        // 3. Geo-proximity (Simplified implementation)
        $geoScore = 1.0; // Default if no geo data
        if ($vacancy->latitude && $vacancy->longitude && $resume->user && $resume->user->latitude) {
            $distance = $this->calculateDistance(
                $vacancy->latitude, $vacancy->longitude,
                $resume->user->latitude, $resume->user->longitude
            );
            $geoScore = max(0, 1 - ($distance / 100)); // 100km radius scaling
        }

        $totalScore = ($semanticScore * 0.6) + ($skillScore * 0.3) + ($geoScore * 0.1);

        return [
            'total' => round($totalScore * 100, 2),
            'semantic' => round($semanticScore, 4),
            'skill' => round($skillScore, 4),
            'geo' => round($geoScore, 4),
            'reasons' => [
                'semantic_match' => $semanticScore > 0.7 ? 'Strong semantic alignment' : 'Low semantic match',
                'skill_match' => "Matches $intersectCount required skills",
                'geo_match' => $geoScore > 0.8 ? 'Local candidate' : 'Remote/Relocation likely'
            ]
        ];
    }

    /**
     * Calculate B2B Recommended Suppliers.
     * Weights: 40% Reliability, 40% Pricing, 20% Geo-logistics.
     */
    public function calculateB2BRecommendation(B2BManufacturer $manufacturer, array $tenantContext): array
    {
        // 1. Reliability Score (Based on Trust Score, 0-100 normalized to 0-1)
        $reliability = $manufacturer->getTrustScore() / 100.0;

        // 2. Pricing Match (Based on Category and historical data)
        $avgCost = DB::table('purchase_orders')
            ->where('supplier_id', $manufacturer->id)
            ->where('created_at', '>=', now()->subMonths(3))
            ->avg('total_amount') ?? 1000;
        
        $tenantAvgCost = DB::table('purchase_orders')
            ->where('tenant_id', auth('tenant')->user()->id)
            ->where('created_at', '>=', now()->subMonths(3))
            ->avg('total_amount') ?? 1000;
        
        // Чем ближе цена к средней, тем лучше (0-1)
        $priceDiff = abs($avgCost - $tenantAvgCost) / $tenantAvgCost;
        $pricing = max(0, 1 - $priceDiff);

        // 3. Geo-logistics
        $geoScore = 1.0;
        if (isset($tenantContext['latitude'], $tenantContext['longitude'])) {
            // Assume manufacturer has a main warehouse or office location in geo_coverage
            $geoData = $manufacturer->geo_coverage ?? [];
            $mLat = $geoData['lat'] ?? 0;
            $mLng = $geoData['lng'] ?? 0;
            
            if ($mLat !== 0 || $mLng !== 0) {
                $distance = $this->calculateDistance($tenantContext['latitude'], $tenantContext['longitude'], $mLat, $mLng);
                $geoScore = max(0, 1 - ($distance / 500)); // 500km radius for B2B logistics
            }
        }

        $totalScore = ($reliability * 0.4) + ($pricing * 0.4) + ($geoScore * 0.2);

        return [
            'total' => round($totalScore * 100, 2),
            'reliability' => $reliability,
            'pricing' => $pricing,
            'geo' => $geoScore,
            'reasons' => [
                'trust' => $reliability > 0.8 ? 'High ecosystem trust' : 'Standard trust level',
                'logistics' => $geoScore > 0.5 ? 'Efficient logistics route' : 'Extended delivery time'
            ]
        ];
    }

    private function getVectorSimilarity(string $textA, string $textB): float
    {
        // Генерируем embeddings для обоих текстов
        $embA = $this->aiService->generateEmbeddings(substr($textA, 0, 500));
        $embB = $this->aiService->generateEmbeddings(substr($textB, 0, 500));

        // Вычисляем косинусное сходство
        if (empty($embA) || empty($embB)) {
            return 0.5; // Fallback если embedding не сгенерирована
        }

        return $this->cosineSimilarity($embA, $embB);
    }

    /**
     * Вычислить косинусное сходство между двумя vectors.
     */
    private function cosineSimilarity(array $vecA, array $vecB): float
    {
        $dotProduct = 0;
        $normA = 0;
        $normB = 0;

        foreach (array_keys($vecA) as $key) {
            $a = $vecA[$key] ?? 0;
            $b = $vecB[$key] ?? 0;
            
            $dotProduct += $a * $b;
            $normA += $a * $a;
            $normB += $b * $b;
        }

        $denominator = sqrt($normA) * sqrt($normB);
        if ($denominator == 0) {
            return 0;
        }

        return ($dotProduct / $denominator);
    }

    private function calculateDistance($lat1, $lon1, $lat2, $lon2)
    {
        $theta = $lon1 - $lon2;
        $dist = sin(deg2rad($lat1)) * sin(deg2rad($lat2)) +  cos(deg2rad($lat1)) * cos(deg2rad($lat2)) * cos(deg2rad($theta));
        $dist = acos($dist);
        $dist = rad2deg($dist);
        $miles = $dist * 60 * 1.1515;
        return $miles * 1.609344;
    }
}
