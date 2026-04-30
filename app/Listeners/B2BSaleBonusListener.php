<?php

declare(strict_types=1);

namespace App\Listeners;

use App\Events\B2BSaleBonusCreated;
use App\Services\ManagerBonusService;
use App\Traits\WithAuditLogging;
use Illuminate\Contracts\Queue\ShouldQueue;

final class B2BSaleBonusListener implements ShouldQueue
{
    use WithAuditLogging;

    public function __construct(
        private readonly ManagerBonusService $bonusService
    ) {}

    public function handle(B2BSaleBonusCreated $event): void
    {
        $bonusPercent = match ($event->referenceType) {
            'b2b_sale' => ManagerBonus::B2B_SALE_BONUS_PERCENT,
            'referral' => ManagerBonus::REFERRAL_BONUS_PERCENT,
            default => ManagerBonus::B2B_SALE_BONUS_PERCENT,
        };

        $bonus = $this->bonusService->createGenericBonus(
            $event->managerId,
            $event->referenceType,
            $event->referenceId,
            $event->saleAmount,
            $bonusPercent,
            $event->tenantId,
            $event->verticalId
        );

        $this->logCreated('manager_bonus', $bonus->id, [
            'manager_id' => $event->managerId,
            'reference_type' => $event->referenceType,
            'reference_id' => $event->referenceId,
            'sale_amount' => $event->saleAmount,
            'bonus_amount' => $bonus->bonus_amount,
        ], $event->managerId);
    }
}
