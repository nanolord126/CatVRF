<?php

declare(strict_types=1);

namespace App\Domains\Staff\Domain\Entities;

use App\Domains\Staff\Domain\ValueObjects\StaffId;
use App\Domains\Staff\Domain\ValueObjects\ContactInfo;
use App\Domains\Staff\Domain\ValueObjects\FullName;
use App\Domains\Staff\Domain\Enums\StaffStatus;
use App\Domains\Staff\Domain\Enums\Vertical;
use Illuminate\Support\Collection;
use Ramsey\Uuid\UuidInterface;

final class StaffMember
{
    private StaffId $id;
    private FullName $fullName;
    private ContactInfo $contactInfo;
    private StaffStatus $status;
    private UuidInterface $tenantId;
    private ?UuidInterface $businessGroupId;
    private Collection $roles;
    private Collection $schedules;
    private Vertical $vertical;
    private ?UuidInterface $verticalResourceId;

    public function __construct(
        StaffId $id,
        FullName $fullName,
        ContactInfo $contactInfo,
        StaffStatus $status,
        UuidInterface $tenantId,
        Vertical $vertical,
        ?UuidInterface $verticalResourceId = null,
        ?UuidInterface $businessGroupId = null
    ) {
        $this->id = $id;
        $this->fullName = $fullName;
        $this->contactInfo = $contactInfo;
        $this->status = $status;
        $this->tenantId = $tenantId;
        $this->vertical = $vertical;
        $this->verticalResourceId = $verticalResourceId;
        $this->businessGroupId = $businessGroupId;
        $this->roles = new Collection();
        $this->schedules = new Collection();
    }

    public function getId(): StaffId
    {
        return $this->id;
    }

    public function getFullName(): FullName
    {
        return $this->fullName;
    }

    public function getContactInfo(): ContactInfo
    {
        return $this->contactInfo;
    }

    public function getStatus(): StaffStatus
    {
        return $this->status;
    }

    public function getTenantId(): UuidInterface
    {
        return $this->tenantId;
    }

    public function getBusinessGroupId(): ?UuidInterface
    {
        return $this->businessGroupId;
    }

    public function getVertical(): Vertical
    {
        return $this->vertical;
    }

    public function getVerticalResourceId(): ?UuidInterface
    {
        return $this->verticalResourceId;
    }

    public function assignRole(Role $role): void
    {
        if (!$this->roles->contains('id', $role->getId())) {
            $this->roles->push($role);
        }
    }

    public function revokeRole(Role $role): void
    {
        $this->roles = $this->roles->filter(fn (Role $r) => !$r->getId()->equals($role->getId()));
    }

    public function getRoles(): Collection
    {
        return $this->roles;
    }

    public function addSchedule(Schedule $schedule): void
    {
        $this->schedules->push($schedule);
    }

    public function getSchedules(): Collection
    {
        return $this->schedules;
    }

    public function changeStatus(StaffStatus $newStatus): void
    {
        $this->status = $newStatus;
    }
}
