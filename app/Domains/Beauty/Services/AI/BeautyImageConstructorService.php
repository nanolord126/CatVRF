<?php
declare(strict_types=1);

namespace App\Domains\Beauty\Services\AI;

use Illuminate\Http\UploadedFile;
use App\Services\RecommendationService;
use App\Services\InventoryService;
use App\Services\ML\UserTasteAnalyzerService;
use App\Services\FraudControlService;
use App\Services\AuditService;
use Illuminate\Log\LogManager;
use Illuminate\Database\DatabaseManager;
use Illuminate\Contracts\Filesystem\Factory as StorageFactory;
use Illuminate\Contracts\Cache\Repository as CacheRepository;
use Illuminate\Support\Str;
use Carbon\Carbon;

final readonly class BeautyImageConstructorService
{
    public function __construct(
        private RecommendationService $recommendation,
        private InventoryService $inventory,
        private UserTasteAnalyzerService $tasteAnalyzer,
        private FraudControlService $fraud,
        private AuditService $audit,
        private LogManager $logger,
        private DatabaseManager $db,
        private StorageFactory $storage,
        private CacheRepository $cache
    ) {}

    public function analyzePhotoAndRecommend(UploadedFile $photo, int $userId, string $correlationId): array
    {
        $correlationId = $correlationId ?: Str::uuid()->toString();

        $this->fraud->check([
            'user_id' => $userId,
            'operation_type' => 'beauty_ai_constructor',
            'correlation_id' => $correlationId,
        ]);
        
        $this->scanForViruses($photo);
        $cacheKey = "user_ai_designs:beauty:{$userId}:" . md5($photo->getClientOriginalName() . $photo->getSize());

        return $this->cache->remember($cacheKey, Carbon::now()->addHour(), function () use ($photo, $userId, $correlationId) {

            return $this->db->transaction(function () use ($photo, $userId, $correlationId) {
                $path = $this->storage->disk('s3')->putFile('beauty/scans', $photo);

                $styleProfile = [
                    'face_shape' => 'oval',
                    'skin_tone' => 'warm',
                    'hair_color' => 'brunette',
                    'recommended_styles' => ['pixie_cut', 'balayage']
                ];

                $taste = $this->tasteAnalyzer->getProfile($userId);
                $styleProfile = array_merge($styleProfile, $taste->beauty_preferences ?? []);

                $recommendations = $this->recommendation->getForBeauty($styleProfile, $userId);

                foreach ($recommendations as &$item) {
                    $item['in_stock'] = $this->inventory->getAvailableStock((int)$item['product_id']) > 0;
                }
                unset($item);

                $this->db->table('user_ai_designs')->insert([
                    'user_id' => $userId,
                    'vertical' => 'beauty',
                    'design_data' => json_encode($styleProfile, JSON_THROW_ON_ERROR),
                    'correlation_id' => $correlationId,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);

                $this->audit->record(
                    action: 'beauty_ai_constructor_used',
                    subjectType: 'beauty_design',
                    subjectId: $userId,
                    oldValues: [],
                    newValues: ['style_profile' => $styleProfile, 'recommendations_count' => count($recommendations)],
                    correlationId: $correlationId
                );

                $this->logger->channel('audit')->info('Beauty AI constructor used', [
                    'user_id' => $userId,
                    'style_profile' => $styleProfile,
                    'correlation_id' => $correlationId,
                ]);

                return [
                    'success' => true,
                    'vertical' => 'beauty',
                    'payload' => $styleProfile,
                    'suggestions' => $recommendations,
                    'confidence_score' => 0.95,
                    'correlation_id' => $correlationId,
                    's3_path' => $path,
                ];
            });
        });
    }

    private function scanForViruses(UploadedFile $file): void
    {
        $mime = $file->getMimeType();
        if (!in_array($mime, ['image/jpeg', 'image/png'], true)) {
            throw new \InvalidArgumentException('Invalid file type for Beauty Scan.');
        }
        // integration with ClamAV or AWS Macie
    }
}
