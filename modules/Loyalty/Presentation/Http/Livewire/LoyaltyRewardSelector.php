<?php

declare(strict_types=1);

namespace Modules\Loyalty\Livewire;

use Illuminate\Support\Facades\Cache;
use Livewire\Component;
use Modules\Loyalty\Domain\Repositories\LoyaltyRewardRepositoryInterface;
use Modules\Loyalty\Domain\Repositories\GuestLoyaltyProfileRepositoryInterface;
use Modules\Loyalty\Domain\ValueObjects\Points;

final class LoyaltyRewardSelector extends Component
{
    public string $profileId;
    public ?string $selectedRewardId = null;

    public ?float $availablePoints = null;
    public array $availableRewards = [];

    public function mount(string $profileId): void
    {
        $this->profileId = $profileId;
        $this->loadData();
    }

    public function loadData(): void
    {
        $cacheKey = "loyalty:rewards:{$this->profileId}";

        $data = Cache::remember($cacheKey, 300, function () {
            $profileRepository = app(GuestLoyaltyProfileRepositoryInterface::class);
            $rewardRepository = app(LoyaltyRewardRepositoryInterface::class);

            $profile = $profileRepository->findById($this->profileId);
            if (!$profile) {
                return [
                    'available_points' => 0.0,
                    'available_rewards' => [],
                ];
            }

            $this->availablePoints = $profile->getAvailablePoints()->getValue();

            $rewards = $rewardRepository->findAvailableByProgramId($profile->getLoyaltyProgramId());
            $this->availableRewards = [];

            foreach ($rewards as $reward) {
                if (!$profile->canSpendPoints($reward->getPointsCost())) {
                    continue;
                }

                if ($reward->getTargetTiers() !== null && $profile->getCurrentTierId() !== null) {
                    $tier = app(\Modules\Loyalty\Domain\Repositories\LoyaltyTierRepositoryInterface::class)
                        ->findById($profile->getCurrentTierId());
                    if ($tier && !$reward->isAvailableToTier($tier->getSlug())) {
                        continue;
                    }
                }

                $this->availableRewards[] = [
                    'id' => $reward->getId(),
                    'uuid' => $reward->getUuid(),
                    'name' => $reward->getName(),
                    'description' => $reward->getDescription(),
                    'points_cost' => $reward->getPointsCost()->getValue(),
                    'type' => $reward->getType()->value,
                    'value_type' => $reward->getValueType(),
                    'value_amount' => $reward->getValueAmount(),
                    'image_url' => $reward->getImageUrl(),
                ];
            }

            return [
                'available_points' => $this->availablePoints,
                'available_rewards' => $this->availableRewards,
            ];
        });

        $this->availablePoints = $data['available_points'];
        $this->availableRewards = $data['available_rewards'];
    }

    public function selectReward(string $rewardId): void
    {
        $this->selectedRewardId = $rewardId;
        $this->dispatch('reward-selected', rewardId: $rewardId);
    }

    public function render()
    {
        return view('loyalty::livewire.loyalty-reward-selector', [
            'availablePoints' => $this->availablePoints,
            'availableRewards' => $this->availableRewards,
            'selectedRewardId' => $this->selectedRewardId,
        ]);
    }
}
