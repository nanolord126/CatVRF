<?php

declare(strict_types=1);

namespace Modules\Loyalty\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Modules\Loyalty\Application\Services\LoyaltyService;
use Modules\Loyalty\Domain\Repositories\GuestLoyaltyProfileRepositoryInterface;

final readonly class CheckTierUpgradeJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 2;
    public int $timeout = 60;

    public function __construct(
        private string $profileId
    ) {
        $this->onQueue(config('loyalty.queue.queue_name', 'loyalty'));
    }

    public function handle(
        LoyaltyService $loyaltyService,
        GuestLoyaltyProfileRepositoryInterface $profileRepository
    ): void {
        try {
            $profile = $profileRepository->findById($this->profileId);
            if (!$profile) {
                Log::warning('Profile not found for tier upgrade check', [
                    'profile_id' => $this->profileId,
                ]);
                return;
            }

            $loyaltyService->checkTierUpgrade($profile);

            Log::info('Tier upgrade check completed', [
                'profile_id' => $this->profileId,
                'guest_id' => $profile->getGuestId(),
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to check tier upgrade', [
                'profile_id' => $this->profileId,
                'error' => $e->getMessage(),
            ]);

            $this->release(30);
        }
    }
}
