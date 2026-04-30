<?php

declare(strict_types=1);

namespace Modules\CatCRM\Domain\Staff;

use Carbon\CarbonImmutable;
use Modules\CatCRM\Domain\Staff\ValueObjects\LeaveId;
use Modules\CatCRM\Domain\Staff\ValueObjects\LeaveType;
use Modules\CatCRM\Domain\Staff\ValueObjects\LeaveStatus;

/**
 * Leave — Отпуск или больничный
 * 
 * Readonly DDD entity для представления отпуска/больничного
 */
final readonly class Leave
{
    public function __construct(
        public LeaveId $id,
        public int $tenantId,
        public int $employeeId,
        public LeaveType $type,
        public LeaveStatus $status,
        public CarbonImmutable $startDate,
        public CarbonImmutable $endDate,
        public string $reason,
        public ?int $approvedBy,
        public ?CarbonImmutable $approvedAt,
        public ?string $rejectReason,
        public array $metadata,
        public CarbonImmutable $createdAt,
        public CarbonImmutable $updatedAt,
    ) {}

    public function getDurationDays(): int
    {
        return $this->startDate->diffInDays($this->endDate) + 1;
    }

    public function isApproved(): bool
    {
        return $this->status === LeaveStatus::Approved;
    }

    public function isPending(): bool
    {
        return $this->status === LeaveStatus::Pending;
    }

    public function isActive(): bool
    {
        $now = CarbonImmutable::now();
        return $this->isApproved() 
            && $now->gte($this->startDate) 
            && $now->lte($this->endDate);
    }
}
