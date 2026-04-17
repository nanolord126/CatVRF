<?php
declare(strict_types=1);

namespace App\Domains\Art\Events;


use App\Domains\Art\Models\Review;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
final class ReviewRecorded
{

    public function __construct(
        public readonly Review $review,
        public readonly string $correlationId,
        public array $context = [],
    ) {}

    public static function dispatch(Review $review, string $correlationId, array $context = []): void
    {
        event(new self($review, $correlationId, $context));
    }

    public function decisionPayload(): array
    {
        return [
            'review_id' => $this->review->id,
            'project_id' => $this->review->project_id,
            'artist_id' => $this->review->artist_id,
            'tenant_id' => $this->review->tenant_id,
            'business_group_id' => $this->review->business_group_id,
            'user_id' => $this->review->user_id,
            'rating' => $this->review->rating,
            'correlation_id' => $this->correlationId,
        ];
    }

    public function auditContext(): array
    {
        return array_merge($this->decisionPayload(), [
            'comment' => $this->review->comment,
            'tags' => $this->review->tags,
            'meta' => $this->review->meta,
            'context' => $this->context,
        ]);
    }

    public function isPositive(): bool
    {
        return $this->review->rating >= 4;
    }

    public function describe(): string
    {
        return sprintf(
            'Review %s for project %s with rating %s (tenant %s)',
            $this->review->id,
            $this->review->project_id,
            $this->review->rating,
            $this->review->tenant_id,
        );
    }
}
