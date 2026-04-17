<?php declare(strict_types=1);

namespace App\Domains\Electronics\DTOs\AI;

use Illuminate\Http\UploadedFile;

final readonly class GadgetVisionAnalysisRequestDto
{
    /**
     * @param array<string> $preferredBrands
     * @param array<string> $useCases
     * @param array<string, mixed> $additionalSpecs
     */
    public function __construct(
        public UploadedFile $image,
        public int $userId,
        public string $correlationId,
        public int $budgetMaxKopecks,
        public string $analysisType,
        public array $preferredBrands = [],
        public array $useCases = [],
        public array $additionalSpecs = [],
        public ?string $idempotencyKey = null,
    ) {
    }

    public static function fromRequest(array $data, UploadedFile $image, int $userId, string $correlationId): self
    {
        return new self(
            image: $image,
            userId: $userId,
            correlationId: $correlationId,
            budgetMaxKopecks: (int) ($data['budget_max_kopecks'] ?? 0),
            analysisType: $data['analysis_type'] ?? 'gadget_recommendation',
            preferredBrands: (array) ($data['preferred_brands'] ?? []),
            useCases: (array) ($data['use_cases'] ?? []),
            additionalSpecs: (array) ($data['additional_specs'] ?? []),
            idempotencyKey: $data['idempotency_key'] ?? null,
        );
    }

    public function toArray(): array
    {
        return [
            'user_id' => $this->userId,
            'correlation_id' => $this->correlationId,
            'budget_max_kopecks' => $this->budgetMaxKopecks,
            'analysis_type' => $this->analysisType,
            'preferred_brands' => $this->preferredBrands,
            'use_cases' => $this->useCases,
            'additional_specs' => $this->additionalSpecs,
            'idempotency_key' => $this->idempotencyKey,
        ];
    }
}
