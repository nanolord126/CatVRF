<?php declare(strict_types=1);

namespace App\Domains\Beauty\Policies;

use App\Domains\Beauty\Models\BookingSlot;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

final class BookingSlotPolicy
{
    use HandlesAuthorization;

    public function viewAny(User $user): bool
    {
        return $user->can('view_any_beauty::booking_slot');
    }

    public function view(User $user, BookingSlot $slot): bool
    {
        if ($user->can('view_any_beauty::booking_slot')) {
            return true;
        }

        if ($user->can('view_own_beauty::booking_slot')) {
            return $slot->customer_id === $user->id;
        }

        return false;
    }

    public function create(User $user): bool
    {
        return $user->can('create_beauty::booking_slot');
    }

    public function hold(User $user, BookingSlot $slot): bool
    {
        if (!$user->can('create_beauty::booking_slot')) {
            return false;
        }

        if ($slot->tenant_id !== tenant()->id) {
            return false;
        }

        if (!$slot->isAvailable()) {
            return false;
        }

        return true;
    }

    public function release(User $user, BookingSlot $slot): bool
    {
        if (!$user->can('update_beauty::booking_slot')) {
            return false;
        }

        if ($slot->tenant_id !== tenant()->id) {
            return false;
        }

        if ($user->can('update_any_beauty::booking_slot')) {
            return true;
        }

        if ($user->can('update_own_beauty::booking_slot')) {
            return $slot->customer_id === $user->id;
        }

        return false;
    }

    public function confirm(User $user, BookingSlot $slot): bool
    {
        if (!$user->can('update_beauty::booking_slot')) {
            return false;
        }

        if ($slot->tenant_id !== tenant()->id) {
            return false;
        }

        return true;
    }

    public function update(User $user, BookingSlot $slot): bool
    {
        if (!$user->can('update_beauty::booking_slot')) {
            return false;
        }

        if ($slot->tenant_id !== tenant()->id) {
            return false;
        }

        if ($user->can('update_any_beauty::booking_slot')) {
            return true;
        }

        if ($user->can('update_own_beauty::booking_slot')) {
            return $slot->customer_id === $user->id;
        }

        return false;
    }

    public function delete(User $user, BookingSlot $slot): bool
    {
        if (!$user->can('delete_beauty::booking_slot')) {
            return false;
        }

        if ($slot->tenant_id !== tenant()->id) {
            return false;
        }

        return true;
    }

    public function restore(User $user, BookingSlot $slot): bool
    {
        if (!$user->can('restore_beauty::booking_slot')) {
            return false;
        }

        if ($slot->tenant_id !== tenant()->id) {
            return false;
        }

        return true;
    }

    public function forceDelete(User $user, BookingSlot $slot): bool
    {
        if (!$user->can('force_delete_beauty::booking_slot')) {
            return false;
        }

        if ($slot->tenant_id !== tenant()->id) {
            return false;
        }

        return true;
    }
}
