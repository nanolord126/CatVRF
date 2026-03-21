<?php

declare(strict_types=1);

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\B2BManufacturer;
use App\Models\B2BProduct;
use App\Models\WholesaleContract;
use App\Models\Tenant;
use Illuminate\Support\Str;

/**
 * B2B маркетплейс (НЕ ЗАПУСКАТЬ В PRODUCTION).
 */
final class B2BMarketplaceSeeder extends Seeder
{
    /**
     * Seed the B2B Marketplace with realistic data.
     */
    public function run(): void
    {
        // 1. Create Manufacturers
        $manufacturers = [
            [
                'name' => 'Wholesale Foods Global',
                'brand_name' => 'GlobalFood',
                'registration_number' => '7701122334',
                'contact_email' => 'sales@globalfood.com',
                'category' => 'Food',
                'ai_trust_score' => 4.9,
            ],
            [
                'name' => 'MedSupply Dynamics',
                'brand_name' => 'MedDyn',
                'registration_number' => '7702233445',
                'contact_email' => 'info@meddyn.com',
                'category' => 'Medical',
                'ai_trust_score' => 4.8,
            ],
            [
                'name' => 'EventPro Logistics',
                'brand_name' => 'EventPro',
                'registration_number' => '7703344556',
                'contact_email' => 'partners@eventpro.com',
                'category' => 'Events',
                'ai_trust_score' => 4.2,
            ],
            [
                'name' => 'RealEstate B2B Solutions',
                'brand_name' => 'RE-Manage',
                'registration_number' => '7704455667',
                'contact_email' => 'rentals@re-manage.com',
                'category' => 'Real Estate',
                'ai_trust_score' => 4.7,
            ],
            [
                'name' => 'PureWater Delivery Systems',
                'brand_name' => 'AquaPro',
                'registration_number' => '7705566778',
                'contact_email' => 'sales@aquapro.com',
                'category' => 'Water Delivery',
                'ai_trust_score' => 4.5,
            ],
        ];

        foreach ($manufacturers as $m) {
            $manufacturer = B2BManufacturer::create(array_merge($m, [
                'contact_phone' => '+79001234567',
                'legal_address' => 'Sample Legal Address ' . Str::random(10),
                'correlation_id' => (string) Str::uuid(),
            ]));

            // Create some products for each manufacturer
            $itemCount = 5;
            for ($i = 1; $i <= $itemCount; $i++) {
                $productName = $manufacturer->category . ' Bulk Item ' . $i;
                $unit = 'box';

                if ($manufacturer->category === 'Food') {
                    $unit = 'kg';
                } elseif ($manufacturer->category === 'Real Estate') {
                    $productName = 'Monthly Office Rent - Tier ' . $i;
                    $unit = 'month';
                } elseif ($manufacturer->category === 'Water Delivery') {
                    $productName = 'Bulk Water Delivery (19L) x' . (10 * $i);
                    $unit = 'pallet';
                }

                B2BProduct::create([
                    'manufacturer_id' => $manufacturer->id,
                    'sku' => strtoupper(substr($manufacturer->name, 0, 3)) . '-' . Str::random(5),
                    'name' => $productName,
                    'description' => 'A high-quality bulk product/service for your business needs.',
                    'unit' => $unit,
                    'base_wholesale_price' => rand(10, 5000),
                    'min_order_quantity' => $manufacturer->category === 'Real Estate' ? 1 : rand(10, 100),
                    'stock_quantity' => rand(100, 5000),
                    'correlation_id' => (string) Str::uuid(),
                ]);
            }
        }

        // 2. Create sample contracts for existing tenants (if any)
        $tenants = Tenant::all();
        $firstManufacturer = B2BManufacturer::first();

        foreach ($tenants as $tenant) {
            WholesaleContract::create([
                'manufacturer_id' => $firstManufacturer->id,
                'tenant_id' => $tenant->id,
                'contract_number' => 'CONT-' . Str::upper(Str::random(8)),
                'signed_at' => now()->subMonths(2),
                'expires_at' => now()->addYear(),
                'special_discount_percent' => 10.00,
                'credit_limit' => 50000.00,
                'deferred_payment_days' => 30,
                'status' => 'active',
                'correlation_id' => (string) Str::uuid(),
            ]);
        }
    }
}
