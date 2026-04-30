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
use App\Policies\StaffSecurityPolicy;
use App\Policies\TaxiRidePolicy;
use App\Policies\WalletManagementPolicy;
use Illuminate\Contracts\Auth\Access\Gate as GateContract;
use Illuminate\Foundation\Support\Providers\AuthServiceProvider as ServiceProvider;

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
        // Security
        'staff-security' => StaffSecurityPolicy::class,
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
        $this->app->make(GateContract::class)->define('view-admin-dashboard', static fn($user) => $user->hasRole('admin'));
        $this->app->make(GateContract::class)->define('manage-platforms', static fn($user) => $user->hasRole('admin'));
        $this->app->make(GateContract::class)->define('manage-disputes', static fn($user) => $user->hasRole('admin'));

        // Business owner gates
        $this->app->make(GateContract::class)->define('view-business-dashboard', static fn($user) => $user->hasRole('business_owner'));
        $this->app->make(GateContract::class)->define('manage-employees', static fn($user) => $user->hasRole('business_owner'));
        $this->app->make(GateContract::class)->define('manage-payroll', static fn($user) => $user->hasRole('business_owner'));

            // Manager gates
            $this->app->make(GateContract::class)->define('manage-operations', static fn($user) => $user->hasRole('manager'));
            $this->app->make(GateContract::class)->define('view-analytics', static fn($user) => $user->hasRole('manager'));

            // Accountant gates
            $this->app->make(GateContract::class)->define('manage-payments', static fn($user) => $user->hasRole('accountant'));
            $this->app->make(GateContract::class)->define('view-financial-reports', static fn($user) => $user->hasRole('accountant'));

            // Employee gates
            $this->app->make(GateContract::class)->define('manage-tasks', static fn($user) => $user->hasRole('employee'));
            $this->app->make(GateContract::class)->define('view-schedule', static fn($user) => $user->hasRole('employee'));
        }
}
