<?php declare(strict_types=1);

namespace App\Domains\RealEstate\Policies;

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
                ? $this->response->allow()
                : $this->response->deny('Нет прав');
        }

        public function update($user, $listing): Response
        {
            return $listing->property->owner_id === $user?->id || $user?->is_admin
                ? $this->response->allow()
                : $this->response->deny('Нет прав');
        }

        public function delete($user, $listing): Response
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
        return static::class;
    }

    /**
     * Get debug information for this instance.
     *
     * @return array<string, mixed> Debug data including class name and state
     */
    public function toDebugArray(): array
    {
        return [
            'class' => static::class,
            'timestamp' => now()->toIso8601String(),
        ];
    }
}
