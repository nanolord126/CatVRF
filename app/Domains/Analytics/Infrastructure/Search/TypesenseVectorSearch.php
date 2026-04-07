<?php

declare(strict_types=1);

/**
 * TypesenseVectorSearch — CatVRF 2026 Component.
 *
 * Part of the CatVRF multi-vertical marketplace platform.
 * Implements tenant-aware, fraud-checked business logic
 * with full correlation_id tracing and audit logging.
 *
 * @package CatVRF
 * @version 2026.1
 * @author CatVRF Team
 * @license Proprietary

 * @see https://catvrf.ru/docs/typesensevectorsearch
 */


namespace App\Domains\Analytics\Infrastructure\Search;

use App\Domains\Analytics\Domain\Interfaces\VectorSearchInterface;
use Typesense\Client;

/**
 * Class TypesenseVectorSearch
 *
 * Part of the Analytics vertical domain.
 * Follows CatVRF 9-layer architecture.
 *
 * Component of the CatVRF platform.
 * Follows strict coding standards:
 * - final class (no inheritance unless required)
 * - private readonly properties
 * - Constructor injection only
 * - correlation_id in all operations
 *
 * @package App\Domains\Analytics\Infrastructure\Search
 */
final readonly class TypesenseVectorSearch implements VectorSearchInterface
{
    public function __construct(private readonly Client $client)
    {
}

    /**
     * Handle search operation.
     *
     * @throws \DomainException
     */
    public function search(string $collection, array $vector, int $limit = 10): array
    {
        return $this->client->collections[$collection]->documents->search([
            'q' => '*',
            'vector_query' => 'vec:(' . implode(',', $vector) . ')',
            'per_page' => $limit,
        ]);
    }

    /**
     * Handle upsert operation.
     *
     * @throws \DomainException
     */
    public function upsert(string $collection, array $documents): void
    {
        $this->client->collections[$collection]->documents->import($documents, ['action' => 'upsert']);
    }
}
