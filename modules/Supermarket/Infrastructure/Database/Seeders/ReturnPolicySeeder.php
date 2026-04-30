<?php

declare(strict_types=1);

namespace Modules\Supermarket\Database\Seeders;

use Modules\Supermarket\Infrastructure\Models\ReturnPolicy;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

final class ReturnPolicySeeder extends Seeder
{
    public function run(): void
    {
        DB::statement('SET FOREIGN_KEY_CHECKS=0;');
        ReturnPolicy::truncate();
        DB::statement('SET FOREIGN_KEY_CHECKS=1;');

        $policies = [
            [
                'sub_vertical' => 'meat_shops',
                'vertical' => 'supermarket',
                'max_days' => 2,
                'allowed_reasons' => ['spoiled', 'damaged', 'wrong_item'],
                'cold_chain_only_defect' => true,
                'requires_photo' => true,
                'requires_temperature' => true,
                'max_refund_percent' => 100,
                'is_active' => true,
            ],
            [
                'sub_vertical' => 'dairy_products',
                'vertical' => 'supermarket',
                'max_days' => 3,
                'allowed_reasons' => ['spoiled', 'damaged', 'wrong_item'],
                'cold_chain_only_defect' => true,
                'requires_photo' => true,
                'requires_temperature' => true,
                'max_refund_percent' => 100,
                'is_active' => true,
            ],
            [
                'sub_vertical' => 'confectionery',
                'vertical' => 'supermarket',
                'max_days' => 7,
                'allowed_reasons' => ['spoiled', 'damaged', 'wrong_item', 'changed_mind'],
                'cold_chain_only_defect' => false,
                'requires_photo' => false,
                'requires_temperature' => false,
                'max_refund_percent' => 100,
                'is_active' => true,
            ],
            [
                'sub_vertical' => 'groceries',
                'vertical' => 'supermarket',
                'max_days' => 14,
                'allowed_reasons' => ['wrong_item', 'changed_mind', 'damaged'],
                'cold_chain_only_defect' => false,
                'requires_photo' => false,
                'requires_temperature' => false,
                'max_refund_percent' => 100,
                'is_active' => true,
            ],
            [
                'sub_vertical' => 'beverages',
                'vertical' => 'supermarket',
                'max_days' => 30,
                'allowed_reasons' => ['wrong_item', 'damaged', 'changed_mind'],
                'cold_chain_only_defect' => false,
                'requires_photo' => false,
                'requires_temperature' => false,
                'max_refund_percent' => 100,
                'is_active' => true,
            ],
            [
                'sub_vertical' => 'frozen_products',
                'vertical' => 'supermarket',
                'max_days' => 2,
                'allowed_reasons' => ['spoiled', 'damaged', 'wrong_item'],
                'cold_chain_only_defect' => true,
                'requires_photo' => true,
                'requires_temperature' => true,
                'max_refund_percent' => 100,
                'is_active' => true,
            ],
            [
                'sub_vertical' => 'bakery',
                'vertical' => 'supermarket',
                'max_days' => 1,
                'allowed_reasons' => ['spoiled', 'damaged', 'wrong_item'],
                'cold_chain_only_defect' => false,
                'requires_photo' => true,
                'requires_temperature' => false,
                'max_refund_percent' => 100,
                'is_active' => true,
            ],
            [
                'sub_vertical' => 'vegetables_fruits',
                'vertical' => 'supermarket',
                'max_days' => 2,
                'allowed_reasons' => ['spoiled', 'damaged', 'wrong_item'],
                'cold_chain_only_defect' => false,
                'requires_photo' => true,
                'requires_temperature' => false,
                'max_refund_percent' => 80,
                'is_active' => true,
            ],
            [
                'sub_vertical' => 'seafood',
                'vertical' => 'supermarket',
                'max_days' => 1,
                'allowed_reasons' => ['spoiled', 'damaged', 'wrong_item'],
                'cold_chain_only_defect' => true,
                'requires_photo' => true,
                'requires_temperature' => true,
                'max_refund_percent' => 100,
                'is_active' => true,
            ],
            [
                'sub_vertical' => 'default',
                'vertical' => 'supermarket',
                'max_days' => 3,
                'allowed_reasons' => ['spoiled', 'wrong_item'],
                'cold_chain_only_defect' => true,
                'requires_photo' => true,
                'requires_temperature' => false,
                'max_refund_percent' => 100,
                'is_active' => true,
            ],
        ];

        foreach ($policies as $policy) {
            ReturnPolicy::create($policy);
        }

        $this->command->info('Return policies seeded successfully.');
    }
}
