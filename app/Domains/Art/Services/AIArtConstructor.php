<?php
declare(strict_types=1);

namespace App\Domains\Art\Services;



use Carbon\Carbon;
use Illuminate\Contracts\Auth\Guard;
use Psr\Log\LoggerInterface;
use Illuminate\Config\Repository as ConfigRepository;

use App\Services\FraudControlService;
use App\Services\RecommendationService;
use Illuminate\Contracts\Cache\Repository as CacheRepository;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Cache\RateLimiter;
use Illuminate\Support\Str;

final readonly class AIArtConstructor
{
    public function __construct(private readonly FraudControlService $fraud,
        private readonly CacheRepository $cache,
        private readonly RecommendationService $recommendation,
        private readonly \Illuminate\Database\DatabaseManager $db,
        private readonly ConfigRepository $config, private readonly Request $request, private readonly LoggerInterface $logger, private readonly Guard $guard) {
    }

    public function analyzePhotoAndRecommend(UploadedFile $photo, array $context): array
    {
        $correlationId = $context['correlation_id'] ?? (string) Str::uuid();
        $audience = $this->determineAudience($context);
        $tenantId = $context['tenant_id'] ?? $this->resolveTenantId();
        $userId = (int) ($context['user_id'] ?? 0);
        $estimatedAmount = (int) ($context['estimated_budget_cents'] ?? 0);

        $this->fraud->check(userId: $this->guard->id() ?? 0, operationType: 'ai_art_constructor', amount: 0, correlationId: $correlationId ?? '');
        $this->enforceRateLimit($tenantId, $audience, $correlationId);

        $cacheKey = "ai:art:{$tenantId}:{$audience}:" . sha1_file($photo->getRealPath());
        $payload = $this->cache->remember($cacheKey, 3600, function () use ($photo, $context, $correlationId, $audience, $tenantId, $userId): array {
            $fingerprint = substr(hash_file('sha256', $photo->getRealPath()), 0, 16);
            $palette = $this->buildPaletteFromContext($context);
            $recommendations = $this->fetchRecommendations($userId, $tenantId, $audience, $correlationId);

            $suggestions = [
                [
                    'style' => 'surrealism',
                    'confidence' => 0.83,
                    'reference' => $fingerprint,
                    'palette' => $palette,
                ],
                [
                    'style' => 'modern-minimal',
                    'confidence' => 0.77,
                    'reference' => $fingerprint,
                    'palette' => array_reverse($palette),
                ],
            ];

            $this->logger->info('AI Art Constructor generated suggestions', [
                'correlation_id' => $correlationId,
                'tenant_id' => $tenantId,
                'audience' => $audience,
                'reference' => $fingerprint,
            ]);

            $this->persistPayload([
                'fingerprint' => $fingerprint,
                'correlation_id' => $correlationId,
                'tenant_id' => $tenantId,
                'audience' => $audience,
                'recommendations' => $recommendations,
            ]);

            return [
                'success' => true,
                'correlation_id' => $correlationId,
                'audience' => $audience,
                'reference' => $fingerprint,
                'palette' => $palette,
                'recommendations' => $recommendations,
                'suggestions' => $suggestions,
            ];
        });

        return $payload;
    }

    private function determineAudience(array $context): string
    {
        if (!empty($context['inn']) || !empty($context['business_card_id'])) {
            return 'b2b';
        }

        return 'b2c';
    }

    private function buildPaletteFromContext(array $context): array
    {
        $baseTone = $context['base_tone'] ?? 'neutral';

        return [
            $baseTone,
            $context['accent'] ?? 'amber',
            $context['shadow'] ?? 'charcoal',
            $context['highlight'] ?? 'linen',
        ];
    }

    private function enforceRateLimit(int $tenantId, string $audience, string $correlationId): void
    {
        $key = "ai-art:{$audience}:{$tenantId}";
        $allowed = $this->rateLimiter->attempt($key, 5, static function (): bool { return true; }, 120);

        if (!$allowed) {
            $this->logger->warning('AI Art constructor rate limit exceeded', [
                'correlation_id' => $correlationId,
                'tenant_id' => $tenantId,
            ]);

            throw new \RuntimeException('AI constructor rate limit exceeded');
        }
    }

    private function resolveTenantId(): int
    {
        if (function_exists('tenant') && tenant()) {
            return (int) tenant()->id;
        }

        if ($this->request->user() && isset($this->request->user()->tenant_id)) {
            return (int) $this->request->user()->tenant_id;
        }

        return (int) $this->config->get('app.tenant_id', 0);
    }

    private function fetchRecommendations(int $userId, int $tenantId, string $audience, string $correlationId): array
    {
        try {
            $collection = $this->recommendation->getForUser(
                userId: $userId,
                vertical: 'art',
                context: [
                    'tenant_id' => $tenantId,
                    'audience' => $audience,
                ],
                correlationId: $correlationId,
            );

            return $collection->take(5)->toArray();
        } catch (\Throwable $e) {
            $this->logger->warning('AI Art constructor recommendation fallback', [
                'correlation_id' => $correlationId,
                'tenant_id' => $tenantId,
                'error' => $e->getMessage(),
            ]);

            return [];
        }
    }

    private function persistPayload(array $payload): void
    {
        if (!$this->schema->hasTable('ai_art_constructor_logs')) {
            return;
        }

        $db = $this->db;
        $db->transaction(static function () use ($db, $payload): void {
            $db->table('ai_art_constructor_logs')->insert([
                'fingerprint' => $payload['fingerprint'],
                'tenant_id' => $payload['tenant_id'],
                'audience' => $payload['audience'],
                'recommendations' => json_encode($payload['recommendations'], JSON_THROW_ON_ERROR),
                'correlation_id' => $payload['correlation_id'],
                'created_at' => Carbon::now(),
            ]);
        });
    }
}
