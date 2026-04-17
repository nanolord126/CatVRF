<?php declare(strict_types=1);

namespace App\Providers;

use App\Domains\Beauty\Models\Appointment;
use App\Domains\Education\Models\Enrollment;
use App\Domains\Hotels\Models\Booking;
use App\Domains\Taxi\Models\TaxiRide;
use App\Models\Channels\BusinessChannel;
use App\Models\Channels\Post;
use App\Models\Company\Employee;
use App\Models\Company\Payroll;
use App\Models\Restaurant\RestaurantOrder;
use App\Models\Wallet\Payout;
use App\Models\Wallet\Wallet;
use App\Policies\Beauty\BeautyAppointmentPolicy;
use App\Policies\Channels\ChannelPolicy;
use App\Policies\Channels\PostPolicy;
use App\Policies\Company\EmployeePolicy;
use App\Policies\Company\PayrollPolicy;
use App\Policies\Hotels\HotelBookingPolicy;
use App\Policies\PayoutPolicy;
use App\Policies\RestaurantOrderPolicy;
use App\Policies\TaxiRidePolicy;
use App\Policies\WalletManagementPolicy;
use Illuminate\Foundation\Support\Providers\AuthServiceProvider as ServiceProvider;
use Illuminate\Support\Facades\Gate;

final class AuthServiceProvider extends ServiceProvider
{
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
        Enrollment::class => LearningPathPolicy::class,
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
