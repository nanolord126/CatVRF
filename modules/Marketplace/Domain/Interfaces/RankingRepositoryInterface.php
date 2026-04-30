<?php

declare(strict_types=1);

namespace Modules\Marketplace\Domain\Interfaces;

use Modules\Marketplace\Domain\Entities\RankingScore;
use Ramsey\Uuid\UuidInterface;

/**
 * Интерфейс репозитория рейтингов
 */
interface RankingRepositoryInterface
{
    /**
     * Сохранить рейтинг
     */
    public function save(RankingScore $score): void;

    /**
     * Найти рейтинг позиции
     */
    public function findByListingUuid(UuidInterface $listingUuid): ?RankingScore;

    /**
     * Массовое сохранение рейтингов
     *
     * @param RankingScore[] $scores
     */
    public function batchSave(array $scores): void;

    /**
     * Получить топ позиций по рейтингу
     *
     * @return array{uuid: string, score: float}[]
     */
    public function findTopScores(int $limit = 100): array;

    /**
     * Получить просроченные рейтинги
     *
     * @return RankingScore[]
     */
    public function findExpired(): array;

    /**
     * Удалить рейтинг
     */
    public function delete(UuidInterface $listingUuid): void;

    /**
     * Очистить старые рейтинги
     */
    public function deleteOlderThan(\DateTimeImmutable $date): int;
}
