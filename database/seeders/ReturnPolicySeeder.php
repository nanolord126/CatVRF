<?php

namespace Database\Seeders;

use App\Domains\Supermarket\Models\ReturnPolicy;
use Illuminate\Database\Seeder;

/**
 * ReturnPolicySeeder - Сидер для создания политик возврата по SubVertical.
 *
 * Создаёт политики для всех под-вертикалей Supermarket:
 * - Мясные лавки (meat_shops) - строгие правила, холодная цепь
 * - Веган-продукты (vegan_products) - умеренные правила
 * - Кондитерка (confectionery) - стандартные правила
 * - Фермерские продукты (farm_direct) - гибкие правила
 * - Бакалея (grocery_and_delivery) - стандартные правила
 * - Товары для дома (household) - длительный срок
 * - Готовая еда (food) - строгие правила
 * - Выпечка (bakery) - умеренные правила
 * - Молочные продукты (dairy) - строгие правила, холодная цепь
 *
 * Также создаёт B2B политики с более длительными сроками.
 */
class ReturnPolicySeeder extends Seeder
{
    public function run(): void
    {
        // Общая политика B2C по умолчанию
        ReturnPolicy::create([
            'vertical' => 'supermarket',
            'sub_vertical' => null,
            'customer_type' => 'b2c',
            'max_days' => 7,
            'allowed_reasons' => ['spoiled', 'wrong_item', 'damaged'],
            'cold_chain_only_defect' => true,
            'requires_photo' => true,
            'requires_temperature' => false,
            'requires_receipt' => true,
            'max_refund_percent' => 100,
            'max_refund_amount' => null,
            'description' => 'Общая политика возврата для B2C клиентов Supermarket',
            'is_active' => true,
            'priority' => 0,
        ]);

        // Общая политика B2B по умолчанию
        ReturnPolicy::create([
            'vertical' => 'supermarket',
            'sub_vertical' => null,
            'customer_type' => 'b2b',
            'max_days' => 14,
            'allowed_reasons' => ['spoiled', 'wrong_item', 'damaged', 'expired'],
            'cold_chain_only_defect' => true,
            'requires_photo' => true,
            'requires_temperature' => true,
            'requires_receipt' => true,
            'max_refund_percent' => 100,
            'max_refund_amount' => 10000000, // 100,000 ₽
            'description' => 'Общая политика возврата для B2B клиентов Supermarket',
            'is_active' => true,
            'priority' => 0,
        ]);

        // Мясные лавки (B2C) - строгие правила
        ReturnPolicy::create([
            'vertical' => 'supermarket',
            'sub_vertical' => 'meat_shops',
            'customer_type' => 'b2c',
            'max_days' => 1,
            'allowed_reasons' => ['spoiled', 'wrong_item', 'damaged'],
            'cold_chain_only_defect' => true,
            'requires_photo' => true,
            'requires_temperature' => true,
            'requires_receipt' => true,
            'max_refund_percent' => 100,
            'max_refund_amount' => null,
            'description' => 'Политика для мясных лавок - возврат только при браке в течение 24 часов',
            'is_active' => true,
            'priority' => 10,
        ]);

        // Мясные лавки (B2B)
        ReturnPolicy::create([
            'vertical' => 'supermarket',
            'sub_vertical' => 'meat_shops',
            'customer_type' => 'b2b',
            'max_days' => 3,
            'allowed_reasons' => ['spoiled', 'wrong_item', 'damaged', 'expired'],
            'cold_chain_only_defect' => true,
            'requires_photo' => true,
            'requires_temperature' => true,
            'requires_receipt' => true,
            'max_refund_percent' => 100,
            'max_refund_amount' => 50000000, // 500,000 ₽
            'description' => 'Политика для мясных лавок B2B',
            'is_active' => true,
            'priority' => 10,
        ]);

        // Веган-продукты (B2C)
        ReturnPolicy::create([
            'vertical' => 'supermarket',
            'sub_vertical' => 'vegan_products',
            'customer_type' => 'b2c',
            'max_days' => 5,
            'allowed_reasons' => ['spoiled', 'wrong_item', 'changed_mind', 'damaged'],
            'cold_chain_only_defect' => false,
            'requires_photo' => false,
            'requires_temperature' => false,
            'requires_receipt' => true,
            'max_refund_percent' => 100,
            'max_refund_amount' => null,
            'description' => 'Политика для веган-продуктов',
            'is_active' => true,
            'priority' => 10,
        ]);

        // Веган-продукты (B2B)
        ReturnPolicy::create([
            'vertical' => 'supermarket',
            'sub_vertical' => 'vegan_products',
            'customer_type' => 'b2b',
            'max_days' => 10,
            'allowed_reasons' => ['spoiled', 'wrong_item', 'changed_mind', 'damaged'],
            'cold_chain_only_defect' => false,
            'requires_photo' => false,
            'requires_temperature' => false,
            'requires_receipt' => true,
            'max_refund_percent' => 100,
            'max_refund_amount' => 30000000, // 300,000 ₽
            'description' => 'Политика для веган-продуктов B2B',
            'is_active' => true,
            'priority' => 10,
        ]);

        // Кондитерка (B2C)
        ReturnPolicy::create([
            'vertical' => 'supermarket',
            'sub_vertical' => 'confectionery',
            'customer_type' => 'b2c',
            'max_days' => 3,
            'allowed_reasons' => ['spoiled', 'wrong_item', 'changed_mind', 'damaged'],
            'cold_chain_only_defect' => false,
            'requires_photo' => true,
            'requires_temperature' => false,
            'requires_receipt' => true,
            'max_refund_percent' => 80,
            'max_refund_amount' => null,
            'description' => 'Политика для кондитерских изделий - 80% возврат при изменении мнения',
            'is_active' => true,
            'priority' => 10,
        ]);

        // Кондитерка (B2B)
        ReturnPolicy::create([
            'vertical' => 'supermarket',
            'sub_vertical' => 'confectionery',
            'customer_type' => 'b2b',
            'max_days' => 7,
            'allowed_reasons' => ['spoiled', 'wrong_item', 'damaged', 'expired'],
            'cold_chain_only_defect' => false,
            'requires_photo' => true,
            'requires_temperature' => false,
            'requires_receipt' => true,
            'max_refund_percent' => 100,
            'max_refund_amount' => 20000000, // 200,000 ₽
            'description' => 'Политика для кондитерских изделий B2B',
            'is_active' => true,
            'priority' => 10,
        ]);

        // Фермерские продукты (B2C)
        ReturnPolicy::create([
            'vertical' => 'supermarket',
            'sub_vertical' => 'farm_direct',
            'customer_type' => 'b2c',
            'max_days' => 5,
            'allowed_reasons' => ['spoiled', 'wrong_item', 'changed_mind', 'damaged'],
            'cold_chain_only_defect' => false,
            'requires_photo' => false,
            'requires_temperature' => false,
            'requires_receipt' => true,
            'max_refund_percent' => 100,
            'max_refund_amount' => null,
            'description' => 'Политика для фермерских продуктов',
            'is_active' => true,
            'priority' => 10,
        ]);

        // Фермерские продукты (B2B)
        ReturnPolicy::create([
            'vertical' => 'supermarket',
            'sub_vertical' => 'farm_direct',
            'customer_type' => 'b2b',
            'max_days' => 14,
            'allowed_reasons' => ['spoiled', 'wrong_item', 'damaged', 'expired'],
            'cold_chain_only_defect' => false,
            'requires_photo' => true,
            'requires_temperature' => true,
            'requires_receipt' => true,
            'max_refund_percent' => 100,
            'max_refund_amount' => 100000000, // 1,000,000 ₽
            'description' => 'Политика для фермерских продуктов B2B',
            'is_active' => true,
            'priority' => 10,
        ]);

        // Бакалея и доставка (B2C)
        ReturnPolicy::create([
            'vertical' => 'supermarket',
            'sub_vertical' => 'grocery_and_delivery',
            'customer_type' => 'b2c',
            'max_days' => 7,
            'allowed_reasons' => ['spoiled', 'wrong_item', 'changed_mind', 'damaged'],
            'cold_chain_only_defect' => false,
            'requires_photo' => false,
            'requires_temperature' => false,
            'requires_receipt' => true,
            'max_refund_percent' => 100,
            'max_refund_amount' => null,
            'description' => 'Политика для бакалеи и доставки',
            'is_active' => true,
            'priority' => 10,
        ]);

        // Бакалея и доставка (B2B)
        ReturnPolicy::create([
            'vertical' => 'supermarket',
            'sub_vertical' => 'grocery_and_delivery',
            'customer_type' => 'b2b',
            'max_days' => 14,
            'allowed_reasons' => ['spoiled', 'wrong_item', 'damaged', 'expired'],
            'cold_chain_only_defect' => false,
            'requires_photo' => false,
            'requires_temperature' => false,
            'requires_receipt' => true,
            'max_refund_percent' => 100,
            'max_refund_amount' => 50000000, // 500,000 ₽
            'description' => 'Политика для бакалеи и доставки B2B',
            'is_active' => true,
            'priority' => 10,
        ]);

        // Товары для дома (B2C)
        ReturnPolicy::create([
            'vertical' => 'supermarket',
            'sub_vertical' => 'household',
            'customer_type' => 'b2c',
            'max_days' => 14,
            'allowed_reasons' => ['spoiled', 'wrong_item', 'changed_mind', 'damaged'],
            'cold_chain_only_defect' => false,
            'requires_photo' => true,
            'requires_temperature' => false,
            'requires_receipt' => true,
            'max_refund_percent' => 100,
            'max_refund_amount' => null,
            'description' => 'Политика для товаров для дома - 14 дней на возврат',
            'is_active' => true,
            'priority' => 10,
        ]);

        // Товары для дома (B2B)
        ReturnPolicy::create([
            'vertical' => 'supermarket',
            'sub_vertical' => 'household',
            'customer_type' => 'b2b',
            'max_days' => 30,
            'allowed_reasons' => ['spoiled', 'wrong_item', 'damaged', 'expired'],
            'cold_chain_only_defect' => false,
            'requires_photo' => true,
            'requires_temperature' => false,
            'requires_receipt' => true,
            'max_refund_percent' => 100,
            'max_refund_amount' => 200000000, // 2,000,000 ₽
            'description' => 'Политика для товаров для дома B2B',
            'is_active' => true,
            'priority' => 10,
        ]);

        // Готовая еда (B2C)
        ReturnPolicy::create([
            'vertical' => 'supermarket',
            'sub_vertical' => 'food',
            'customer_type' => 'b2c',
            'max_days' => 1,
            'allowed_reasons' => ['spoiled', 'wrong_item', 'damaged'],
            'cold_chain_only_defect' => true,
            'requires_photo' => true,
            'requires_temperature' => true,
            'requires_receipt' => true,
            'max_refund_percent' => 100,
            'max_refund_amount' => null,
            'description' => 'Политика для готовой еды - строгие правила, холодная цепь',
            'is_active' => true,
            'priority' => 10,
        ]);

        // Готовая еда (B2B)
        ReturnPolicy::create([
            'vertical' => 'supermarket',
            'sub_vertical' => 'food',
            'customer_type' => 'b2b',
            'max_days' => 2,
            'allowed_reasons' => ['spoiled', 'wrong_item', 'damaged', 'expired'],
            'cold_chain_only_defect' => true,
            'requires_photo' => true,
            'requires_temperature' => true,
            'requires_receipt' => true,
            'max_refund_percent' => 100,
            'max_refund_amount' => 100000000, // 1,000,000 ₽
            'description' => 'Политика для готовой еды B2B',
            'is_active' => true,
            'priority' => 10,
        ]);

        // Выпечка (B2C)
        ReturnPolicy::create([
            'vertical' => 'supermarket',
            'sub_vertical' => 'bakery',
            'customer_type' => 'b2c',
            'max_days' => 2,
            'allowed_reasons' => ['spoiled', 'wrong_item', 'damaged'],
            'cold_chain_only_defect' => false,
            'requires_photo' => true,
            'requires_temperature' => false,
            'requires_receipt' => true,
            'max_refund_percent' => 100,
            'max_refund_amount' => null,
            'description' => 'Политика для выпечки',
            'is_active' => true,
            'priority' => 10,
        ]);

        // Выпечка (B2B)
        ReturnPolicy::create([
            'vertical' => 'supermarket',
            'sub_vertical' => 'bakery',
            'customer_type' => 'b2b',
            'max_days' => 5,
            'allowed_reasons' => ['spoiled', 'wrong_item', 'damaged', 'expired'],
            'cold_chain_only_defect' => false,
            'requires_photo' => true,
            'requires_temperature' => false,
            'requires_receipt' => true,
            'max_refund_percent' => 100,
            'max_refund_amount' => 50000000, // 500,000 ₽
            'description' => 'Политика для выпечки B2B',
            'is_active' => true,
            'priority' => 10,
        ]);

        // Молочные продукты (B2C)
        ReturnPolicy::create([
            'vertical' => 'supermarket',
            'sub_vertical' => 'dairy',
            'customer_type' => 'b2c',
            'max_days' => 2,
            'allowed_reasons' => ['spoiled', 'wrong_item', 'damaged', 'expired'],
            'cold_chain_only_defect' => true,
            'requires_photo' => true,
            'requires_temperature' => true,
            'requires_receipt' => true,
            'max_refund_percent' => 100,
            'max_refund_amount' => null,
            'description' => 'Политика для молочных продуктов - строгие правила, холодная цепь',
            'is_active' => true,
            'priority' => 10,
        ]);

        // Молочные продукты (B2B)
        ReturnPolicy::create([
            'vertical' => 'supermarket',
            'sub_vertical' => 'dairy',
            'customer_type' => 'b2b',
            'max_days' => 5,
            'allowed_reasons' => ['spoiled', 'wrong_item', 'damaged', 'expired'],
            'cold_chain_only_defect' => true,
            'requires_photo' => true,
            'requires_temperature' => true,
            'requires_receipt' => true,
            'max_refund_percent' => 100,
            'max_refund_amount' => 100000000, // 1,000,000 ₽
            'description' => 'Политика для молочных продуктов B2B',
            'is_active' => true,
            'priority' => 10,
        ]);
    }
}
