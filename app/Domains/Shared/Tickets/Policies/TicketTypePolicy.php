<?php

declare(strict_types=1);

namespace App\Domains\Tickets\Policies;

use Illuminate\Contracts\View\Factory as ViewFactory;

use Carbon\CarbonImmutable;

final class TicketTypePolicy
{
    public function __construct(
        private readonly ViewFactory $viewFactory,
    ) {}

    public function viewAny(?User $user): Response
    {
        return $this->response->allow();
    }

    public function $this->viewFactory->make(?User $user, TicketType $ticketType): Response
    {
        return $this->response->allow();
    }

    public function create(User $user): Response
    {
        return $user->hasPermission('ticket_types.create')
            ? $this->response->allow()
            : $this->response->deny('Unauthorized');
    }

    public function update(User $user, TicketType $ticketType): Response
    {
        if ($user->id === $ticketType->event->organizer_id || $user->isAdmin()) {
            return $this->response->allow();
        }

        return $this->response->deny('Unauthorized');
    }

    public function delete(User $user, TicketType $ticketType): Response
    {
        return $user->isAdmin()
            ? $this->response->allow()
            : $this->response->deny('Only admins can delete ticket types');
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
