<?php

declare(strict_types=1);

namespace Modules\PromoCampaign\Infrastructure\Adapters;

use Modules\PromoCampaign\Domain\Repositories\PromoCampaignRepositoryInterface;
use Modules\PromoCampaign\Domain\Entities\PromoCampaign;
use Modules\PromoCampaign\Domain\Enums\PromoType;
use Modules\PromoCampaign\Domain\Enums\PromoStatus;
use Modules\PromoCampaign\Domain\ValueObjects\PromoCode;
use Modules\PromoCampaign\Infrastructure\Models\PromoCampaignModel;
use Modules\PromoCampaign\Infrastructure\Models\PromoUseModel;
use Carbon\CarbonImmutable;

/**
 * Инфраструктурный Eloquent-адаптер интерфейса репозитория промо-кампаний.
 *
 * Исключительно отвечает за конвертацию доменных сущностей в ORM-базированные модели и обратно.
 * Строго применяет пессимистичную базу данных блокировку (lockForUpdate) при извлечении кампаний.
 */
final readonly class EloquentPromoCampaignRepository implements PromoCampaignRepositoryInterface
{
    /**
     * Извлекает промо-кампанию с применением исключительной блокировки строк `FOR UPDATE`.
     * Это абсолютно необходимо для предотвращения race condition при конкурентных запросах.
     *
     * @param string $code Промокод.
     * @param int $tenantId Идентификатор тенанта.
     * @return PromoCampaign|null
     */
    public function findByCodeAndTenant(string $code, int $tenantId): ?PromoCampaign
    {
        $model = PromoCampaignModel::where('code', strtoupper($code))
            ->where('tenant_id', $tenantId)
            ->lockForUpdate()
            ->first();

        if ($model === null) {
            return null;
        }

        return $this->mapToDomain($model);
    }

    /**
     * Безупречно синхронизирует и сохраняет доменную сущность в постоянное SQL хранилище.
     *
     * @param PromoCampaign $campaign
     * @return void
     */
    public function save(PromoCampaign $campaign): void
    {
        PromoCampaignModel::updateOrCreate(
            ['uuid' => $campaign->getId(), 'tenant_id' => $campaign->getTenantId()],
            [
                'type' => $campaign->getType()->value,
                'code' => $campaign->getCode()->getValue(),
                'status' => $campaign->getStatus()->value,
                'budget' => (int) (new \ReflectionProperty($campaign, 'budget'))->getValue($campaign), // В домене budget приватный и readonly, но для адаптера вытягиваем аккуратно
                'spent_budget' => (int) (new \ReflectionProperty($campaign, 'spentBudget'))->getValue($campaign),
                'min_order_amount' => (new \ReflectionProperty($campaign, 'minOrderAmount'))->getValue($campaign),
                'max_uses_total' => (int) (new \ReflectionProperty($campaign, 'maxUsesTotal'))->getValue($campaign),
                'current_uses' => (int) (new \ReflectionProperty($campaign, 'currentUses'))->getValue($campaign),
                'start_at' => (new \ReflectionProperty($campaign, 'startAt'))->getValue($campaign),
                'end_at' => (new \ReflectionProperty($campaign, 'endAt'))->getValue($campaign)
            ]
        );
    }

    /**
     * Строго записывает факт использования промокода в специализированную аудиторскую таблицу базы данных.
     */
    public function logUsage(string $campaignId, int $tenantId, int $userId, string $action, int $discountKopecks, string $correlationId): void
    {
        PromoUseModel::create([
            'promo_campaign_uuid' => $campaignId,
            'tenant_id' => $tenantId,
            'user_id' => $userId,
            'action' => $action,
            'discount_amount' => $discountKopecks,
            'correlation_id' => $correlationId
        ]);
    }

    /**
     * Вспомогательный метод для аккуратной гидратации инфраструктурной модели Eloquent
     * в защищенную и строго типизированную доменную сущность (Aggregate Root).
     */
    private function mapToDomain(PromoCampaignModel $model): PromoCampaign
    {
        return new PromoCampaign(
            id: $model->uuid,
            tenantId: (int) $model->tenant_id,
            type: PromoType::from($model->type),
            code: new PromoCode($model->code),
            status: PromoStatus::from($model->status),
            budget: (int) $model->budget,
            spentBudget: (int) $model->spent_budget,
            minOrderAmount: $model->min_order_amount ? (int) $model->min_order_amount : null,
            maxUsesTotal: (int) $model->max_uses_total,
            currentUses: (int) $model->current_uses,
            startAt: $model->start_at ? CarbonImmutable::parse($model->start_at) : null,
            endAt: $model->end_at ? CarbonImmutable::parse($model->end_at) : null
        );
    }
}
