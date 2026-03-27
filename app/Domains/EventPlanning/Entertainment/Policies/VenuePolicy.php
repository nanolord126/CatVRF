<?php

declare(strict_types=1);

namespace App\Domains\EventPlanning\Entertainment\Policies;

use App\Domains\EventPlanning\Entertainment\Models\Venue;
use App\Models\User;
use Illuminate\Auth\Access\Response;

/**
 * КАНОН 2026 — VENUE POLICY
 */
final class VenuePolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('view_venues');
    }

    public function view(User $user, Venue $venue): bool
    {
        return $user->tenant_id === $venue->tenant_id;
    }

    public function create(User $user): bool
    {
        return $user->can('manage_entertainment');
    }

    public function update(User $user, Venue $venue): bool
    {
        return $user->tenant_id === $venue->tenant_id && $user->can('manage_entertainment');
    }

    public function delete(User $user, Venue $venue): bool
    {
        return $user->tenant_id === $venue->tenant_id && $user->hasRole('admin');
    }
}
