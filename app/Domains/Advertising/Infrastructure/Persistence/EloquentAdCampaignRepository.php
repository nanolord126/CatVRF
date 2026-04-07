<?php

declare(strict_types=1);

namespace App\Domains\Advertising\Infrastructure\Persistence;

use App\Domains\Advertising\Domain\Entities\AdCampaign;
use App\Domains\Advertising\Domain\Interfaces\AdCampaignRepositoryInterface;
use App\Models\Advertising\AdCampaign as AdCampaignModel;
use Carbon\Carbon;
use Illuminate\Support\Collection;

final class EloquentAdCampaignRepository implements AdCampaignRepositoryInterface
{
    public function findById(int $id): ?AdCampaign
    {
        $model = AdCampaignModel::find($id);
        return $model ? $this->toEntity($model) : null;
    }

    public function findByUuid(string $uuid): ?AdCampaign
    {
        $model = AdCampaignModel::where('uuid', $uuid)->first();
        return $model ? $this->toEntity($model) : null;
    }

    public function save(AdCampaign $campaign): AdCampaign
    {
        $model = AdCampaignModel::updateOrCreate(
            ['id' => $campaign->id],
            [
                'uuid' => $campaign->uuid,
                'tenant_id' => $campaign->tenant_id,
                'name' => $campaign->name,
                'status' => $campaign->status,
                'start_at' => $campaign->start_at,
                'end_at' => $campaign->end_at,
                'budget' => $campaign->budget,
                'spent' => $campaign->spent,
                'pricing_model' => $campaign->pricing_model,
                'targeting_criteria' => $campaign->targeting_criteria,
                'correlation_id' => $campaign->correlation_id,
            ]
        );

        return $this->toEntity($model);
    }

    public function getActiveCampaignsForTenant(int $tenantId): Collection
    {
        return AdCampaignModel::where('tenant_id', $tenantId)
            ->where('status', 'active')
            ->where('start_at', '<=', Carbon::now())
            ->where('end_at', '>=', Carbon::now())
            ->get()
            ->map(fn (AdCampaignModel $model) => $this->toEntity($model));
    }
    
    public function updateSpent(int $campaignId, int $amount): void
    {
        AdCampaignModel::where('id', $campaignId)->increment('spent', $amount);
    }

    private function toEntity(AdCampaignModel $model): AdCampaign
    {
        return new AdCampaign(
            id: $model->id,
            uuid: $model->uuid,
            tenant_id: $model->tenant_id,
            name: $model->name,
            status: $model->status,
            start_at: $model->start_at,
            end_at: $model->end_at,
            budget: $model->budget,
            spent: $model->spent,
            pricing_model: $model->pricing_model,
            targeting_criteria: $model->targeting_criteria,
            correlation_id: $model->correlation_id
        );
    }
}
