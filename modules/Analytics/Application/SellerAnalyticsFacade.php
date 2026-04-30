<?php

declare(strict_types=1);

namespace Modules\Analytics\Facades;

use Illuminate\Support\Facades\Facade;
use Modules\Analytics\Application\DTOs\CLVPredictionDTO;
use Modules\Analytics\Application\Services\SellerCLVService;

/**
 * Seller Analytics Facade
 * 
 * Provides a simple, fluent interface for seller analytics operations.
 * Includes CLV prediction methods for easy access across the application.
 * 
 * Usage:
 * SellerAnalytics::clv()->forBuyer($buyerId)->predict();
 * SellerAnalytics::clv()->getTopBuyersByCLV(50);
 * 
 * @method static CLVPredictionDTO predictForBuyer(int $sellerId, int $buyerId, int $tenantId)
 * @method static array getTopBuyersByCLV(int $sellerId, int $tenantId, int $limit = 50)
 * @method static array getHighChurnRiskBuyers(int $sellerId, int $tenantId, float $threshold = 0.5, int $limit = 100)
 * @method static array getSegmentDistribution(int $sellerId, int $tenantId)
 * @method static array getAggregatedCLVMetrics(int $sellerId, int $tenantId)
 * @method static void invalidateSellerCache(int $sellerId, int $tenantId)
 * 
 * @see \Modules\Analytics\Application\Services\SellerCLVService
 */
final class SellerAnalyticsFacade extends Facade
{
    /**
     * Get the CLV service instance.
     */
    public static function clv(): SellerCLVService
    {
        return app(SellerCLVService::class);
    }

    /**
     * Get the registered name of the component.
     */
    protected static function getFacadeAccessor(): string
    {
        return SellerCLVService::class;
    }
}
