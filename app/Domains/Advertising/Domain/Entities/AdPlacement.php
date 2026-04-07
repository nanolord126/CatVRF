<?php

declare(strict_types=1);

namespace App\Domains\Advertising\Domain\Entities;

use Carbon\Carbon;

/**
 * AdPlacement DDD Entity.
 *
 * Represents an ad placement within a campaign.
 * Immutable value object with public readonly properties.
 *
 * @package App\Domains\Advertising\Domain\Entities
 */
final class AdPlacement
{
    public function __construct(
        public readonly ?int $id,
        public readonly int $campaign_id,
        public readonly string $placement_zone,
        public readonly string $content_type,
        public readonly array $content,
        public readonly Carbon $created_at,
    ) {}

    /**
     * Check if placement is of a specific content type.
     */
    public function isContentType(string $type): bool
    {
        return $this->content_type === $type;
    }

    /**
     * Check if placement is a banner.
     */
    public function isBanner(): bool
    {
        return $this->content_type === 'banner';
    }

    /**
     * Check if placement is a video ad.
     */
    public function isVideo(): bool
    {
        return $this->content_type === 'video';
    }

    /**
     * Check if placement is a text ad.
     */
    public function isText(): bool
    {
        return $this->content_type === 'text';
    }

    /**
     * Get the zone parts (e.g., 'marketplace.sidebar' → ['marketplace', 'sidebar']).
     */
    public function zoneParts(): array
    {
        return explode('.', $this->placement_zone);
    }

    /**
     * String representation for debugging.
     */
    public function __toString(): string
    {
        return static::class . '::' . ($this->id ?? 'new')
            . ':zone=' . $this->placement_zone;
    }

    /**
     * Validate essential fields.
     */
    public function isValid(): bool
    {
        return $this->campaign_id > 0
            && $this->placement_zone !== ''
            && $this->content_type !== ''
            && !empty($this->content);
    }
}
