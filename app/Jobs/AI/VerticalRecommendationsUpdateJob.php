<?php

namespace App\Jobs\AI;

use App\Models\Tenant;
use App\Models\B2BProduct;
use App\Models\B2BRecommendation;
use App\Services\Common\AI\RecommendationServiceVertical;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class VerticalRecommendationsUpdateJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function handle(RecommendationServiceVertical $service): void
    {
        Log::info("Starting Daily Vertical AI Re-ranking...");

        // Process each tenant to pre-calculate best matches for their vertical
        Tenant::all()->each(function (Tenant $tenant) use ($service) {
            $vertical = $tenant->vertical_type ?? 'General';
            
            // Clear old cache-like recommendations
            B2BRecommendation::where('tenant_id', $tenant->id)
                ->where('type', 'VerticalTailored')
                ->delete();

            $products = $service->forVertical($tenant, $vertical, 20);

            foreach ($products as $product) {
                B2BRecommendation::create([
                    'tenant_id' => $tenant->id,
                    'recommendable_id' => $product->id,
                    'recommendable_type' => B2BProduct::class,
                    'type' => 'VerticalTailored',
                    'match_score' => $service->supplierMatchForVertical($product, $vertical),
                    'correlation_id' => bin2hex(random_bytes(16)),
                ]);
            }
        });

        Log::info("Vertical AI Re-ranking completed.");
    }
}
