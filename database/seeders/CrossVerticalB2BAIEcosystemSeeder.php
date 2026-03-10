<?php

namespace Database\Seeders;

use App\Models\B2BManufacturer;
use App\Models\B2BProduct;
use App\Models\Tenant;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class CrossVerticalB2BAIEcosystemSeeder extends Seeder
{
    public function run(): void
    {
        $mfg = B2BManufacturer::firstOrCreate(['name' => 'EcoSystem Global Logistics 2026'], [
            'description' => 'Primary supplier for all ecosystem verticals.',
            'compliance_score' => 0.99,
            'correlation_id' => Str::uuid(),
        ]);

        $catalog = [
            'All'         => [
                ['name' => 'Monthly Office Space Rental (Central)', 'price' => 1500.00, 'tags' => 'Real Estate,Rent,Office'],
                ['name' => 'Monthly Warehouse Storage (B2B)', 'price' => 800.00, 'tags' => 'Real Estate,Rent,Storage'],
                ['name' => 'Pure Drinking Water (19L Cooler Bottle)', 'price' => 5.50, 'tags' => 'Water Delivery,Consumables'],
                ['name' => 'Pure Sparkling Water (Box of 24x0.5L)', 'price' => 12.00, 'tags' => 'Water Delivery,Consumables'],
            ],
            'Hotels'      => [
                ['name' => 'Premium Egyptian Cotton Bedding Set', 'price' => 45.00, 'tags' => 'Textiles,Luxury'],
                ['name' => 'Industrial Vacuum Cleaner X-200', 'price' => 280.00, 'tags' => 'Cleaning,Equipment'],
                ['name' => 'Eco-friendly Bathroom Amenities Bulk', 'price' => 0.85, 'tags' => 'Amenities,Consumables'],
            ],
            'Beauty'      => [
                ['name' => 'Professional Hair Dye Kit (48 shades)', 'price' => 120.00, 'tags' => 'Cosmetics,Consumables'],
                ['name' => 'Ergonomic Hydraulic Salon Chair', 'price' => 350.00, 'tags' => 'Salon Furniture,Equipment'],
                ['name' => 'Disposable Towels (Box of 500)', 'price' => 35.00, 'tags' => 'Consumables,Cleaning'],
            ],
            'Restaurants' => [
                ['name' => 'Chef Grade Stainless Steel Knife Set', 'price' => 190.00, 'tags' => 'Professional Kitchen,Tools'],
                ['name' => 'Organic Cold-pressed Olive Oil (20L)', 'price' => 110.00, 'tags' => 'Ingredients,Organic'],
                ['name' => 'Biodegradable Takeaway Containers (1000pcs)', 'price' => 95.00, 'tags' => 'Packaging,Green'],
            ],
            'Flowers'     => [
                ['name' => 'Craft Wrapping Paper Roll (50m)', 'price' => 18.00, 'tags' => 'Packaging,Decor'],
                ['name' => 'Premium Orchid Fertilizer 5L', 'price' => 45.00, 'tags' => 'Fertilizers,Consumables'],
            ],
            'Taxi'        => [
                ['name' => 'Synthetic Motor Oil 5W-30 (Bulk 200L)', 'price' => 850.00, 'tags' => 'Lubricants,Maintenance'],
                ['name' => 'All-Season Tire Set (Eco-Drive)', 'price' => 220.00, 'tags' => 'Tires,Parts'],
            ],
            'Clinics'      => [
                ['name' => 'Digital Ultrasonography System Z-1', 'price' => 4500.00, 'tags' => 'Medical Equipment,Tech'],
                ['name' => 'Surgical Nitrile Gloves (Case of 2000)', 'price' => 140.00, 'tags' => 'Consumables,Lab Supplies'],
            ],
            'Vet'         => [
                ['name' => 'Post-Surgical Pet Recovery Collar (Mixed Sizes)', 'price' => 8.50, 'tags' => 'Pet Care,Medical'],
                ['name' => 'Hypoallergenic Puppy Feed (50kg)', 'price' => 115.00, 'tags' => 'Feed,Nutrition'],
            ],
            'Events'      => [
                ['name' => 'Wireless LED Uplighting System (8 units)', 'price' => 520.00, 'tags' => 'AV Equipment,Decor'],
                ['name' => 'Folding Event Chair - White Resin', 'price' => 22.00, 'tags' => 'Furniture,Events'],
            ],
            'Sports'      => [
                ['name' => 'Adjustable Dumbbell Set (5-50lb)', 'price' => 310.00, 'tags' => 'Gym Gear,Equipment'],
                ['name' => 'Isolate Whey Protein 5kg', 'price' => 85.00, 'tags' => 'Nutrition,Supplements'],
            ],
            'Education'   => [
                ['name' => 'Interactive 4K Touch Panel 65"', 'price' => 1100.00, 'tags' => 'Tech,Educational Kits'],
                ['name' => 'Eco-Recycled Stationery Bulk Pack', 'price' => 40.00, 'tags' => 'Stationery,Consumables'],
            ],
        ];

        foreach ($catalog as $vertical => $products) {
            foreach ($products as $p) {
                B2BProduct::updateOrCreate([
                    'sku' => strtoupper($vertical) . '-' . Str::random(6),
                ], [
                    'manufacturer_id' => $mfg->id,
                    'name' => $p['name'],
                    'description' => "High-quality supplies for the $vertical vertical.",
                    'unit' => 'unit',
                    'base_wholesale_price' => $p['price'],
                    'min_order_quantity' => rand(5, 50),
                    'stock_quantity' => rand(100, 1000),
                    'tags' => $p['tags'] . ",$vertical",
                    'correlation_id' => Str::uuid(),
                ]);
            }
        }
    }
}
