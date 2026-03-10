<?php

namespace Database\Seeders;

use App\Models\B2B\B2BProduct;
use App\Models\B2B\Supplier;
use App\Models\B2B\B2BRecommendation;
use App\Models\Tenant;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

/**
 * Seeder for B2B AI Ecosystem with realistic data for 2026.
 */
class B2BAIEcosystemSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // 1. Ensure Suppliers exist
        $suppliers = [
            ['name' => 'BioTech Medical Supplies', 'vertical' => 'Medical'],
            ['name' => 'Organic Fresh Distributors', 'vertical' => 'Food'],
            ['name' => 'EventPro Lighting & Audio', 'vertical' => 'Events'],
        ];

        foreach ($suppliers as $s) {
            $supplier = Supplier::updateOrCreate(['name' => $s['name']], [
                'correlation_id' => (string) Str::uuid(),
            ]);

            // 2. Create products for this supplier
            for ($i = 0; $i < 5; $i++) {
                B2BProduct::updateOrCreate(
                    ['name' => "{$s['vertical']} Product X" . ($i + 1), 'supplier_id' => $supplier->id],
                    [
                        'sku' => Str::upper(Str::random(8)),
                        'price' => rand(100, 5000),
                        'category' => $s['vertical'],
                        'correlation_id' => (string) Str::uuid(),
                    ]
                );
            }
        }

        // 3. Generate AI recommendations for existing tenants
        $tenants = Tenant::all();
        $products = B2BProduct::all();

        foreach ($tenants as $tenant) {
            foreach ($products->random(3) as $product) {
                B2BRecommendation::updateOrCreate(
                    [
                        'tenant_id' => $tenant->getTenantKey(),
                        'recommendable_id' => $product->id,
                        'recommendable_type' => B2BProduct::class,
                    ],
                    [
                        'uuid' => (string) Str::uuid(),
                        'match_score' => rand(75, 99) / 100,
                        'type' => 'SupplierBuy',
                        'reasoning' => [
                            'text' => 'Strong demand pattern in ' . $tenant->name . ' geographic region.',
                            'confidence' => 0.95,
                        ],
                        'embeddings_version' => 'v1.1-march-2026',
                        'correlation_id' => (string) Str::uuid(),
                    ]
                );
            }
        }
    }
}
