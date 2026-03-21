<?php declare(strict_types=1);

namespace App\Domains\RealEstate\Policies;

use Illuminate\Auth\Access\Response;

/**
 * Policy для объявлений о продаже.
 * Production 2026.
 */
final class SaleListingPolicy
{
    public function viewAny(): bool
    {
        return true;
    }

    public function view(): bool
    {
        return true;
    }

    public function create($user): Response
    {
        return $user?->can('create_sale_listing')
            ? Response::allow()
            : Response::deny('Нет прав');
    }

    public function update($user, $listing): Response
    {
        return $listing->property->owner_id === $user?->id || $user?->is_admin
            ? Response::allow()
            : Response::deny('Нет прав');
    }

    public function delete($user, $listing): Response
    {
        return $user?->is_admin
            ? Response::allow()
            : Response::deny('Только админ');
    }
}
