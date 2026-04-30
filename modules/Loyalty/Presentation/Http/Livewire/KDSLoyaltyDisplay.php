<?php

declare(strict_types=1);

namespace Modules\Loyalty\Livewire;

use Illuminate\Support\Facades\Cache;
use Livewire\Component;
use Modules\Loyalty\Application\Services\LoyaltyService;

final class KDSLoyaltyDisplay extends Component
{
    public int $guestId;
    public string $programId;
    public int $pollingInterval = 30; // seconds

    public ?float $availablePoints = null;
    public ?string $tierName = null;
    public ?string $tierColor = null;
    public ?float $tierProgress = null;
    public ?float $nextTierPoints = null;
    public ?string $nextTierName = null;

    public function mount(int $guestId, string $programId, int $pollingInterval = 30): void
    {
        $this->guestId = $guestId;
        $this->programId = $programId;
        $this->pollingInterval = $pollingInterval;

        $this->loadLoyaltyData();
    }

    public function loadLoyaltyData(): void
    {
        $cacheKey = "loyalty:kds:{$this->guestId}:{$this->programId}";

        $data = Cache::remember($cacheKey, 60, function () {
            $loyaltyService = app(LoyaltyService::class);

            $balance = $loyaltyService->getProfileBalance($this->guestId, $this->programId);
            $this->availablePoints = $balance?->getValue() ?? 0.0;

            $profile = app(\Modules\Loyalty\Domain\Repositories\GuestLoyaltyProfileRepositoryInterface::class)
                ->findByGuestAndProgram($this->guestId, $this->programId);

            if ($profile) {
                $currentTier = null;
                if ($profile->getCurrentTierId()) {
                    $currentTier = app(\Modules\Loyalty\Domain\Repositories\LoyaltyTierRepositoryInterface::class)
                        ->findById($profile->getCurrentTierId());
                }

                if ($currentTier) {
                    $this->tierName = $currentTier->getName();
                    $this->tierColor = $currentTier->getColor();
                }

                // Calculate progress to next tier
                $program = app(\Modules\Loyalty\Domain\Repositories\LoyaltyProgramRepositoryInterface::class)
                    ->findById($this->programId);

                if ($program && $program->isTierSystemEnabled()) {
                    $tiers = app(\Modules\Loyalty\Domain\Repositories\LoyaltyTierRepositoryInterface::class)
                        ->findByProgramIdSorted($this->programId);

                    foreach ($tiers as $tier) {
                        if ($tier->getId() === $profile->getCurrentTierId()) {
                            continue;
                        }

                        if ($profile->getEarnedPoints()->isLessThan($tier->getMinPoints())) {
                            $this->nextTierName = $tier->getName();
                            $this->nextTierPoints = $tier->getMinPoints()->getValue();

                            if ($currentTier) {
                                $currentMin = $currentTier->getMinPoints()->getValue();
                                $nextMin = $tier->getMinPoints()->getValue();
                                $earned = $profile->getEarnedPoints()->getValue();
                                $range = $nextMin - $currentMin;
                                $progress = $range > 0 ? (($earned - $currentMin) / $range) * 100 : 0;
                                $this->tierProgress = max(0, min(100, $progress));
                            }
                            break;
                        }
                    }
                }
            }

            return [
                'available_points' => $this->availablePoints,
                'tier_name' => $this->tierName,
                'tier_color' => $this->tierColor,
                'tier_progress' => $this->tierProgress,
                'next_tier_points' => $this->nextTierPoints,
                'next_tier_name' => $this->nextTierName,
            ];
        });

        $this->availablePoints = $data['available_points'];
        $this->tierName = $data['tier_name'];
        $this->tierColor = $data['tier_color'];
        $this->tierProgress = $data['tier_progress'];
        $this->nextTierPoints = $data['next_tier_points'];
        $this->nextTierName = $data['next_tier_name'];
    }

    public function render()
    {
        return view('loyalty::livewire.kds-loyalty-display', [
            'availablePoints' => $this->availablePoints,
            'tierName' => $this->tierName,
            'tierColor' => $this->tierColor,
            'tierProgress' => $this->tierProgress,
            'nextTierPoints' => $this->nextTierPoints,
            'nextTierName' => $this->nextTierName,
        ]);
    }
}
