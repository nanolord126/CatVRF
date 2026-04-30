<?php

declare(strict_types=1);

namespace Modules\CatCRM\Application\Services;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Redis;

final class CRMCacheService
{
    private const CACHE_TTL = 3600; // 1 hour
    private const CACHE_TAGS_PREFIX = 'crm';

    public function getCustomerLTV(int $customerId): ?array
    {
        return Cache::tags([$this->getCustomerTag($customerId)])
            ->remember("crm:customer:{$customerId}:ltv", self::CACHE_TTL, function () use ($customerId) {
                $customer = \Modules\CatCRM\Domain\Entities\Customer::with(['deals' => fn($q) => $q->won()])
                    ->find($customerId);
                
                if (!$customer) return null;

                return [
                    'total_spent' => $customer->deals->sum('value'),
                    'orders_count' => $customer->deals->count(),
                    'average_order_value' => $customer->orders_count > 0 
                        ? $customer->deals->sum('value') / $customer->orders_count 
                        : 0,
                ];
            });
    }

    public function invalidateCustomerCache(int $customerId): void
    {
        Cache::tags([$this->getCustomerTag($customerId)])->flush();
    }

    public function getLeadEstimatedValue(int $leadId): ?int
    {
        return Cache::tags([$this->getLeadTag($leadId)])
            ->remember("crm:lead:{$leadId}:estimated_value", self::CACHE_TTL, function () use ($leadId) {
                $lead = \Modules\CatCRM\Domain\Entities\B2BLead::find($leadId);
                return $lead?->estimateValue();
            });
    }

    public function invalidateLeadCache(int $leadId): void
    {
        Cache::tags([$this->getLeadTag($leadId)])->flush();
    }

    public function getTenantStats(int $tenantId): array
    {
        return Cache::tags([$this->getTenantTag($tenantId)])
            ->remember("crm:tenant:{$tenantId}:stats", 300, function () use ($tenantId) {
                return [
                    'total_customers' => \Modules\CatCRM\Domain\Entities\Customer::where('tenant_id', $tenantId)->count(),
                    'total_leads' => \Modules\CatCRM\Domain\Entities\B2BLead::where('tenant_id', $tenantId)->count(),
                    'total_deals' => \Modules\CatCRM\Domain\Entities\Deal::where('tenant_id', $tenantId)->count(),
                    'won_deals_value' => \Modules\CatCRM\Domain\Entities\Deal::where('tenant_id', $tenantId)
                        ->where('status', \Modules\CatCRM\Domain\Enums\DealStatus::Won)
                        ->sum('value'),
                ];
            });
    }

    public function invalidateTenantCache(int $tenantId): void
    {
        Cache::tags([$this->getTenantTag($tenantId)])->flush();
    }

    public function getPipelineConversionRate(int $pipelineId): float
    {
        return Cache::tags([$this->getPipelineTag($pipelineId)])
            ->remember("crm:pipeline:{$pipelineId}:conversion", 600, function () use ($pipelineId) {
                $total = \Modules\CatCRM\Domain\Entities\Deal::where('pipeline_id', $pipelineId)->count();
                if ($total === 0) return 0.0;

                $won = \Modules\CatCRM\Domain\Entities\Deal::where('pipeline_id', $pipelineId)
                    ->where('status', \Modules\CatCRM\Domain\Enums\DealStatus::Won)
                    ->count();

                return round(($won / $total) * 100, 2);
            });
    }

    private function getCustomerTag(int $customerId): string
    {
        return self::CACHE_TAGS_PREFIX . ':customer:' . $customerId;
    }

    private function getLeadTag(int $leadId): string
    {
        return self::CACHE_TAGS_PREFIX . ':lead:' . $leadId;
    }

    private function getTenantTag(int $tenantId): string
    {
        return self::CACHE_TAGS_PREFIX . ':tenant:' . $tenantId;
    }

    private function getPipelineTag(int $pipelineId): string
    {
        return self::CACHE_TAGS_PREFIX . ':pipeline:' . $pipelineId;
    }
}
