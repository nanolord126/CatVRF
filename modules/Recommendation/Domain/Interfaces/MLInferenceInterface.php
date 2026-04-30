<?php

declare(strict_types=1);

namespace Modules\Recommendation\Domain\Interfaces;

interface MLInferenceInterface
{
    public function getCandidates(int $tenantId, array $userFeatures, int $limit = 200, ?string $vertical = null): array;

    public function rankItems(int $tenantId, array $userFeatures, array $itemFeatures, array $contextFeatures): array;

    public function getEmbeddings(string $entityType, int $entityId, int $tenantId): array;

    public function healthCheck(): array;

    public function getModelVersion(): string;

    public function trainModel(string $modelType, array $config): array;

    public function getTrainingStatus(string $jobId): array;
}
