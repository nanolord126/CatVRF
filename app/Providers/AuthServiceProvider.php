<?php
declare(strict_types=1);

namespace App\Providers;

use App\Domains\Taxi\Models\TaxiRide;
use App\Domains\Beauty\Models\Appointment;
use App\Domains\Food\Models\RestaurantOrder;
use App\Domains\Hotels\Models\Booking;
use App\Models\Employee;
use App\Models\Payroll;
use App\Models\Payout;
use App\Models\Wallet;
use App\Policies\Auto\TaxiRidePolicy;
use App\Policies\Beauty\BeautyAppointmentPolicy;
use App\Policies\Food\RestaurantOrderPolicy;
use App\Policies\Hotels\HotelBookingPolicy;
use App\Policies\EmployeePolicy;
use App\Policies\PayoutPolicy;
use App\Policies\PayrollPolicy;
use App\Policies\WalletManagementPolicy;
use App\Policies\ChannelPolicy;
use App\Policies\PostPolicy;
use App\Domains\Channels\Models\BusinessChannel;
use App\Domains\Channels\Models\Post;
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
        $this->gate->define('view-admin-dashboard', static fn($user) => $user->hasRole('admin'));
        $this->gate->define('manage-platforms', static fn($user) => $user->hasRole('admin'));
        $this->gate->define('manage-disputes', static fn($user) => $user->hasRole('admin'));

        // Business owner gates
        $this->gate->define('view-business-dashboard', static fn($user) => $user->hasRole('business_owner'));
        $this->gate->define('manage-employees', static fn($user) => $user->hasRole('business_owner'));
        $this->gate->define('manage-payroll', static fn($user) => $user->hasRole('business_owner'));

        // Manager gates
        $this->gate->define('manage-operations', static fn($user) => $user->hasRole('manager'));
        $this->gate->define('view-analytics', static fn($user) => $user->hasRole('manager'));

        // Accountant gates
        $this->gate->define('manage-payments', static fn($user) => $user->hasRole('accountant'));
        $this->gate->define('view-financial-reports', static fn($user) => $user->hasRole('accountant'));

        // Employee gates
        $this->gate->define('manage-tasks', static fn($user) => $user->hasRole('employee'));
        $this->gate->define('view-schedule', static fn($user) => $user->hasRole('employee'));
    }
}
