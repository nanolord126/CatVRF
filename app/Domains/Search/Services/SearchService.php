<?php declare(strict_types=1);

namespace App\Domains\Search\Services;

use App\Domains\Search\Models\SearchIndex;
use Illuminate\Database\DatabaseManager;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Psr\Log\LoggerInterface;
use Illuminate\Support\Str;
use App\Services\AuditService;
use App\Services\FraudControlService;

final readonly class SearchService
{
    public function __construct(
        private readonly DatabaseManager $db,
        private readonly LoggerInterface $logger,
        private readonly AuditService $audit,
        private readonly FraudControlService $fraud,
    ) {}

    /**
     * Index entity for search
     */
    public function index(string $type, int $id, string $title, string $content, array $metadata = [], string $correlationId = ''): void
    {
        $correlationId ??= Str::uuid()->toString();

        $this->fraud->check(
            userId: 0,
            operationType: 'search_index',
            amount: 0,
            ipAddress: null,
            deviceFingerprint: null,
            correlationId: $correlationId,
        );

        $this->db->transaction(function () use ($type, $id, $title, $content, $metadata, $correlationId) {
            SearchIndex::updateOrCreate(
                [
                    'searchable_type' => $type,
                    'searchable_id' => $id,
                    'tenant_id' => function_exists('tenant') && tenant() ? tenant()->id : 1,
                ],
                [
                    'title' => $title,
                    'content' => $content,
                    'metadata' => $metadata,
                    'ranking_score' => $this->calculateScore($title, $content),
                ]
            );

            $this->audit->record(
                action: 'search_indexed',
                subjectType: SearchIndex::class,
                subjectId: null,
                newValues: ['type' => $type, 'id' => $id],
                correlationId: $correlationId,
            );
        });
    }

    /**
     * Search indexed content
     */
    public function search(string $term, ?string $type = null, int $limit = 20): array
    {
        $query = SearchIndex::search($term);

        if ($type) {
            $query->byType($type);
        }

        return $query->orderBy('ranking_score', 'desc')
            ->limit($limit)
            ->get()
            ->toArray();
    }

    /**
     * Remove from index
     */
    public function remove(string $type, int $id, string $correlationId = ''): void
    {
        $correlationId ??= Str::uuid()->toString();

        $this->db->transaction(function () use ($type, $id, $correlationId) {
            SearchIndex::where('searchable_type', $type)
                ->where('searchable_id', $id)
                ->delete();

            $this->audit->record(
                action: 'search_removed',
                subjectType: SearchIndex::class,
                subjectId: null,
                newValues: ['type' => $type, 'id' => $id],
                correlationId: $correlationId,
            );
        });
    }

    /**
     * Rebuild index for type
     */
    public function rebuild(string $type, string $correlationId = ''): void
    {
        $correlationId ??= Str::uuid()->toString();

        $this->db->transaction(function () use ($type, $correlationId) {
            $tenantId = function_exists('tenant') && tenant() ? tenant()->id : 1;

            // Delete existing index for this type
            $deleted = SearchIndex::where('tenant_id', $tenantId)
                ->where('searchable_type', $type)
                ->delete();

            // Fetch all entities of type and re-index them
            $entities = $this->db->table($this->getTableName($type))
                ->where('tenant_id', $tenantId)
                ->where('is_active', true)
                ->get();

            $indexData = [];
            foreach ($entities as $entity) {
                $indexData[] = [
                    'tenant_id' => $tenantId,
                    'searchable_type' => $type,
                    'searchable_id' => $entity->id,
                    'title' => $entity->name ?? $entity->title ?? '',
                    'content' => $entity->description ?? '',
                    'metadata' => json_encode([
                        'category_id' => $entity->category_id ?? null,
                        'price' => $entity->price ?? null,
                    ]),
                    'ranking_score' => $this->calculateScore(
                        $entity->name ?? $entity->title ?? '',
                        $entity->description ?? ''
                    ),
                    'created_at' => Carbon::now(),
                    'updated_at' => Carbon::now(),
                ];
            }

            if (!empty($indexData)) {
                SearchIndex::insert($indexData);
            }

            $this->audit->record(
                action: 'search_index_rebuilt',
                subjectType: SearchIndex::class,
                subjectId: null,
                newValues: [
                    'type' => $type,
                    'deleted_count' => $deleted,
                    'indexed_count' => count($indexData),
                ],
                correlationId: $correlationId,
            );

            $this->logger->info('Search index rebuilt', [
                'type' => $type,
                'deleted_count' => $deleted,
                'indexed_count' => count($indexData),
                'correlation_id' => $correlationId,
            ]);
        });
    }

    /**
     * Get table name for searchable type
     */
    private function getTableName(string $type): string
    {
        $typeMap = [
            'product' => 'products',
            'category' => 'categories',
            'service' => 'services',
        ];

        return $typeMap[$type] ?? strtolower($type) . 's';
    }

    /**
     * Calculate ranking score
     */
    private function calculateScore(string $title, string $content): float
    {
        $score = 0;
        $score += str_word_count($title) * 2;
        $score += str_word_count($content);
        return min(100, $score);
    }
}
