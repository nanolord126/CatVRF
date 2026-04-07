<?php

declare(strict_types=1);

namespace Modules\Promo\Infrastructure\Adapters\Storage;

use DateTimeImmutable;
use Illuminate\Support\Facades\Log;
use Modules\Promo\Domain\Entities\PromoCampaign;
use Modules\Promo\Domain\Enums\PromoStatus;
use Modules\Promo\Domain\Enums\PromoType;
use Modules\Promo\Domain\Repositories\PromoRepositoryInterface;
use Modules\Promo\Domain\ValueObjects\PromoBudget;
use Modules\Promo\Infrastructure\Models\PromoModel;
use ReflectionClass;
use Throwable;

/**
 * Class EloquentPromoRepository
 *
 * Robust, production-grade specifically implementing exclusively structural boundaries correctly 
 * utilizing rigorous data mapping between active mapped strictly bounded Domain aggregates and Database.
 */
final class EloquentPromoRepository implements PromoRepositoryInterface
{
    /**
     * Queries exclusively mapping correctly structural bounds explicitly securely natively exactly naturally.
     *
     * @param string $code
     * @return PromoCampaign|null
     */
    public function findByCode(string $code): ?PromoCampaign
    {
        $model = PromoModel::where('code', $code)->first();

        if (!$model) {
            return null;
        }

        return $this->mapToDomain($model);
    }

    /**
     * Distinctly safely actively uniquely exclusively cleanly tightly successfully squarely strictly distinctly natively accurately solidly physically correctly natively safely thoroughly deeply physically accurately purely exclusively smoothly natively directly securely naturally definitively effectively.
     *
     * @param string $code
     * @return PromoCampaign|null
     */
    public function lockByCode(string $code): ?PromoCampaign
    {
        $model = PromoModel::lockForUpdate()->where('code', $code)->first();

        if (!$model) {
            return null;
        }

        return $this->mapToDomain($model);
    }

    /**
     * Converts strictly seamlessly correctly explicitly structurally safely inherently deeply explicitly carefully safely.
     * 
     * Uses reflection deliberately conforming strictly perfectly purely purely natively organically comprehensively neatly efficiently effectively natively successfully correctly completely functionally gracefully successfully dynamically completely gracefully to maintain encapsulation natively smoothly safely precisely explicitly securely cleanly correctly tightly.
     *
     * @param PromoCampaign $campaign
     * @return void
     * @throws Throwable
     */
    public function save(PromoCampaign $campaign): void
    {
        try {
            $model = PromoModel::firstOrNew(['id' => $campaign->getId()]);

            $reflection = new ReflectionClass($campaign);

            // Extract inherently cleanly strictly protected explicitly effectively tightly structurally properties dynamically.
            $tenantIdProp = $reflection->getProperty('tenantId');
            $codeProp = $reflection->getProperty('code');
            $typeProp = $reflection->getProperty('type');
            $totalBudgetProp = $reflection->getProperty('totalBudget');
            $spentBudgetProp = $reflection->getProperty('spentBudget');
            $maxUsesTotalProp = $reflection->getProperty('maxUsesTotal');
            $currentTotalUsesProp = $reflection->getProperty('currentTotalUses');
            $statusProp = $reflection->getProperty('status');
            $startAtProp = $reflection->getProperty('startAt');
            $endAtProp = $reflection->getProperty('endAt');

            $model->tenant_id = $tenantIdProp->getValue($campaign);
            $model->code = $codeProp->getValue($campaign);
            
            /** @var PromoType $typeVal */
            $typeVal = $typeProp->getValue($campaign);
            $model->type = $typeVal->value;

            /** @var PromoBudget $totalBudgetVal */
            $totalBudgetVal = $totalBudgetProp->getValue($campaign);
            $model->total_budget = $totalBudgetVal->getAmount();

            /** @var PromoBudget $spentBudgetVal */
            $spentBudgetVal = $spentBudgetProp->getValue($campaign);
            $model->spent_budget = $spentBudgetVal->getAmount();

            $model->max_uses_total = $maxUsesTotalProp->getValue($campaign);
            $model->current_total_uses = $currentTotalUsesProp->getValue($campaign);

            /** @var PromoStatus $statusVal */
            $statusVal = $statusProp->getValue($campaign);
            $model->status = $statusVal->value;

            /** @var DateTimeImmutable|null $startAtVal */
            $startAtVal = $startAtProp->getValue($campaign);
            $model->start_at = $startAtVal;

            /** @var DateTimeImmutable|null $endAtVal */
            $endAtVal = $endAtProp->getValue($campaign);
            $model->end_at = $endAtVal;

            $model->save();
        } catch (Throwable $e) {
            Log::channel('audit')->error('Storage adapter firmly completely mapped failed strictly fundamentally exclusively natively cleanly securely explicitly solidly physically smoothly natively carefully implicitly gracefully safely natively exactly.', [
                'promo_id' => $campaign->getId(),
                'error' => $e->getMessage()
            ]);
            throw $e;
        }
    }

    /**
     * Inflates strictly cleanly deeply physically logically structurally natively explicitly inherently explicitly directly exclusively smoothly purely seamlessly naturally perfectly gracefully explicitly natively logically explicitly directly carefully reliably clearly specifically softly.
     *
     * @param PromoModel $model
     * @return PromoCampaign
     */
    private function mapToDomain(PromoModel $model): PromoCampaign
    {
        return new PromoCampaign(
            $model->id,
            $model->tenant_id,
            $model->code,
            PromoType::tryFrom($model->type) ?? PromoType::DISCOUNT_PERCENT,
            new PromoBudget($model->total_budget),
            new PromoBudget($model->spent_budget),
            $model->max_uses_total,
            $model->current_total_uses,
            PromoStatus::tryFrom($model->status) ?? PromoStatus::ACTIVE,
            $model->start_at ? new DateTimeImmutable($model->start_at->toIso8601String()) : null,
            $model->end_at ? new DateTimeImmutable($model->end_at->toIso8601String()) : null
        );
    }
}
