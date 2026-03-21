<?php declare(strict_types=1);

namespace App\Domains\RealEstate\Policies;

use Illuminate\Auth\Access\Response;

/**
 * Policy для заявок на ипотеку.
 * Production 2026.
 */
final class MortgageApplicationPolicy
{
    public function viewAny($user): bool
    {
        return $user?->is_admin || false;
    }

    public function view($user, $application): Response
    {
        return $application->client_id === $user?->id || $user?->is_admin
            ? Response::allow()
            : Response::deny('Нет прав');
    }

    public function create($user): Response
    {
        return $user ? Response::allow() : Response::deny('Требуется авторизация');
    }

    public function update($user, $application): Response
    {
        return $user?->is_admin ? Response::allow() : Response::deny('Только админ');
    }
}
