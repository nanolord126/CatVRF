<?php

namespace App\Jobs\AI;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use App\Models\B2BProduct;
use App\Models\Tenant;
use App\Services\B2B\B2BAIAnalyticsService;
use App\Notifications\B2B\AIOpportunityDetected;
use Illuminate\Support\Facades\Notification;

class RunDailyB2BMarketAnalysisJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function handle(B2BAIAnalyticsService $aiService): void
    {
        // 1. Scan all products for price opportunities
        $products = B2BProduct::all();

        foreach ($products as $product) {
            $suggestion = $aiService::suggestOptimalPrice($product);

            // If a significant price drop is suggested (e.g. > 5%)
            if ($suggestion['price_change_pc'] <= -5) {
                $this->notifyInterestedTenants($product, $suggestion);
            }
        }
    }

    protected function notifyInterestedTenants(B2BProduct $product, array $aiData): void
    {
        // In a real 2026 schema-per-tenant, we might notify across all tenants that consume this category
        $tenants = Tenant::all(); 
        
        foreach ($tenants as $tenant) {
            // Business logic to check if tenant buys this category...
            // For now, notifying all active B2B buyers in the global scope
            Notification::send($tenant, new AIOpportunityDetected($product, $aiData));
        }
    }
}
