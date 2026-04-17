<?php

declare(strict_types=1);

namespace App\Domains\Beauty\Services;

use App\Domains\Beauty\DTOs\MasterMatchingByPhotoDto;
use App\Domains\Beauty\Events\MasterMatchedEvent;
use App\Domains\Beauty\Models\Master;
use App\Models\User;
use App\Services\AuditService;
use App\Services\FraudControlService;
use App\Services\RecommendationService;
use App\Services\Security\IdempotencyService;
use App\Services\ML\UserTasteAnalyzerService;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Redis;
use Illuminate\Support\Str;
use OpenAI\Client;

final readonly class MasterMatchingByPhotoService
{
    private const CACHE_TTL = 3600;
    private const EMBEDDING_DIMENSION = 512;

    public function __construct(
        private Client $openai,
        private FraudControlService $fraud,
        private AuditService $audit,
        private IdempotencyService $idempotency,
        private RecommendationService $recommendation,
        private UserTasteAnalyzerService $tasteAnalyzer,
    ) {}

    public function match(MasterMatchingByPhotoDto $dto): array
    {
        $this->fraud->check(
            userId: $dto->userId,
            operationType: 'beauty_master_match',
            amount: 0,
            ipAddress: request()->ip(),
            deviceFingerprint: request()->header('User-Agent'),
            correlationId: $dto->correlationId,
        );

        $cacheKey = $this->getCacheKey($dto);
        $cached = Redis::get($cacheKey);

        if ($cached !== null) {
            Log::channel('audit')->info('Master matching cache hit', [
                'correlation_id' => $dto->correlationId,
                'user_id' => $dto->userId,
            ]);

            return json_decode($cached, true);
        }

        return DB::transaction(function () use ($dto, $cacheKey) {
            $analysis = $this->analyzePhoto($dto);

            $faceEmbedding = $this->generateFaceEmbedding($dto->photo);

            $skinTone = $this->extractSkinTone($analysis);
            $hairType = $this->extractHairType($analysis);
            $faceShape = $this->extractFaceShape($analysis);

            $user = User::findOrFail($dto->userId);
            $this->tasteAnalyzer->analyzeAndSaveUserProfile($user);
            $userProfile = $this->getUserProfile($dto->userId);

            $matchedMasters = $this->findMatchingMasters(
                $dto,
                $skinTone,
                $hairType,
                $faceShape,
                $userProfile,
            );

            $enrichedMasters = $this->enrichWithMLScores($matchedMasters, $faceEmbedding, $userProfile);

            $result = [
                'success' => true,
                'analysis' => [
                    'skin_tone' => $skinTone,
                    'hair_type' => $hairType,
                    'face_shape' => $faceShape,
                    'confidence' => $analysis['confidence'] ?? 0.85,
                ],
                'matched_masters' => $enrichedMasters,
                'total_matches' => count($enrichedMasters),
                'correlation_id' => $dto->correlationId,
            ];

            Redis::setex($cacheKey, self::CACHE_TTL, json_encode($result));

            Log::channel('audit')->info('Master matching completed', [
                'correlation_id' => $dto->correlationId,
                'user_id' => $dto->userId,
                'matches_count' => count($enrichedMasters),
                'tenant_id' => $dto->tenantId,
            ]);

            event(new MasterMatchedEvent($dto->userId, $enrichedMasters, $dto->correlationId));

            $this->audit->record(
                action: 'beauty_master_matched',
                subjectType: Master::class,
                subjectId: $dto->userId,
                oldValues: [],
                newValues: [
                    'matches_count' => count($enrichedMasters),
                    'analysis' => $result['analysis'],
                ],
                correlationId: $dto->correlationId,
            );

            return $result;
        });
    }

    private function analyzePhoto(MasterMatchingByPhotoDto $dto): array
    {
        $response = $this->openai->chat()->create([
            'model' => 'gpt-4o',
            'messages' => [
                [
                    'role' => 'user',
                    'content' => [
                        [
                            'type' => 'text',
                            'text' => 'Analyze this photo for beauty salon services. Extract: skin tone (fair/medium/olive/dark), hair type (straight/wavy/curly/coily), face shape (oval/round/square/heart/diamond/oblong), hair color, age range, skin concerns. Return JSON with keys: skin_tone, hair_type, face_shape, hair_color, age_range, skin_concerns, confidence.',
                        ],
                        [
                            'type' => 'image_url',
                            'image_url' => [
                                'url' => 'data:image/jpeg;base64,' . base64_encode(file_get_contents($dto->photo->getRealPath())),
                            ],
                        ],
                    ],
                ],
            ],
            'max_tokens' => 500,
            'response_format' => ['type' => 'json_object'],
        ]);

        $content = $response->choices[0]->message->content;
        $decoded = json_decode($content, true);

        return is_array($decoded) ? $decoded : [];
    }

    private function generateFaceEmbedding(UploadedFile $photo): array
    {
        $response = $this->openai->embeddings()->create([
            'model' => 'text-embedding-3-small',
            'input' => base64_encode(file_get_contents($photo->getRealPath())),
            'dimensions' => self::EMBEDDING_DIMENSION,
        ]);

        return $response->embeddings[0]->embedding ?? array_fill(0, self::EMBEDDING_DIMENSION, 0.0);
    }

    private function extractSkinTone(array $analysis): string
    {
        return $analysis['skin_tone'] ?? 'medium';
    }

    private function extractHairType(array $analysis): string
    {
        return $analysis['hair_type'] ?? 'straight';
    }

    private function extractFaceShape(array $analysis): string
    {
        return $analysis['face_shape'] ?? 'oval';
    }

    private function findMatchingMasters(
        MasterMatchingByPhotoDto $dto,
        string $skinTone,
        string $hairType,
        string $faceShape,
        array $userProfile,
    ): array {
        $query = Master::query()
            ->where('tenant_id', $dto->tenantId)
            ->where('is_active', true)
            ->with(['salon', 'services', 'reviews']);

        if ($dto->businessGroupId !== null) {
            $query->where('business_group_id', $dto->businessGroupId);
        }

        if ($dto->serviceType !== null) {
            $query->whereHas('services', function ($q) use ($dto) {
                $q->where('type', $dto->serviceType);
            });
        }

        if ($dto->preferredGender !== null) {
            $query->where('gender', $dto->preferredGender);
        }

        if ($dto->minRating !== null) {
            $query->where('rating', '>=', $dto->minRating);
        }

        $masters = $query->get();

        return $masters->filter(function ($master) use ($dto, $skinTone, $hairType) {
            if ($dto->priceMin !== null && $master->base_price < $dto->priceMin) {
                return false;
            }

            if ($dto->priceMax !== null && $master->base_price > $dto->priceMax) {
                return false;
            }

            $specializations = $master->specializations ?? [];
            $skinCompatibility = $this->checkSkinCompatibility($skinTone, $specializations);
            $hairCompatibility = $this->checkHairCompatibility($hairType, $specializations);

            return $skinCompatibility && $hairCompatibility;
        })->map(function ($master) use ($dto) {
            return [
                'id' => $master->id,
                'uuid' => $master->uuid,
                'name' => $master->full_name,
                'avatar' => $master->avatar_url ?? null,
                'rating' => $master->rating,
                'reviews_count' => $master->reviews_count ?? 0,
                'base_price' => $dto->isB2B ? ($master->b2b_price ?? $master->base_price) : $master->base_price,
                'specializations' => $master->specialization ?? [],
                'salon' => [
                    'id' => $master->salon->id,
                    'name' => $master->salon->name,
                    'address' => $master->salon->address,
                    'lat' => $master->salon->lat,
                    'lon' => $master->salon->lon,
                ],
                'services' => $master->services->map(fn($s) => [
                    'id' => $s->id,
                    'name' => $s->name,
                    'duration' => $s->duration,
                    'price' => $dto->isB2B ? ($s->b2b_price ?? $s->price) : $s->price,
                ])->toArray(),
            ];
        })->toArray();
    }

    private function checkSkinCompatibility(string $skinTone, array $specializations): bool
    {
        $skinSpecializations = [
            'fair' => ['coloring', 'highlights', 'makeup'],
            'medium' => ['coloring', 'highlights', 'makeup', 'facial'],
            'olive' => ['coloring', 'facial', 'skincare'],
            'dark' => ['coloring', 'braids', 'natural_hair', 'skincare'],
        ];

        $compatibleServices = $skinSpecializations[$skinTone] ?? $skinSpecializations['medium'];

        foreach ($compatibleServices as $service) {
            if (in_array($service, $specializations, true)) {
                return true;
            }
        }

        return count($specializations) === 0;
    }

    private function checkHairCompatibility(string $hairType, array $specializations): bool
    {
        $hairSpecializations = [
            'straight' => ['cutting', 'styling', 'coloring'],
            'wavy' => ['cutting', 'styling', 'coloring', 'treatment'],
            'curly' => ['curly_specialist', 'cutting', 'styling', 'treatment'],
            'coily' => ['natural_hair', 'braids', 'coily_specialist', 'treatment'],
        ];

        $compatibleServices = $hairSpecializations[$hairType] ?? $hairSpecializations['straight'];

        foreach ($compatibleServices as $service) {
            if (in_array($service, $specializations, true)) {
                return true;
            }
        }

        return count($specializations) === 0;
    }

    private function enrichWithMLScores(array $masters, array $faceEmbedding, array $userProfile): array
    {
        foreach ($masters as &$master) {
            $mlScore = $this->calculateMasterScore($master, $faceEmbedding, $userProfile);

            $master['ml_score'] = $mlScore;
            $master['match_percentage'] = min(99, (int) round($mlScore * 100));
        }

        usort($masters, fn($a, $b) => $b['ml_score'] <=> $a['ml_score']);

        return array_slice($masters, 0, 10);
    }

    private function calculateMasterScore(array $master, array $faceEmbedding, array $userProfile): float
    {
        $ratingScore = ($master['rating'] ?? 4.0) / 5.0 * 0.4;
        $reviewsScore = min(1.0, ($master['reviews_count'] ?? 0) / 100.0) * 0.3;
        $priceScore = $this->calculatePriceScore($master, $userProfile) * 0.3;

        return $ratingScore + $reviewsScore + $priceScore;
    }

    private function calculatePriceScore(array $master, array $userProfile): float
    {
        $price = $master['base_price'] ?? 0;
        $preferredPrice = $userProfile['preferred_price'] ?? 5000;

        if ($price === 0) {
            return 0.5;
        }

        $ratio = $preferredPrice / $price;
        return min(1.0, max(0.0, $ratio));
    }

    private function getUserProfile(int $userId): array
    {
        return [
            'preferred_price' => 5000,
            'preferred_services' => [],
            'history' => [],
        ];
    }

    private function getCacheKey(MasterMatchingByPhotoDto $dto): string
    {
        $photoHash = md5_file($dto->photo->getRealPath());

        return sprintf(
            'beauty:master_match:%d:%s:%s:%s',
            $dto->userId,
            $photoHash,
            $dto->serviceType ?? 'all',
            $dto->preferredGender ?? 'any',
        );
    }
}
