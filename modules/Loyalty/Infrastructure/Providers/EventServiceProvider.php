<?php

declare(strict_types=1);

namespace Modules\Loyalty\Infrastructure\Providers;

use Illuminate\Foundation\Support\Providers\EventServiceProvider as ServiceProvider;
use Modules\Loyalty\Domain\Events\GuestEnrolled;
use Modules\Loyalty\Domain\Events\PointsEarned;
use Modules\Loyalty\Domain\Events\RewardRedeemed;
use Modules\Loyalty\Domain\Events\TierUpgraded;
use Modules\Loyalty\Infrastructure\Listeners\SendGuestEnrolledNotification;
use Modules\Loyalty\Infrastructure\Listeners\SendPointsEarnedNotification;
use Modules\Loyalty\Infrastructure\Listeners\SendRewardRedeemedNotification;
use Modules\Loyalty\Infrastructure\Listeners\SendTierUpgradedNotification;

final class LoyaltyEventServiceProvider extends ServiceProvider
{
    protected $listen = [
        PointsEarned::class => [
            SendPointsEarnedNotification::class,
        ],
        TierUpgraded::class => [
            SendTierUpgradedNotification::class,
        ],
        RewardRedeemed::class => [
            SendRewardRedeemedNotification::class,
        ],
        GuestEnrolled::class => [
            SendGuestEnrolledNotification::class,
        ],
    ];

    public function boot(): void
    {
        parent::boot();
    }
}
