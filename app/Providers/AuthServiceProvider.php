<?php declare(strict_types=1);

namespace App\Providers;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

final class AuthServiceProvider extends Model
{
    use HasFactory;

    // TODO: Проверить и восстановить содержимое класса, если оно было утеряно
    /**
         * @var array<class-string, class-string>
         */
        protected $policies = [
            TaxiRide::class => TaxiRidePolicy::class,
            Appointment::class => BeautyAppointmentPolicy::class,
            RestaurantOrder::class => RestaurantOrderPolicy::class,
            Booking::class => HotelBookingPolicy::class,
            Employee::class => EmployeePolicy::class,
            Payroll::class => PayrollPolicy::class,
            Payout::class => PayoutPolicy::class,
            Wallet::class => WalletManagementPolicy::class,
            // Channels
            BusinessChannel::class => ChannelPolicy::class,
            Post::class             => PostPolicy::class,
        ];

        public function boot(): void
        {
            $this->registerPolicies();

            // RBAC Gates
            $this->defineGates();
        }

        private function defineGates(): void
        {
            // Admin gates
            Gate::define('view-admin-dashboard', static fn($user) => $user->hasRole('admin'));
            Gate::define('manage-platforms', static fn($user) => $user->hasRole('admin'));
            Gate::define('manage-disputes', static fn($user) => $user->hasRole('admin'));

            // Business owner gates
            Gate::define('view-business-dashboard', static fn($user) => $user->hasRole('business_owner'));
            Gate::define('manage-employees', static fn($user) => $user->hasRole('business_owner'));
            Gate::define('manage-payroll', static fn($user) => $user->hasRole('business_owner'));

            // Manager gates
            Gate::define('manage-operations', static fn($user) => $user->hasRole('manager'));
            Gate::define('view-analytics', static fn($user) => $user->hasRole('manager'));

            // Accountant gates
            Gate::define('manage-payments', static fn($user) => $user->hasRole('accountant'));
            Gate::define('view-financial-reports', static fn($user) => $user->hasRole('accountant'));

            // Employee gates
            Gate::define('manage-tasks', static fn($user) => $user->hasRole('employee'));
            Gate::define('view-schedule', static fn($user) => $user->hasRole('employee'));
        }
}
