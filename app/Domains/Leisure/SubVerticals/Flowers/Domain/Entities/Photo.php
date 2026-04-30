<?php

declare(strict_types=1);

namespace Modules\Flowers\Domain\Entities;

use Carbon\CarbonImmutable;

final readonly class Photo
{
    public function __construct(
        public int $id,
        public int $orderId,
        public ?int $orderItemId,
        public string $imagePath,
        public ?string $thumbnailPath,
        public string $type,
        public ?string $caption,
        public bool $isVisibleToClient,
        public int $sortOrder,
        public CarbonImmutable $createdAt,
        public CarbonImmutable $updatedAt,
    ) {}

    public static function create(
        int $orderId,
        string $imagePath,
        string $type = 'after',
        ?int $orderItemId = null,
        ?string $thumbnailPath = null,
        ?string $caption = null,
        bool $isVisibleToClient = true,
    ): self {
        return new self(
            id: 0,
            orderId: $orderId,
            orderItemId: $orderItemId,
            imagePath: $imagePath,
            thumbnailPath: $thumbnailPath,
            type: $type,
            caption: $caption,
            isVisibleToClient: $isVisibleToClient,
            sortOrder: 0,
            createdAt: CarbonImmutable::now(),
            updatedAt: CarbonImmutable::now(),
        );
    }

    public function isBeforePhoto(): bool
    {
        return $this->type === 'before';
    }

    public function isAfterPhoto(): bool
    {
        return $this->type === 'after';
    }

    public function isDeliveryPhoto(): bool
    {
        return $this->type === 'delivery';
    }
}
