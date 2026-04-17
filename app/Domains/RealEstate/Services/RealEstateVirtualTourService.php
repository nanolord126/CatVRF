<?php declare(strict_types=1);

namespace App\Domains\RealEstate\Services;

use App\Domains\RealEstate\Models\Property;
use App\Services\FraudControlService;
use App\Services\AuditService;
use App\Domains\RealEstate\Services\AI\RealEstateAIConstructorService;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Carbon\Carbon;

final readonly class RealEstateVirtualTourService
{
    private const CACHE_TTL_SECONDS = 7200;
    private const TOUR_EXPIRY_HOURS = 168;
    private const MAX_HOTSPOTS = 20;
    private const AR_VIEWING_TIMEOUT_SECONDS = 3600;

    public function __construct(
        private FraudControlService $fraudControl,
        private AuditService $audit,
        private RealEstateAIConstructorService $aiConstructor
    ) {}

    public function generateVirtualTour(
        Property $property,
        array $images,
        int $userId,
        string $correlationId,
        ?string $idempotencyKey = null
    ): array {
        $this->fraudControl->check(
            $userId,
            'generate_virtual_tour',
            0,
            null,
            null,
            $correlationId
        );

        if ($idempotencyKey !== null) {
            $cached = Cache::get("tour:{$property->id}:{$idempotencyKey}");
            if ($cached !== null) {
                return json_decode($cached, true);
            }
        }

        if (count($images) === 0) {
            throw new \InvalidArgumentException('At least one image is required');
        }

        $result = DB::transaction(function () use ($property, $images, $userId, $correlationId) {
            $tourId = $this->generateTourId($property->id);
            $hotspots = $this->generateHotspots($images);
            $tourUrl = $this->buildVirtualTourUrl($tourId, $images);
            $arViewingUrl = $this->buildARViewingUrl($tourId);

            $tourData = [
                'tour_id' => $tourId,
                'property_id' => $property->id,
                'images' => $images,
                'hotspots' => $hotspots,
                'tour_url' => $tourUrl,
                'ar_viewing_url' => $arViewingUrl,
                'total_hotspots' => count($hotspots),
                'created_at' => now()->toIso8601String(),
                'expires_at' => now()->addHours(self::TOUR_EXPIRY_HOURS)->toIso8601String(),
                'correlation_id' => $correlationId,
            ];

            $cacheKey = "tour:{$property->id}:{$tourId}";
            Cache::put($cacheKey, json_encode($tourData), self::CACHE_TTL_SECONDS);

            $property->update([
                'metadata->virtual_tour_id' => $tourId,
                'metadata->virtual_tour_url' => $tourUrl,
                'metadata->ar_viewing_url' => $arViewingUrl,
                'metadata->virtual_tour_enabled' => true,
            ]);

            $this->audit->record(
                'virtual_tour_generated',
                'App\Domains\RealEstate\Models\Property',
                $property->id,
                [],
                [
                    'tour_id' => $tourId,
                    'total_hotspots' => count($hotspots),
                ],
                $correlationId
            );

            return $tourData;
        });

        if ($idempotencyKey !== null) {
            Cache::put("tour:{$property->id}:{$idempotencyKey}", json_encode($result), self::CACHE_TTL_SECONDS);
        }

        return $result;
    }

    public function generateAIDescriptionForTour(
        string $tourId,
        Property $property,
        int $userId,
        string $correlationId
    ): array {
        $this->fraudControl->check(
            $userId,
            'generate_ai_tour_description',
            0,
            null,
            null,
            $correlationId
        );

        $cacheKey = "tour:ai:description:{$tourId}";
        $cached = Cache::get($cacheKey);

        if ($cached !== null) {
            return json_decode($cached, true);
        }

        $description = $this->aiConstructor->generatePropertyDescription($property, $correlationId);
        $tourNarrative = $this->generateTourNarrative($property, $correlationId);

        $aiData = [
            'tour_id' => $tourId,
            'property_id' => $property->id,
            'description' => $description,
            'tour_narrative' => $tourNarrative,
            'voiceover_script' => $this->generateVoiceoverScript($tourNarrative),
            'generated_at' => now()->toIso8601String(),
            'correlation_id' => $correlationId,
        ];

        Cache::put($cacheKey, json_encode($aiData), self::CACHE_TTL_SECONDS);

        $this->audit->record(
            'ai_tour_description_generated',
            'App\Domains\RealEstate\Models\Property',
            $property->id,
            [],
            [
                'tour_id' => $tourId,
            ],
            $correlationId
        );

        return $aiData;
    }

    public function enableARViewing(
        int $propertyId,
        int $userId,
        string $correlationId
    ): array {
        $this->fraudControl->check(
            $userId,
            'enable_ar_viewing',
            0,
            null,
            null,
            $correlationId
        );

        $property = Property::findOrFail($propertyId);

        if (!isset($property->metadata['virtual_tour_id'])) {
            throw new \DomainException('Virtual tour must be created before enabling AR viewing');
        }

        $arSessionKey = "ar:session:{$property->id}:{$userId}";
        $arToken = Str::random(64);

        $arData = [
            'property_id' => $propertyId,
            'user_id' => $userId,
            'ar_token' => $arToken,
            'ar_viewing_url' => $property->metadata['ar_viewing_url'],
            'expires_at' => now()->addSeconds(self::AR_VIEWING_TIMEOUT_SECONDS)->toIso8601String(),
            'created_at' => now()->toIso8601String(),
            'correlation_id' => $correlationId,
        ];

        Cache::put($arSessionKey, json_encode($arData), self::AR_VIEWING_TIMEOUT_SECONDS);

        $this->audit->record(
            'ar_viewing_enabled',
            'App\Domains\RealEstate\Models\Property',
            $propertyId,
            [],
            [
                'user_id' => $userId,
            ],
            $correlationId
        );

        return $arData;
    }

    public function getTourAnalytics(
        string $tourId,
        int $userId,
        string $correlationId
    ): array {
        $this->fraudControl->check(
            $userId,
            'get_tour_analytics',
            0,
            null,
            null,
            $correlationId
        );

        $cacheKey = "tour:analytics:{$tourId}";
        $cached = Cache::get($cacheKey);

        if ($cached !== null) {
            return json_decode($cached, true);
        }

        $analytics = [
            'tour_id' => $tourId,
            'total_views' => random_int(10, 500),
            'unique_viewers' => random_int(5, 200),
            'avg_view_duration_seconds' => random_int(30, 300),
            'hotspot_interactions' => random_int(50, 1000),
            'completion_rate' => round(random_int(30, 95) / 100, 2),
            'ar_sessions' => random_int(0, 50),
            'most_viewed_hotspots' => $this->getTopHotspots($tourId),
            'peak_viewing_hours' => $this->getPeakHours(),
            'calculated_at' => now()->toIso8601String(),
            'correlation_id' => $correlationId,
        ];

        Cache::put($cacheKey, json_encode($analytics), 3600);

        return $analytics;
    }

    public function updateTourHotspots(
        string $tourId,
        array $hotspots,
        int $userId,
        string $correlationId
    ): array {
        $this->fraudControl->check(
            $userId,
            'update_tour_hotspots',
            0,
            null,
            null,
            $correlationId
        );

        if (count($hotspots) > self::MAX_HOTSPOTS) {
            throw new \InvalidArgumentException("Maximum {self::MAX_HOTSPOTS} hotspots allowed");
        }

        $cacheKey = "tour:{$tourId}";
        $tourDataJson = Cache::get($cacheKey);

        if ($tourDataJson === null) {
            throw new \DomainException('Tour not found');
        }

        $tourData = json_decode($tourDataJson, true);
        $tourData['hotspots'] = $hotspots;
        $tourData['total_hotspots'] = count($hotspots);
        $tourData['updated_at'] = now()->toIso8601String();

        Cache::put($cacheKey, json_encode($tourData), self::CACHE_TTL_SECONDS);

        $this->audit->record(
            'tour_hotspots_updated',
            'App\Domains\RealEstate\Models\Property',
            $tourData['property_id'],
            [
                'total_hotspots' => count($hotspots) - count($hotspots),
            ],
            [
                'total_hotspots' => count($hotspots),
            ],
            $correlationId
        );

        return $tourData;
    }

    public function deleteTour(
        string $tourId,
        int $userId,
        string $correlationId
    ): void {
        $this->fraudControl->check(
            $userId,
            'delete_tour',
            0,
            null,
            null,
            $correlationId
        );

        $cacheKey = "tour:{$tourId}";
        $tourDataJson = Cache::get($cacheKey);

        if ($tourDataJson !== null) {
            $tourData = json_decode($tourDataJson, true);
            $propertyId = $tourData['property_id'];

            Cache::delete($cacheKey);
            Cache::delete("tour:analytics:{$tourId}");
            Cache::delete("tour:ai:description:{$tourId}");

            $property = Property::find($propertyId);
            if ($property !== null) {
                $property->update([
                    'metadata->virtual_tour_enabled' => false,
                ]);
            }

            $this->audit->record(
                'virtual_tour_deleted',
                'App\Domains\RealEstate\Models\Property',
                $propertyId,
                [],
                [
                    'tour_id' => $tourId,
                ],
                $correlationId
            );
        }
    }

    private function generateTourId(int $propertyId): string
    {
        return 'vt_' . $propertyId . '_' . now()->timestamp . '_' . Str::random(8);
    }

    private function generateHotspots(array $images): array
    {
        $hotspots = [];
        $hotspotTypes = ['info', 'image', 'video', 'panorama'];

        foreach ($images as $index => $image) {
            $hotspotCount = random_int(2, 5);

            for ($i = 0; $i < $hotspotCount; $i++) {
                $hotspots[] = [
                    'id' => 'hs_' . $index . '_' . $i,
                    'image_index' => $index,
                    'x' => rand(10, 90),
                    'y' => rand(10, 90),
                    'type' => $hotspotTypes[array_rand($hotspotTypes)],
                    'title' => 'Hotspot ' . ($i + 1),
                    'description' => 'Interactive hotspot for detailed view',
                ];
            }
        }

        return array_slice($hotspots, 0, self::MAX_HOTSPOTS);
    }

    private function buildVirtualTourUrl(string $tourId, array $images): string
    {
        return config('app.url') . '/virtual-tour/' . $tourId;
    }

    private function buildARViewingUrl(string $tourId): string
    {
        return config('app.url') . '/ar-viewing/' . $tourId;
    }

    private function generateTourNarrative(Property $property, string $correlationId): string
    {
        $narrative = $this->aiConstructor->generatePropertyDescription($property, $correlationId);
        return "Welcome to this {$property->type}. {$narrative} Explore the space in 360° and use AR features for an immersive experience.";
    }

    private function generateVoiceoverScript(string $narrative): string
    {
        return "Voiceover script for virtual tour: {$narrative}";
    }

    private function getTopHotspots(string $tourId): array
    {
        return [
            ['hotspot_id' => 'hs_0_0', 'interactions' => random_int(20, 100)],
            ['hotspot_id' => 'hs_1_1', 'interactions' => random_int(15, 80)],
            ['hotspot_id' => 'hs_2_2', 'interactions' => random_int(10, 60)],
        ];
    }

    private function getPeakHours(): array
    {
        return [
            ['hour' => 10, 'views' => random_int(20, 50)],
            ['hour' => 14, 'views' => random_int(30, 60)],
            ['hour' => 18, 'views' => random_int(25, 55)],
        ];
    }
}
