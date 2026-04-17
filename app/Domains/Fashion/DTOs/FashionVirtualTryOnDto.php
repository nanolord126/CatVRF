<?php declare(strict_types=1);

namespace App\Domains\Fashion\DTOs;

final readonly class FashionVirtualTryOnDto
{
    public function __construct(
        public int $designId,
        public int $userId,
        public int $tenantId,
        public ?int $businessGroupId,
        public array $tryOnResults,
        public float $averageFitScore,
        public string $correlationId,
    ) {}

    public function toArray(): array
    {
        return [
            'design_id' => $this->designId,
            'user_id' => $this->userId,
            'tenant_id' => $this->tenantId,
            'business_group_id' => $this->businessGroupId,
            'try_on_results' => $this->tryOnResults,
            'average_fit_score' => $this->averageFitScore,
            'correlation_id' => $this->correlationId,
        ];
    }
}
