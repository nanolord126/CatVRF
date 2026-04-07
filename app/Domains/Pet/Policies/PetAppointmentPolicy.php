<?php declare(strict_types=1);

namespace App\Domains\Pet\Policies;

final class PetAppointmentPolicy
{

    public function viewAny(User $user): Response
        {
            return $this->response->allow();
        }

        public function view(User $user, PetAppointment $appointment): Response
        {
            return ($appointment->owner_id === $user->id || $appointment->clinic->owner_id === $user->id)
                && $appointment->tenant_id === $user->current_tenant_id
                ? $this->response->allow()
                : $this->response->deny('Unauthorized');
        }

        public function create(User $user): Response
        {
            return $this->response->allow();
        }

        public function update(User $user, PetAppointment $appointment): Response
        {
            return $appointment->owner_id === $user->id && $appointment->tenant_id === $user->current_tenant_id
                ? $this->response->allow()
                : $this->response->deny('Unauthorized');
        }

        public function cancel(User $user, PetAppointment $appointment): Response
        {
            return ($appointment->owner_id === $user->id || $appointment->clinic->owner_id === $user->id)
                && $appointment->tenant_id === $user->current_tenant_id
                ? $this->response->allow()
                : $this->response->deny('Unauthorized');
        }

        public function complete(User $user, PetAppointment $appointment): Response
        {
            return $appointment->clinic->owner_id === $user->id && $appointment->tenant_id === $user->current_tenant_id
                ? $this->response->allow()
                : $this->response->deny('Unauthorized');
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
