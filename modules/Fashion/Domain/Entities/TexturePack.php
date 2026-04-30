<?php

declare(strict_types=1);

namespace Modules\Fashion\Domain\Entities;

use Illuminate\Support\Str;
use Modules\Fashion\Domain\ValueObjects\TextureGenerationStatus;
use Modules\Fashion\Domain\ValueObjects\TextureType;

final readonly class TexturePack
{
    public function __construct(
        private string $uuid,
        private int $model3dId,
        private string $name,
        private ?string $description,
        private TextureType $materialType,
        private TextureGenerationStatus $status,
        private ?string $albedoPath,
        private ?string $normalPath,
        private ?string $roughnessPath,
        private ?string $metallicPath,
        private ?string $aoPath,
        private ?string $displacementPath,
        private array $generationMetadata,
        private ?string $correlationId,
        private ?string $errorMessage,
        private \DateTimeImmutable $createdAt,
        private ?\DateTimeImmutable $completedAt,
    ) {}

    public static function create(
        int $model3dId,
        string $name,
        TextureType $materialType,
        ?string $description = null,
    ): self {
        return new self(
            uuid: (string) Str::uuid(),
            model3dId: $model3dId,
            name: $name,
            description: $description,
            materialType: $materialType,
            status: TextureGenerationStatus::PENDING,
            albedoPath: null,
            normalPath: null,
            roughnessPath: null,
            metallicPath: null,
            aoPath: null,
            displacementPath: null,
            generationMetadata: [],
            correlationId: null,
            errorMessage: null,
            createdAt: new \DateTimeImmutable(),
            completedAt: null,
        );
    }

    public function markAsProcessing(string $correlationId): self
    {
        return new self(
            uuid: $this->uuid,
            model3dId: $this->model3dId,
            name: $this->name,
            description: $this->description,
            materialType: $this->materialType,
            status: TextureGenerationStatus::PROCESSING,
            albedoPath: $this->albedoPath,
            normalPath: $this->normalPath,
            roughnessPath: $this->roughnessPath,
            metallicPath: $this->metallicPath,
            aoPath: $this->aoPath,
            displacementPath: $this->displacementPath,
            generationMetadata: $this->generationMetadata,
            correlationId: $correlationId,
            errorMessage: $this->errorMessage,
            createdAt: $this->createdAt,
            completedAt: null,
        );
    }

    public function markAsCompleted(
        string $albedoPath,
        string $normalPath,
        string $roughnessPath,
        string $metallicPath,
        string $aoPath,
        ?string $displacementPath,
        array $metadata,
    ): self {
        return new self(
            uuid: $this->uuid,
            model3dId: $this->model3dId,
            name: $this->name,
            description: $this->description,
            materialType: $this->materialType,
            status: TextureGenerationStatus::COMPLETED,
            albedoPath: $albedoPath,
            normalPath: $normalPath,
            roughnessPath: $roughnessPath,
            metallicPath: $metallicPath,
            aoPath: $aoPath,
            displacementPath: $displacementPath,
            generationMetadata: $metadata,
            correlationId: $this->correlationId,
            errorMessage: null,
            createdAt: $this->createdAt,
            completedAt: new \DateTimeImmutable(),
        );
    }

    public function markAsFailed(string $errorMessage): self
    {
        return new self(
            uuid: $this->uuid,
            model3dId: $this->model3dId,
            name: $this->name,
            description: $this->description,
            materialType: $this->materialType,
            status: TextureGenerationStatus::FAILED,
            albedoPath: $this->albedoPath,
            normalPath: $this->normalPath,
            roughnessPath: $this->roughnessPath,
            metallicPath: $this->metallicPath,
            aoPath: $this->aoPath,
            displacementPath: $this->displacementPath,
            generationMetadata: $this->generationMetadata,
            correlationId: $this->correlationId,
            errorMessage: $errorMessage,
            createdAt: $this->createdAt,
            completedAt: new \DateTimeImmutable(),
        );
    }

    // Getters
    public function getUuid(): string
    {
        return $this->uuid;
    }

    public function getModel3dId(): int
    {
        return $this->model3dId;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function getMaterialType(): TextureType
    {
        return $this->materialType;
    }

    public function getStatus(): TextureGenerationStatus
    {
        return $this->status;
    }

    public function getAlbedoPath(): ?string
    {
        return $this->albedoPath;
    }

    public function getNormalPath(): ?string
    {
        return $this->normalPath;
    }

    public function getRoughnessPath(): ?string
    {
        return $this->roughnessPath;
    }

    public function getMetallicPath(): ?string
    {
        return $this->metallicPath;
    }

    public function getAoPath(): ?string
    {
        return $this->aoPath;
    }

    public function getDisplacementPath(): ?string
    {
        return $this->displacementPath;
    }

    public function getGenerationMetadata(): array
    {
        return $this->generationMetadata;
    }

    public function getCorrelationId(): ?string
    {
        return $this->correlationId;
    }

    public function getErrorMessage(): ?string
    {
        return $this->errorMessage;
    }

    public function getCreatedAt(): \DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function getCompletedAt(): ?\DateTimeImmutable
    {
        return $this->completedAt;
    }

    public function isCompleted(): bool
    {
        return $this->status === TextureGenerationStatus::COMPLETED;
    }

    public function isFailed(): bool
    {
        return $this->status === TextureGenerationStatus::FAILED;
    }

    public function isProcessing(): bool
    {
        return $this->status === TextureGenerationStatus::PROCESSING;
    }

    public function toArray(): array
    {
        return [
            'uuid' => $this->uuid,
            'model3d_id' => $this->model3dId,
            'name' => $this->name,
            'description' => $this->description,
            'material_type' => $this->materialType->value,
            'status' => $this->status->value,
            'albedo_path' => $this->albedoPath,
            'normal_path' => $this->normalPath,
            'roughness_path' => $this->roughnessPath,
            'metallic_path' => $this->metallicPath,
            'ao_path' => $this->aoPath,
            'displacement_path' => $this->displacementPath,
            'generation_metadata' => $this->generationMetadata,
            'correlation_id' => $this->correlationId,
            'error_message' => $this->errorMessage,
            'created_at' => $this->createdAt->format('Y-m-d H:i:s'),
            'completed_at' => $this->completedAt?->format('Y-m-d H:i:s'),
        ];
    }
}
