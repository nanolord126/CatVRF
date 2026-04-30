<?php

declare(strict_types=1);

namespace Modules\Marketplace\Infrastructure\Repositories;

use Illuminate\Database\ConnectionInterface;
use Modules\Marketplace\Domain\Entities\RankingScore;
use Modules\Marketplace\Domain\Interfaces\RankingRepositoryInterface;
use Psr\Log\LoggerInterface;
use Ramsey\Uuid\UuidInterface;

/**
 * Eloquent репозиторий рейтингов
 */
final class EloquentRankingRepository implements RankingRepositoryInterface
{
    private const TABLE = 'marketplace_ranking_scores';

    public function __construct(
        private readonly ConnectionInterface $db,
        private readonly LoggerInterface $logger,
    ) {}

    public function save(RankingScore $score): void
    {
        $data = $score->toArray();
        $data['factors'] = json_encode($data['factors']);

        $exists = $this->db->table(self::TABLE)
            ->where('listing_uuid', $score->listingUuid->toString())
            ->exists();

        if ($exists) {
            $this->db->table(self::TABLE)
                ->where('listing_uuid', $score->listingUuid->toString())
                ->update($data);

            $this->logger->debug('Ranking score updated', [
                'listing_uuid' => $score->listingUuid->toString(),
                'score' => $score->overallScore,
            ]);
        } else {
            $this->db->table(self::TABLE)->insert($data);

            $this->logger->debug('Ranking score created', [
                'listing_uuid' => $score->listingUuid->toString(),
                'score' => $score->overallScore,
            ]);
        }
    }

    public function findByListingUuid(UuidInterface $listingUuid): ?RankingScore
    {
        $record = $this->db->table(self::TABLE)
            ->where('listing_uuid', $listingUuid->toString())
            ->first();

        if ($record === null) {
            return null;
        }

        return $this->mapToEntity($record);
    }

    public function batchSave(array $scores): void
    {
        $data = [];
        foreach ($scores as $score) {
            $scoreData = $score->toArray();
            $scoreData['factors'] = json_encode($scoreData['factors']);
            $data[] = $scoreData;
        }

        if (!empty($data)) {
            $this->db->table(self::TABLE)->insertOrIgnore($data);

            $this->logger->info('Batch ranking scores saved', ['count' => count($data)]);
        }
    }

    public function findTopScores(int $limit = 100): array
    {
        $records = $this->db->table(self::TABLE)
            ->orderBy('overall_score', 'desc')
            ->limit($limit)
            ->get();

        return array_map(fn($r) => [
            'uuid' => $r->listing_uuid,
            'score' => (float) $r->overall_score,
        ], $records->all());
    }

    public function findExpired(): array
    {
        $records = $this->db->table(self::TABLE)
            ->where('valid_until', '<', new \DateTime())
            ->get();

        return array_map([$this, 'mapToEntity'], $records->all());
    }

    public function delete(UuidInterface $listingUuid): void
    {
        $this->db->table(self::TABLE)
            ->where('listing_uuid', $listingUuid->toString())
            ->delete();

        $this->logger->debug('Ranking score deleted', ['listing_uuid' => $listingUuid->toString()]);
    }

    public function deleteOlderThan(\DateTimeImmutable $date): int
    {
        $deleted = $this->db->table(self::TABLE)
            ->where('calculated_at', '<', $date->format('Y-m-d H:i:s'))
            ->delete();

        $this->logger->info('Old ranking scores deleted', [
            'date' => $date->format('Y-m-d H:i:s'),
            'count' => $deleted,
        ]);

        return $deleted;
    }

    private function mapToEntity(object $record): RankingScore
    {
        $data = (array) $record;
        $data['factors'] = json_decode($data['factors'], true);

        return RankingScore::fromArray($data);
    }
}
