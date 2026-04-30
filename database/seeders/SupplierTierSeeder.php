<?php

declare(strict_types=1);

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Modules\Supermarket\Domain\Models\SupplierTier;
use Illuminate\Support\Facades\DB;

class SupplierTierSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        DB::transaction(function () {
            $tiers = [
                [
                    'name' => 'Производитель',
                    'slug' => 'manufacturer',
                    'code' => SupplierTier::CODE_MANUFACTURER,
                    'level' => 1,
                    'description' => 'Завод, ферма или производитель товара. Начало цепочки поставок.',
                    'allowed_transitions' => [
                        SupplierTier::CODE_TRADING_HOUSE,
                        SupplierTier::CODE_WHOLESALER,
                        SupplierTier::CODE_RETAILER,
                    ],
                    'requires_primary_document' => false,
                    'is_manufacturer' => true,
                    'can_sell_direct' => true,
                    'max_chain_depth' => 4,
                    'is_active' => true,
                ],
                [
                    'name' => 'Торговый дом',
                    'slug' => 'trading-house',
                    'code' => SupplierTier::CODE_TRADING_HOUSE,
                    'level' => 2,
                    'description' => 'Торговый дом или дистрибьютор. Промежуточное звено между производителем и оптовиками.',
                    'allowed_transitions' => [
                        SupplierTier::CODE_WHOLESALER,
                        SupplierTier::CODE_RETAILER,
                    ],
                    'requires_primary_document' => true,
                    'is_manufacturer' => false,
                    'can_sell_direct' => true,
                    'max_chain_depth' => 3,
                    'is_active' => true,
                ],
                [
                    'name' => 'Оптовик',
                    'slug' => 'wholesaler',
                    'code' => SupplierTier::CODE_WHOLESALER,
                    'level' => 3,
                    'description' => 'Оптовый продавец. Продает оптом розничным магазинам и бизнес-клиентам.',
                    'allowed_transitions' => [
                        SupplierTier::CODE_WHOLESALER,
                        SupplierTier::CODE_RETAILER,
                    ],
                    'requires_primary_document' => true,
                    'is_manufacturer' => false,
                    'can_sell_direct' => false,
                    'max_chain_depth' => 2,
                    'is_active' => true,
                ],
                [
                    'name' => 'Розничный продавец',
                    'slug' => 'retailer',
                    'code' => SupplierTier::CODE_RETAILER,
                    'level' => 4,
                    'description' => 'Розничный магазин или бизнес на платформе. Конечное звено цепочки.',
                    'allowed_transitions' => [],
                    'requires_primary_document' => true,
                    'is_manufacturer' => false,
                    'can_sell_direct' => false,
                    'max_chain_depth' => 0,
                    'is_active' => true,
                ],
            ];

            foreach ($tiers as $tier) {
                SupplierTier::updateOrCreate(
                    ['code' => $tier['code']],
                    array_merge($tier, ['uuid' => (string) \Illuminate\Support\Str::uuid()])
                );
            }

            $this->command->info('Supplier tiers seeded successfully.');
        });
    }
}
