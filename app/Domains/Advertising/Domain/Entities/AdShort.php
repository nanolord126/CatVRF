<?php

declare(strict_types=1);

namespace App\Domains\Advertising\Domain\Entities;

use Carbon\Carbon;

/**
 * Ad Short Domain Entity
 *
 * Represents a short video advertisement (TikTok/Reels format).
 * 
 * PRODUCTION MANDATORY — CatVRF 2026 Enterprise
 */
final readonly class AdShort
{
    public function __construct(
        public int $id,
        public string $uuid,
        public int $tenant_id,
        public string $title,
        public string $video_url,
        public string $thumbnail_url,
        public int $duration_seconds,
        public string $status,
        public Carbon $start_at,
        public Carbon $end_at,
        public int $budget,
        public int $spent,
        public string $pricing_model,
        public array $targeting_criteria,
        public string $correlation_id,
    ) {}

    public static function create(
        int $tenantId,
        string $title,
        string $videoUrl,
        string $thumbnailUrl,
        int $durationSeconds,
        int $budget,
        string $pricingModel,
        array $targetingCriteria,
        ?string $correlationId = null,
    ): self {
        return new self(
            id: 0,
            uuid: (string) \Illuminate\Support\Str::uuid(),
            tenant_id: $tenantId,
            title: $title,
            video_url: $videoUrl,
            thumbnail_url: $thumbnailUrl,
            duration_seconds: $durationSeconds,
            status: 'draft',
            start_at: Carbon::now(),
            end_at: Carbon::now()->addDays(30),
            budget: $budget,
            spent: 0,
            pricing_model: $pricingModel,
            targeting_criteria: $targetingCriteria,
            correlation_id: $correlationId ?? (string) \Illuminate\Support\Str::uuid(),
        );
    }

    public function isActive(): bool
    {
        return $this->status === 'active'
            && $this->start_at->isPast()
            && $this->end_at->isFuture();
    }

    public function hasBudget(): bool
    {
        return $this->budget > $this->spent;
    }

    public function remainingBudget(): int
    {
        return max(0, $this->budget - $this->spent);
    }

    public function canTransitionTo(string $newStatus): bool
    {
        $validTransitions = [
            'draft' => ['pending_review', 'cancelled'],
            'pending_review' => ['active', 'rejected'],
            'active' => ['paused', 'completed'],
            'paused' => ['active', 'cancelled'],
            'rejected' => [],
            'cancelled' => [],
            'completed' => [],
        ];

        return in_array($newStatus, $validTransitions[$this->status] ?? [], true);
    }
}
