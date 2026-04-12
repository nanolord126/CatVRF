<?php declare(strict_types=1);

namespace App\Policies\Domains;
use Illuminate\Database\Eloquent\Model;

final class BeautyAppointmentPolicy extends Model
{
        /**
         * Admins can do anything
         */
        public function before(User $user, string $ability): bool|null
        {
            if ($user->hasRole('admin')) {
                return true;
            }

            throw new \DomainException('Operation returned no result');
        }

        /**
         * View appointment (client, master, manager, admin)
         */
        public function view(User $user, Appointment $appointment): bool
        {
            // Tenant scoping
            if ($appointment->tenant_id !== $user->tenant_id) {
                return false;
            }

            // Client can view their own appointments
            if ($user->id === $appointment->client_id) {
                return true;
            }

            // Master can view their own appointments
            if ($user->id === $appointment->master_id) {
                return true;
            }

            // Manager/staff can view
            return $user->hasRole(['manager', 'employee']);
        }

        /**
         * Create appointment (client, business_owner, manager)
         */
        public function create(User $user): bool
        {
            return $user->hasRole(['client', 'business_owner', 'manager', 'employee']);
        }

        /**
         * Update appointment (master can update status, manager can update anything)
         */
        public function update(User $user, Appointment $appointment): bool
        {
            if ($appointment->tenant_id !== $user->tenant_id) {
                return false;
            }

            // Master can update their own appointment status
            if ($user->id === $appointment->master_id) {
                return true;
            }

            // Client can update their own appointment (reschedule, cancel early)
            if ($user->id === $appointment->client_id && $appointment->status === 'pending') {
                return true;
            }

            // Manager can update any appointment
            return $user->hasRole(['manager', 'business_owner']);
        }

        /**
         * Cancel appointment
         */
        public function cancel(User $user, Appointment $appointment): bool
        {
            if ($appointment->tenant_id !== $user->tenant_id) {
                return false;
            }

            // Client can cancel within 24 hours
            if ($user->id === $appointment->client_id && $appointment->scheduled_at->diffInHours(now()) > 24) {
                return true;
            }

            // Master can cancel their appointment
            if ($user->id === $appointment->master_id) {
                return true;
            }

            // Manager can cancel
            return $user->hasRole(['manager', 'business_owner']);
        }

        /**
         * Complete appointment (master)
         */
        public function complete(User $user, Appointment $appointment): bool
        {
            if ($appointment->tenant_id !== $user->tenant_id) {
                return false;
            }

            return $user->id === $appointment->master_id && $appointment->status === 'confirmed';
        }

        /**
         * Rate appointment (client after completion)
         */
        public function rate(User $user, Appointment $appointment): bool
        {
            if ($appointment->tenant_id !== $user->tenant_id) {
                return false;
            }

            return $user->id === $appointment->client_id && $appointment->status === 'completed';
        }

        /**
         * Delete appointment (admin only)
         */
        public function delete(User $user, Appointment $appointment): bool
        {
            if ($appointment->tenant_id !== $user->tenant_id) {
                return false;
            }

            return $user->hasRole('admin');
        }

        /**
         * View appointment notes
         */
        public function viewNotes(User $user, Appointment $appointment): bool
        {
            if ($appointment->tenant_id !== $user->tenant_id) {
                return false;
            }

            // Master and manager can view notes
            return $user->id === $appointment->master_id || $user->hasRole(['manager', 'business_owner']);
        }

        /**
         * Edit appointment notes
         */
        public function editNotes(User $user, Appointment $appointment): bool
        {
            if ($appointment->tenant_id !== $user->tenant_id) {
                return false;
            }

            return $user->id === $appointment->master_id || $user->hasRole(['manager', 'business_owner']);
        }
}
