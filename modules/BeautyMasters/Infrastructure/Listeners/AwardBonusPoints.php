<?php

declare(strict_types=1);

namespace Modules\BeautyMasters\Infrastructure\Listeners;

use Modules\BeautyMasters\Domain\Events\AppointmentCompleted;
use Modules\BeautyMasters\Application\Services\BonusService;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Event;

final readonly class AwardBonusPoints
{
    public function __construct(
        private BonusService $bonusService,
    ) {
    }

    public function handle(AppointmentCompleted $event): void
    {
        try {
            $profile = $this->bonusService->addBonusPoints(
                userId: $event->userId,
                venueId: $event->venueId,
                appointmentId: $event->appointmentId,
                spentAmount: $event->finalPrice
            );

            Event::dispatch(new \Modules\BeautyMasters\Domain\Events\BonusPointsEarned(
                userId: $event->userId,
                venueId: $event->venueId,
                appointmentId: $event->appointmentId,
                points: (int) ($event->finalPrice * 0.01),
                tier: $profile->tier,
            ));

            Log::info('Bonus points awarded', [
                'user_id' => $event->userId,
                'venue_id' => $event->venueId,
                'appointment_id' => $event->appointmentId,
                'amount' => $event->finalPrice,
            ]);
        } catch (\Throwable $e) {
            Log::error('Failed to award bonus points', [
                'appointment_id' => $event->appointmentId,
                'error' => $e->getMessage(),
            ]);
        }
    }
}
