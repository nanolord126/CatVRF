<?php

declare(strict_types=1);

namespace App\Domains\Staff\Domain\Entities;

use App\Domains\Staff\Domain\ValueObjects\RoleId;
use Illuminate\Support\Collection;

/**
 * Class Role
 *
 * Part of the Staff vertical domain.
 * Follows CatVRF 9-layer architecture.
 *
 * Component of the CatVRF platform.
 * Follows strict coding standards:
 * - final class (no inheritance unless required)
 * - private readonly properties
 * - Constructor injection only
 * - correlation_id in all operations
 *
 * @package App\Domains\Staff\Domain\Entities
 */
final class Role
{
    private RoleId $id;
    private string $name;
    private Collection $permissions;

    public function __construct(RoleId $id, string $name, array $permissions = [])
    {
        $this->id = $id;
        $this->name = $name;
        $this->permissions = new Collection($permissions);
    }

    /**
     * Handle getId operation.
     *
     * @throws \DomainException
     */
    public function getId(): RoleId
    {
        return $this->id;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function getPermissions(): Collection
    {
        return $this->permissions;
    }

    public function hasPermission(string $permission): bool
    {
        return $this->permissions->contains($permission);
    }
}
