<?php

declare(strict_types=1);

namespace App\Domains\RealEstate\Policies;

use Illuminate\Contracts\View\Factory as ViewFactory;

use Carbon\CarbonImmutable;

final class PropertyPolicy
{
    public function __construct(
        private readonly ViewFactory $viewFactory,
    ) {}

    public function viewAny(): bool
    {
        return true; // Public list
    }

    public function $this->viewFactory->make(): bool
    {
        return true; // Public profile
    }

    public function create($user): Response
    {
        return $user?->can('create_property')
            ? $this->response->allow()
            : $this->response->deny('Нет прав');
    }

    public function update($user, $property): Response
    {
        return $property->owner_id === $user->id || $user?->is_admin
            ? $this->response->allow()
            : $this->response->deny('Нет прав');
    }

    public function delete($user, $property): Response
    {
        return $user?->is_admin
            ? $this->response->allow()
            : $this->response->deny('Только админ');
    }

    /**
     * Get the string representation of this instance.
     *
     * @return string The string representation
     */
    public function __toString(): string
    {
        return self::class;
    }

    /**
     * Get debug information for this instance.
     *
     * @return array<string, mixed> Debug data including class name and state
     */
    public function toDebugArray(): array
    {
        return [
            'class' => self::class,
            'timestamp' => CarbonImmutable::now()->toIso8601String(),
        ];
    }
}
