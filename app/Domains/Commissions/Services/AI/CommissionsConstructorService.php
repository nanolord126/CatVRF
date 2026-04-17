<?php

declare(strict_types=1);

namespace App\Domains\Commissions\Services\AI;

use App\Services\ML\UserBehaviorAnalyzerService;
use App\Services\ML\NewUserColdStartService;
use App\Services\ML\ReturningUserDeepProfileService;
use App\Services\RecommendationService;
use App\Services\InventoryService;
use App\Services\FraudControlService;
use Illuminate\Http\UploadedFile;
use Illuminate\Database\DatabaseManager;
use Illuminate\Contracts\Cache\Repository as Cache;
use Psr\Log\LoggerInterface;

final readonly class CommissionsConstructorService
{
    private const CACHE_TTL = 3600;

    public function __construct(
        private UserBehaviorAnalyzerService $behaviorAnalyzer,
        private NewUserColdStartService $coldStart,
        private ReturningUserDeepProfileService $deepProfile,
        private RecommendationService $recommendation,
        private InventoryService $inventory,
        private FraudControlService $fraud,
        private readonly DatabaseManager $db,
        private readonly Cache $cache,
        private readonly LoggerInterface $logger
    ) {}

    public function analyzeAndRecommend(
        UploadedFile $file,
        int $userId,
        string $correlationId,
    ): array {
        $this->fraud->check([
            'user_id' => $userId,
            'operation_type' => 'commissions_ai_constructor',
            'correlation_id' => $correlationId,
        ]);

        $cacheKey = "ai_constructor:{$userId}:{$verticalSlug}:" . md5($file->getRealPath());

        if ($this->cache->has($cacheKey)) {
            return $this->cache->get($cacheKey);
        }

        $isNewUser = $this->behaviorAnalyzer->classifyUser($userId) === 'new';

        $result = $this->db->transaction(function () use ($file, $userId, $correlationId, $isNewUser) {
            $analysis = $this->performAnalysis($file);

            if ($isNewUser) {
                $recommendations = $this->coldStart->generate($analysis, 'commissions');
            } else {
                $recommendations = $this->deepProfile->generate($analysis, 'commissions');
            }

            $availableRecommendations = $this->inventory->checkAvailability($recommendations);
            $this->saveDesign($userId, $analysis, $availableRecommendations, $correlationId);

            $this->logger->info('Commissions AI constructor used', [
                'user_id' => $userId,
                'correlation_id' => $correlationId,
                'is_new_user' => $isNewUser,
                'recommendations_count' => count($availableRecommendations),
            ]);

            return [
                'success' => true,
                'analysis' => $analysis,
                'recommendations' => $availableRecommendations,
                'is_new_user' => $isNewUser,
            ];
        });

        $this->cache->put($cacheKey, $result, self::CACHE_TTL);

        return $result;
    }

    private function performAnalysis(UploadedFile $file): array
    {
        return [
            'detected_features' => [],
            'confidence' => 0.95,
        ];
    }

    private function saveDesign(int $userId, array $analysis, array $recommendations, string $correlationId): void
    {
        DB::table('user_ai_designs')->insert([
            'user_id' => $userId,
            'vertical' => 'commissions',
            'design_data' => json_encode([
                'analysis' => $analysis,
                'recommendations' => $recommendations,
            ], JSON_UNESCAPED_UNICODE),
            'correlation_id' => $correlationId,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }
}
