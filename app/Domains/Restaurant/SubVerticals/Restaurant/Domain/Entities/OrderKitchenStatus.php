<?php

declare(strict_types=1);

namespace Modules\Restaurant\Domain\Entities;

use Modules\Restaurant\Domain\Enums\OrderKitchenStatusEnum;
use Modules\Restaurant\Domain\Enums\OrderPriority;
use Modules\Restaurant\Domain\ValueObjects\PreparationTime;
use Carbon\CarbonImmutable;

final readonly class OrderKitchenStatus
{
    public function __construct(
        public int $id,
        public int $orderId,
        public int $kitchenStationId,
        public OrderKitchenStatusEnum $status,
        public OrderPriority $priority,
        public PreparationTime $estimatedPreparationTime,
        public ?CarbonImmutable $startedAt,
        public ?CarbonImmutable $completedAt,
        public ?string $problemComment,
        public ?string $notes,
        public bool $isFromMarketplace,
        public bool $isVip,
        public CarbonImmutable $createdAt,
        public CarbonImmutable $updatedAt,
    ) {}

    public static function create(
        int $orderId,
        int $kitchenStationId,
        PreparationTime $estimatedPreparationTime,
        OrderPriority $priority = OrderPriority::NORMAL,
        bool $isFromMarketplace = false,
        bool $isVip = false,
    ): self {
        return new self(
            id: 0,
            orderId: $orderId,
            kitchenStationId: $kitchenStationId,
            status: OrderKitchenStatusEnum::PENDING,
            priority: $priority,
            estimatedPreparationTime: $estimatedPreparationTime,
            startedAt: null,
            completedAt: null,
            problemComment: null,
            notes: null,
            isFromMarketplace: $isFromMarketplace,
            isVip: $isVip,
            createdAt: CarbonImmutable::now(),
            updatedAt: CarbonImmutable::now(),
        );
    }

    public function withStatus(OrderKitchenStatusEnum $status): self
    {
        $startedAt = $this->startedAt;
        $completedAt = $this->completedAt;

        if ($status === OrderKitchenStatusEnum::IN_PROGRESS && $this->startedAt === null) {
            $startedAt = CarbonImmutable::now();
        }

        if ($status === OrderKitchenStatusEnum::READY && $this->completedAt === null) {
            $completedAt = CarbonImmutable::now();
        }

        return new self(
            id: $this->id,
            orderId: $this->orderId,
            kitchenStationId: $this->kitchenStationId,
            status: $status,
            priority: $this->priority,
            estimatedPreparationTime: $this->estimatedPreparationTime,
            startedAt: $startedAt,
            completedAt: $completedAt,
            problemComment: $this->problemComment,
            notes: $this->notes,
            isFromMarketplace: $this->isFromMarketplace,
            isVip: $this->isVip,
            createdAt: $this->createdAt,
            updatedAt: CarbonImmutable::now(),
        );
    }

    public function withPriority(OrderPriority $priority): self
    {
        return new self(
            id: $this->id,
            orderId: $this->orderId,
            kitchenStationId: $this->kitchenStationId,
            status: $this->status,
            priority: $priority,
            estimatedPreparationTime: $this->estimatedPreparationTime,
            startedAt: $this->startedAt,
            completedAt: $this->completedAt,
            problemComment: $this->problemComment,
            notes: $this->notes,
            isFromMarketplace: $this->isFromMarketplace,
            isVip: $this->isVip,
            createdAt: $this->createdAt,
            updatedAt: CarbonImmutable::now(),
        );
    }

    public function withProblem(string $comment): self
    {
        return new self(
            id: $this->id,
            orderId: $this->orderId,
            kitchenStationId: $this->kitchenStationId,
            status: OrderKitchenStatusEnum::PROBLEM,
            priority: $this->priority,
            estimatedPreparationTime: $this->estimatedPreparationTime,
            startedAt: $this->startedAt,
            completedAt: $this->completedAt,
            problemComment: $comment,
            notes: $this->notes,
            isFromMarketplace: $this->isFromMarketplace,
            isVip: $this->isVip,
            createdAt: $this->createdAt,
            updatedAt: CarbonImmutable::now(),
        );
    }

    public function getElapsedMinutes(): int
    {
        if ($this->startedAt === null) {
            return 0;
        }

        $now = CarbonImmutable::now();
        return $this->startedAt->diffInMinutes($now);
    }

    public function isOverdue(): bool
    {
        if ($this->startedAt === null) {
            return false;
        }

        return $this->estimatedPreparationTime->isOverdue($this->getElapsedMinutes());
    }

    public function getTimeRemaining(): int
    {
        if ($this->startedAt === null) {
            return $this->estimatedPreparationTime->minutes;
        }

        return $this->estimatedPreparationTime->getRemainingMinutes($this->getElapsedMinutes());
    }

    public function getProgress(): float
    {
        if ($this->startedAt === null) {
            return 0.0;
        }

        return $this->estimatedPreparationTime->getProgressPercentage($this->getElapsedMinutes());
    }
}
