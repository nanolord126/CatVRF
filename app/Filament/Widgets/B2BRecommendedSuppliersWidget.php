<?php

namespace App\Filament\Widgets;

use Filament\Widgets\Widget;
use App\Models\B2BManufacturer;
use App\Models\B2BProduct;
use Illuminate\Support\Facades\Auth;
use Stancl\Tenancy\Facades\Tenancy;

class B2BRecommendedSuppliersWidget extends Widget
{
    protected static string $view = 'filament.widgets.b2b-recommended-suppliers-widget';

    protected int | string | array $columnSpan = 'full';

    public function getRecommendations(): array
    {
        // In real app, logic would use AI embeddings to match Tenant needs with Suppliers
        $isTenant = Tenancy::tenant() !== null;
        
        if ($isTenant) {
            // Recommendation for Businesses (Tenants) looking for Suppliers
            return B2BManufacturer::where('is_active', true)
                ->orderByDesc('ai_trust_score')
                ->limit(4)
                ->get()
                ->map(fn($m) => [
                    'name' => $m->name,
                    'score' => $m->ai_trust_score ?? 98,
                    'distance' => rand(2, 45) . ' km',
                    'insights' => 'Optimal for bulk ' . ($m->category ?? 'supplies'),
                    'type' => 'Supplier'
                ])
                ->toArray();
        } else {
            // Recommendation for B2B Panel (Manufacturers looking for Buyers/Leads)
            return [
                ['name' => 'PetCare Clinic Group', 'score' => 95, 'distance' => '12 km', 'insights' => 'Active procurement cycle', 'type' => 'Lead'],
                ['name' => 'VetNetwork LLC', 'score' => 92, 'distance' => '5 km', 'insights' => 'Matching stock availability', 'type' => 'Lead'],
                ['name' => 'HappyPaws Store', 'score' => 88, 'distance' => '28 km', 'insights' => 'Recurring demand predicted', 'type' => 'Lead'],
            ];
        }
    }
}
