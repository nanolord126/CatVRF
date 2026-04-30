<?php

declare(strict_types=1);

namespace Modules\Fashion\Domain\DTOs;

final readonly class TextureResultDTO
{
    private function __construct(
        public string $albedoPath,
        public string $normalPath,
        public string $roughnessPath,
        public string $metallicPath,
        public string $aoPath,
        public ?string $displacementPath,
        public array $metadata,
        public bool $success,
        public ?string $errorMessage,
        public int $generationTimeMs,
        public array $qualityMetrics,
    ) {}

    public static function success(
        string $albedoPath,
        string $normalPath,
        string $roughnessPath,
        string $metallicPath,
        string $aoPath,
        ?string $displacementPath,
        array $metadata,
        int $generationTimeMs,
        array $qualityMetrics = [],
    ): self {
        return new self(
            albedoPath: $albedoPath,
            normalPath: $normalPath,
            roughnessPath: $roughnessPath,
            metallicPath: $metallicPath,
            aoPath: $aoPath,
            displacementPath: $displacementPath,
            metadata: $metadata,
            success: true,
            errorMessage: null,
            generationTimeMs: $generationTimeMs,
            qualityMetrics: $qualityMetrics,
        );
    }

    public static function failure(string $errorMessage, array $metadata = []): self
    {
        return new self(
            albedoPath: '',
            normalPath: '',
            roughnessPath: '',
            metallicPath: '',
            aoPath: '',
            displacementPath: null,
            metadata: $metadata,
            success: false,
            errorMessage: $errorMessage,
            generationTimeMs: 0,
            qualityMetrics: [],
        );
    }

    public function getTexturePaths(): array
    {
        return [
            'albedo' => $this->albedoPath,
            'normal' => $this->normalPath,
            'roughness' => $this->roughnessPath,
            'metallic' => $this->metallicPath,
            'ao' => $this->aoPath,
            'displacement' => $this->displacementPath,
        ];
    }

    public function hasDisplacementMap(): bool
    {
        return $this->displacementPath !== null && $this->displacementPath !== '';
    }

    public function getQualityScore(): float
    {
        if (empty($this->qualityMetrics)) {
            return 0.0;
        }

        $scores = array_filter($this->qualityMetrics, fn($value) => is_numeric($value));
        if (empty($scores)) {
            return 0.0;
        }

        return array_sum($scores) / count($scores);
    }

    public function isHighQuality(): bool
    {
        return $this->success && $this->getQualityScore() >= 0.8;
    }

    public function isAcceptableQuality(): bool
    {
        return $this->success && $this->getQualityScore() >= 0.6;
    }

    public function getGenerationTimeSeconds(): float
    {
        return $this->generationTimeMs / 1000;
    }

    public function toArray(): array
    {
        return [
            'albedo_path' => $this->albedoPath,
            'normal_path' => $this->normalPath,
            'roughness_path' => $this->roughnessPath,
            'metallic_path' => $this->metallicPath,
            'ao_path' => $this->aoPath,
            'displacement_path' => $this->displacementPath,
            'metadata' => $this->metadata,
            'success' => $this->success,
            'error_message' => $this->errorMessage,
            'generation_time_ms' => $this->generationTimeMs,
            'generation_time_seconds' => $this->getGenerationTimeSeconds(),
            'quality_metrics' => $this->qualityMetrics,
            'quality_score' => $this->getQualityScore(),
            'is_high_quality' => $this->isHighQuality(),
            'is_acceptable_quality' => $this->isAcceptableQuality(),
            'has_displacement_map' => $this->hasDisplacementMap(),
        ];
    }

    public static function fromArray(array $data): self
    {
        if ($data['success'] ?? false) {
            return self::success(
                albedoPath: $data['albedo_path'],
                normalPath: $data['normal_path'],
                roughnessPath: $data['roughness_path'],
                metallicPath: $data['metallic_path'],
                aoPath: $data['ao_path'],
                displacementPath: $data['displacement_path'] ?? null,
                metadata: $data['metadata'] ?? [],
                generationTimeMs: $data['generation_time_ms'] ?? 0,
                qualityMetrics: $data['quality_metrics'] ?? [],
            );
        }

        return self::failure(
            errorMessage: $data['error_message'] ?? 'Unknown error',
            metadata: $data['metadata'] ?? [],
        );
    }
}
