<?php

declare(strict_types=1);

namespace Modules\Supermarket\Domain\Entities;

use Modules\Supermarket\Domain\Enums\ReturnStatus;
use Modules\Supermarket\Domain\Enums\ReturnReason;
use Modules\Supermarket\Domain\ValueObjects\Money;
use Carbon\Carbon;

final readonly class ReturnEntity
{
    private function __construct(
        public int $id,
        public int $orderId,
        public int $buyerId,
        public int $sellerId,
        public ReturnStatus $status,
        public ReturnReason $reasonType,
        public ?string $reasonComment,
        public Money $totalAmount,
        public Money $refundAmount,
        public bool $isColdChain,
        public string $returnMethod,
        public array $images,
        public ?Carbon $approvedAt,
        public ?Carbon $completedAt,
        public ?Carbon $rejectedAt,
        public ?string $rejectReason,
        public bool $autoApproved,
        public Carbon $createdAt,
        public Carbon $updatedAt,
    ) {}

    public static function create(
        int $orderId,
        int $buyerId,
        int $sellerId,
        ReturnReason $reasonType,
        ?string $reasonComment,
        Money $totalAmount,
        bool $isColdChain,
        string $returnMethod,
        array $images = [],
    ): self {
        return new self(
            id: 0,
            orderId: $orderId,
            buyerId: $buyerId,
            sellerId: $sellerId,
            status: ReturnStatus::PENDING,
            reasonType: $reasonType,
            reasonComment: $reasonComment,
            totalAmount: $totalAmount,
            refundAmount: $totalAmount,
            isColdChain: $isColdChain,
            returnMethod: $returnMethod,
            images: $images,
            approvedAt: null,
            completedAt: null,
            rejectedAt: null,
            rejectReason: null,
            autoApproved: false,
            createdAt: now(),
            updatedAt: now(),
        );
    }

    public static function fromArray(array $data): self
    {
        return new self(
            id: $data['id'],
            orderId: $data['order_id'],
            buyerId: $data['buyer_id'],
            sellerId: $data['seller_id'],
            status: ReturnStatus::from($data['status']),
            reasonType: ReturnReason::from($data['reason_type']),
            reasonComment: $data['reason_comment'] ?? null,
            totalAmount: Money::fromFloat($data['total_amount']),
            refundAmount: Money::fromFloat($data['refund_amount']),
            isColdChain: (bool) $data['is_cold_chain'],
            returnMethod: $data['return_method'],
            images: $data['images'] ?? [],
            approvedAt: $data['approved_at'] ? Carbon::parse($data['approved_at']) : null,
            completedAt: $data['completed_at'] ? Carbon::parse($data['completed_at']) : null,
            rejectedAt: $data['rejected_at'] ? Carbon::parse($data['rejected_at']) : null,
            rejectReason: $data['reject_reason'] ?? null,
            autoApproved: (bool) ($data['auto_approved'] ?? false),
            createdAt: Carbon::parse($data['created_at']),
            updatedAt: Carbon::parse($data['updated_at']),
        );
    }

    public function approve(bool $auto = false): self
    {
        return new self(
            id: $this->id,
            orderId: $this->orderId,
            buyerId: $this->buyerId,
            sellerId: $this->sellerId,
            status: ReturnStatus::APPROVED,
            reasonType: $this->reasonType,
            reasonComment: $this->reasonComment,
            totalAmount: $this->totalAmount,
            refundAmount: $this->refundAmount,
            isColdChain: $this->isColdChain,
            returnMethod: $this->returnMethod,
            images: $this->images,
            approvedAt: now(),
            completedAt: null,
            rejectedAt: null,
            rejectReason: null,
            autoApproved: $auto,
            createdAt: $this->createdAt,
            updatedAt: now(),
        );
    }

    public function reject(string $reason): self
    {
        return new self(
            id: $this->id,
            orderId: $this->orderId,
            buyerId: $this->buyerId,
            sellerId: $this->sellerId,
            status: ReturnStatus::REJECTED,
            reasonType: $this->reasonType,
            reasonComment: $this->reasonComment,
            totalAmount: $this->totalAmount,
            refundAmount: Money::zero(),
            isColdChain: $this->isColdChain,
            returnMethod: $this->returnMethod,
            images: $this->images,
            approvedAt: null,
            completedAt: null,
            rejectedAt: now(),
            rejectReason: $reason,
            autoApproved: false,
            createdAt: $this->createdAt,
            updatedAt: now(),
        );
    }

    public function complete(): self
    {
        if ($this->status !== ReturnStatus::APPROVED) {
            throw new \InvalidArgumentException('Can only complete approved returns');
        }

        return new self(
            id: $this->id,
            orderId: $this->orderId,
            buyerId: $this->buyerId,
            sellerId: $this->sellerId,
            status: ReturnStatus::COMPLETED,
            reasonType: $this->reasonType,
            reasonComment: $this->reasonComment,
            totalAmount: $this->totalAmount,
            refundAmount: $this->refundAmount,
            isColdChain: $this->isColdChain,
            returnMethod: $this->returnMethod,
            images: $this->images,
            approvedAt: $this->approvedAt,
            completedAt: now(),
            rejectedAt: null,
            rejectReason: null,
            autoApproved: $this->autoApproved,
            createdAt: $this->createdAt,
            updatedAt: now(),
        );
    }

    public function updateRefundAmount(Money $amount): self
    {
        return new self(
            id: $this->id,
            orderId: $this->orderId,
            buyerId: $this->buyerId,
            sellerId: $this->sellerId,
            status: $this->status,
            reasonType: $this->reasonType,
            reasonComment: $this->reasonComment,
            totalAmount: $this->totalAmount,
            refundAmount: $amount,
            isColdChain: $this->isColdChain,
            returnMethod: $this->returnMethod,
            images: $this->images,
            approvedAt: $this->approvedAt,
            completedAt: $this->completedAt,
            rejectedAt: $this->rejectedAt,
            rejectReason: $this->rejectReason,
            autoApproved: $this->autoApproved,
            createdAt: $this->createdAt,
            updatedAt: now(),
        );
    }

    public function isPending(): bool
    {
        return $this->status === ReturnStatus::PENDING;
    }

    public function isApproved(): bool
    {
        return $this->status === ReturnStatus::APPROVED;
    }

    public function isRejected(): bool
    {
        return $this->status === ReturnStatus::REJECTED;
    }

    public function isCompleted(): bool
    {
        return $this->status === ReturnStatus::COMPLETED;
    }

    public function canBeAutoApproved(): bool
    {
        return $this->isColdChain
            && $this->reasonType === ReturnReason::SPOILED
            && $this->createdAt->diffInHours(now()) <= 12;
    }

    public function hasImages(): bool
    {
        return !empty($this->images);
    }

    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'order_id' => $this->orderId,
            'buyer_id' => $this->buyerId,
            'seller_id' => $this->sellerId,
            'status' => $this->status->value,
            'reason_type' => $this->reasonType->value,
            'reason_comment' => $this->reasonComment,
            'total_amount' => $this->totalAmount->toArray(),
            'refund_amount' => $this->refundAmount->toArray(),
            'is_cold_chain' => $this->isColdChain,
            'return_method' => $this->returnMethod,
            'images' => $this->images,
            'approved_at' => $this->approvedAt?->toIso8601String(),
            'completed_at' => $this->completedAt?->toIso8601String(),
            'rejected_at' => $this->rejectedAt?->toIso8601String(),
            'reject_reason' => $this->rejectReason,
            'auto_approved' => $this->autoApproved,
            'created_at' => $this->createdAt->toIso8601String(),
            'updated_at' => $this->updatedAt->toIso8601String(),
        ];
    }
}
