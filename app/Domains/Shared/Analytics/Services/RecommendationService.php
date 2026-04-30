<?php

declare(strict_types=1);

namespace Modules\Analytics\Services;

use App\Traits\WithAuditLogging;
use App\Services\Security\AuditService;
use Illuminate\Contracts\Cache\Repository;
use Illuminate\Log\LogManager;
use Modules\Common\Services\AbstractTechnicalVerticalService;
use OpenAI\Laravel\Contracts\OpenAIContract;

final class RecommendationService extends AbstractTechnicalVerticalService
{
    use WithAuditLogging;

    public function __construct(
        private readonly Repository $cache,
        private readonly LogManager $log,
        private readonly OpenAIContract $openai,
        private readonly AuditService $auditService,
    ) {
        parent::__construct();
    }

    public function isEnabled(): bool
    {
        return $this->tenant->settings['recommendations_enabled'] ?? true;
    }

    /**
     * Генерация эмбеддинга для сущности (Товар/Услуга/Профиль)
     */
    public function getEmbedding(string $text): array
    {
        $embedding = $this->cache->remember('emb_'.md5($text), 86400, function () use ($text) {
            $response = $this->openai->embeddings()->create([
                'model' => 'text-embedding-3-small',
                'input' => $text,
            ]);

            return $response->embeddings[0]->embedding;
        });

        $this->logAction('embedding_generated', 'Recommendation', null, [
            'text_length' => strlen($text),
            'embedding_dimension' => count($embedding),
        ]);

        return $embedding;
    }

    /**
     * Поиск похожих объектов через косинусное сходство (Vector Search)
     * В реальном 2026 проекте это делается через Typesense/Elasticsearch,
     * но для бизнес-логики мы предоставляем интерфейс.
     */
    public function findSimilar(array $entityEmbedding, string $targetType, int $limit = 5): array
    {
        $this->log->info('AI Vector Search triggered', [
            'type' => $targetType,
            'correlation_id' => request()->header('X-Correlation-ID'),
        ]);

        $this->logAction('vector_search_performed', 'Recommendation', null, [
            'target_type' => $targetType,
            'limit' => $limit,
            'embedding_dimension' => count($entityEmbedding),
        ]);

        // Поиск в БД по векторному полю (mock для канона)
        return [];
    }
}
