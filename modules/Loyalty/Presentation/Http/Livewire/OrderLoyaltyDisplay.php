<?php

declare(strict_types=1);

namespace Modules\Loyalty\Livewire;

use Illuminate\Support\Facades\Cache;
use Livewire\Component;
use Modules\Loyalty\Application\Services\LoyaltyService;
use Modules\Loyalty\Domain\ValueObjects\Points;

final class OrderLoyaltyDisplay extends Component
{
    public int $guestId;
    public string $programId;
    public float $orderAmount;
    public ?string $tierSlug = null;
    public bool $showCalculation = true;

    public ?float $availablePoints = null;
    public ?float $earnedPoints = null;
    public ?string $pointsDescription = null;
    public ?string $tierName = null;
    public ?string $tierColor = null;

    public function mount(
        int $guestId,
        string $programId,
        float $orderAmount,
        ?string $tierSlug = null
    ): void {
        $this->guestId = $guestId;
        $this->programId = $programId;
        $this->orderAmount = $orderAmount;
        $this->tierSlug = $tierSlug;

        $this->loadLoyaltyData();
    }

    public function loadLoyaltyData(): void
    {
        $cacheKey = "loyalty:order:{$this->guestId}:{$this->programId}:{$this->orderAmount}";

        $data = Cache::remember($cacheKey, 300, function () {
            $loyaltyService = app(LoyaltyService::class);

            $balance = $loyaltyService->getProfileBalance($this->guestId, $this->programId);
            $this->availablePoints = $balance?->getValue() ?? 0.0;

            if ($this->showCalculation) {
                $dto = \Modules\Loyalty\Application\DTOs\CalculatePointsDTO::fromArray([
                    'program_id' => $this->programId,
                    'guest_id' => $this->guestId,
                    'order_amount' => $this->orderAmount,
                    'tier_slug' => $this->tierSlug,
                ]);

                $result = $loyaltyService->calculatePoints($dto);
                $this->earnedPoints = $result->totalPoints->getValue();
                $this->pointsDescription = $result->description;
            }

            // Load tier info
            $profile = app(\Modules\Loyalty\Domain\Repositories\GuestLoyaltyProfileRepositoryInterface::class)
                ->findByGuestAndProgram($this->guestId, $this->programId);

            if ($profile && $profile->getCurrentTierId()) {
                $tier = app(\Modules\Loyalty\Domain\Repositories\LoyaltyTierRepositoryInterface::class)
                    ->findById($profile->getCurrentTierId());
                if ($tier) {
                    $this->tierName = $tier->getName();
                    $this->tierColor = $tier->getColor();
                }
            }

            return [
                'available_points' => $this->availablePoints,
                'earned_points' => $this->earnedPoints,
                'points_description' => $this->pointsDescription,
                'tier_name' => $this->tierName,
                'tier_color' => $this->tierColor,
            ];
        });

        $this->availablePoints = $data['available_points'];
        $this->earnedPoints = $data['earned_points'];
        $this->pointsDescription = $data['points_description'];
        $this->tierName = $data['tier_name'];
        $this->tierColor = $data['tier_color'];
    }

    public function render()
    {
        return view('loyalty::livewire.order-loyalty-display', [
            'availablePoints' => $this->availablePoints,
            'earnedPoints' => $this->earnedPoints,
            'pointsDescription' => $this->pointsDescription,
            'tierName' => $this->tierName,
            'tierColor' => $this->tierColor,
        ]);
    }
}
