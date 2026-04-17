<?php declare(strict_types=1);

namespace Modules\Beauty\Services\AI;

use App\Services\FraudControlService;
use App\Services\AuditService;
use App\Services\RecommendationService;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

final readonly class BeautyAIConsultantService
{
    private const CACHE_TTL = 3600;

    public function __construct(
        private FraudControlService $fraud,
        private AuditService $audit,
        private RecommendationService $recommendation,
    ) {}

    public function getPersonalizedRecommendations(
        int $userId,
        ?string $skinType = null,
        ?string $hairType = null,
        ?string $concern = null,
        string $correlationId = 'default',
    ): array {
        $this->fraud->check([
            'operation_type' => 'beauty_ai_consultant',
            'user_id' => $userId,
            'correlation_id' => $correlationId,
        ]);

        $cacheKey = "beauty:ai:recommendations:{$userId}:" . md5("{$skinType}:{$hairType}:{$concern}");
        $cached = Cache::get($cacheKey);

        if ($cached !== null) {
            return json_decode($cached, true);
        }

        $recommendations = $this->analyzeBeautyProfile($userId, $skinType, $hairType, $concern, $correlationId);

        Cache::set($cacheKey, json_encode($recommendations), self::CACHE_TTL);

        return $recommendations;
    }

    public function generateTreatmentPlan(
        int $userId,
        array $selectedServices,
        string $correlationId = 'default',
    ): array {
        $services = DB::table('beauty_services')
            ->whereIn('id', $selectedServices)
            ->get();

        $treatmentPlan = [
            'plan_id' => (string) Str::uuid(),
            'user_id' => $userId,
            'services' => $services->map(fn ($s) => [
                'id' => $s->id,
                'name' => $s->name,
                'duration_minutes' => $s->duration_minutes,
                'price_kopecks' => $s->price_kopecks,
            ])->toArray(),
            'total_duration_minutes' => $services->sum('duration_minutes'),
            'total_price_kopecks' => $services->sum('price_kopecks'),
            'recommended_order' => $this->optimizeServiceOrder($services),
            'created_at' => now()->toIso8601String(),
        ];

        $this->audit->record('beauty_treatment_plan_generated', 'TreatmentPlan', null, [], [
            'correlation_id' => $correlationId,
            'user_id' => $userId,
            'plan_id' => $treatmentPlan['plan_id'],
            'service_count' => count($selectedServices),
        ], $correlationId);

        return $treatmentPlan;
    }

    public function suggestSalons(
        float $latitude,
        float $longitude,
        array $serviceIds,
        ?string $priceRange = null,
        string $correlationId = 'default',
    ): array {
        $salons = DB::table('beauty_salons')
            ->where('status', 'active')
            ->where('is_verified', true)
            ->where('rating', '>=', 4.0)
            ->get();

        $scoredSalons = [];

        foreach ($salons as $salon) {
            $distance = $this->calculateDistance($latitude, $longitude, $salon->latitude, $salon->longitude);
            $hasServices = $this->salonHasServices($salon->id, $serviceIds);
            
            if (!$hasServices) {
                continue;
            }

            $score = $this->calculateSalonScore($salon, $distance, $priceRange);

            $scoredSalons[] = [
                'salon_id' => $salon->id,
                'name' => $salon->name,
                'address' => $salon->address,
                'rating' => $salon->rating,
                'distance_meters' => $distance,
                'price_range' => $salon->price_range,
                'score' => $score,
            ];
        }

        usort($scoredSalons, fn ($a, $b) => $b['score'] <=> $a['score']);

        return array_slice($scoredSalons, 0, 5);
    }

    private function analyzeBeautyProfile(
        int $userId,
        ?string $skinType,
        ?string $hairType,
        ?string $concern,
        string $correlationId,
    ): array {
        $userBehavior = $this->getUserBehavior($userId);
        
        $services = DB::table('beauty_services')
            ->where('status', 'active')
            ->get();

        $matchedServices = [];

        foreach ($services as $service) {
            $matchScore = $this->calculateServiceMatch($service, $skinType, $hairType, $concern, $userBehavior);
            
            if ($matchScore > 0.5) {
                $matchedServices[] = [
                    'service_id' => $service->id,
                    'name' => $service->name,
                    'category' => $service->category,
                    'match_score' => $matchScore,
                    'reason' => $this->getMatchReason($service, $skinType, $hairType, $concern),
                ];
            }
        }

        usort($matchedServices, fn ($a, $b) => $b['match_score'] <=> $a['match_score']);

        return [
            'user_id' => $userId,
            'profile_analysis' => [
                'skin_type' => $skinType ?? 'detected',
                'hair_type' => $hairType ?? 'detected',
                'primary_concern' => $concern ?? 'general care',
            ],
            'recommended_services' => array_slice($matchedServices, 0, 5),
            'tips' => $this->generateBeautyTips($skinType, $hairType, $concern),
        ];
    }

    private function getUserBehavior(int $userId): array
    {
        $bookings = DB::table('beauty_bookings')
            ->where('user_id', $userId)
            ->where('created_at', '>=', now()->subMonths(6))
            ->get();

        $servicePreferences = [];
        foreach ($bookings as $booking) {
            $serviceId = $booking->service_id;
            $servicePreferences[$serviceId] = ($servicePreferences[$serviceId] ?? 0) + 1;
        }

        arsort($servicePreferences);

        return [
            'total_bookings' => $bookings->count(),
            'preferred_services' => array_keys(array_slice($servicePreferences, 0, 3)),
            'avg_spend' => $bookings->avg('price_kopecks'),
        ];
    }

    private function calculateServiceMatch(
        $service,
        ?string $skinType,
        ?string $hairType,
        ?string $concern,
        array $userBehavior,
    ): float {
        $score = 0;

        if ($skinType !== null && str_contains(strtolower($service->name), strtolower($skinType))) {
            $score += 0.4;
        }

        if ($hairType !== null && str_contains(strtolower($service->name), strtolower($hairType))) {
            $score += 0.3;
        }

        if ($concern !== null && str_contains(strtolower($service->description), strtolower($concern))) {
            $score += 0.2;
        }

        if (in_array($service->id, $userBehavior['preferred_services'] ?? [])) {
            $score += 0.1;
        }

        return min($score, 1.0);
    }

    private function getMatchReason($service, ?string $skinType, ?string $hairType, ?string $concern): string
    {
        $reasons = [];

        if ($skinType !== null && str_contains(strtolower($service->name), strtolower($skinType))) {
            $reasons[] = "matches your skin type ({$skinType})";
        }

        if ($hairType !== null && str_contains(strtolower($service->name), strtolower($hairType))) {
            $reasons[] = "suitable for your hair type ({$hairType})";
        }

        if ($concern !== null && str_contains(strtolower($service->description), strtolower($concern))) {
            $reasons[] = "addresses your concern ({$concern})";
        }

        return empty($reasons) ? 'popular choice' : implode(', ', $reasons);
    }

    private function optimizeServiceOrder($services): array
    {
        $priorities = [
            'facial' => 1,
            'hair' => 2,
            'nails' => 3,
            'massage' => 4,
        ];

        return $services
            ->sortBy(fn ($s) => $priorities[$s->category] ?? 999)
            ->pluck('id')
            ->toArray();
    }

    private function generateBeautyTips(?string $skinType, ?string $hairType, ?string $concern): array
    {
        $tips = [
            'Stay hydrated for glowing skin',
            'Use sunscreen daily',
            'Get regular sleep for better skin health',
        ];

        if ($skinType === 'oily') {
            $tips[] = 'Use oil-free products';
        } elseif ($skinType === 'dry') {
            $tips[] = 'Moisturize regularly';
        }

        if ($hairType === 'colored') {
            $tips[] = 'Use color-safe shampoo';
        }

        return array_slice($tips, 0, 3);
    }

    private function salonHasServices(int $salonId, array $serviceIds): bool
    {
        $salonServices = DB::table('beauty_salon_services')
            ->where('salon_id', $salonId)
            ->pluck('service_id')
            ->toArray();

        return count(array_intersect($salonServices, $serviceIds)) > 0;
    }

    private function calculateSalonScore($salon, float $distance, ?string $priceRange): float
    {
        $distanceScore = max(0, 1 - ($distance / 10000));
        $ratingScore = $salon->rating / 5.0;
        $priceScore = 1.0;

        if ($priceRange !== null) {
            $priceScore = $this->matchPriceRange($salon->price_range, $priceRange);
        }

        return ($distanceScore * 0.4) + ($ratingScore * 0.4) + ($priceScore * 0.2);
    }

    private function matchPriceRange(string $salonRange, string $userRange): float
    {
        return $salonRange === $userRange ? 1.0 : 0.5;
    }

    private function calculateDistance(float $lat1, float $lon1, float $lat2, float $lon2): float
    {
        $earthRadius = 6371000;
        $dLat = $this->deg2rad($lat2 - $lat1);
        $dLon = $this->deg2rad($lon2 - $lon1);

        $a = sin($dLat / 2) * sin($dLat / 2) +
             cos($this->deg2rad($lat1)) * cos($this->deg2rad($lat2)) *
             sin($dLon / 2) * sin($dLon / 2);

        $c = 2 * atan2(sqrt($a), sqrt(1 - $a));

        return $earthRadius * $c;
    }

    private function deg2rad(float $deg): float
    {
        return $deg * (M_PI / 180);
    }
}
