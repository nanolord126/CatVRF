<?php

declare(strict_types=1);

namespace Database\Seeders;

use Database\Seeders\VerticalFilterSeederBase;

/**
 * Общие фильтры маркетплейса (НЕ ЗАПУСКАТЬ В PRODUCTION).
 */
final class MarketplaceGeneralFilterSeeder extends VerticalFilterSeederBase
{
    public function run(): void
    {
        $this->createFilters('HouseholdChemicals', [
            'Тип средства' => ['type' => 'select', 'values' => [
                ['value' => 'concentrate-gel', 'label' => 'Концентрат (Гель)'],
                ['value' => 'foam-active', 'label' => 'Активная пена'],
                ['value' => 'eco-tabs', 'label' => 'Эко-таблетки'],
                ['value' => 'spray-disinfectant', 'label' => 'Спрей-антисептик'],
            ]],
            'Бережность к материалам' => ['type' => 'multi-select', 'values' => [
                ['value' => 'wood-safe', 'label' => 'Для дерева'],
                ['value' => 'stone-marble-safe', 'label' => 'Для камня/мрамора'],
                ['value' => 'delicate-fabrics', 'label' => 'Для деликатных тканей'],
                ['value' => 'stainless-steel', 'label' => 'Для нержавеющей стали'],
            ]],
            'Объем' => ['type' => 'range', 'unit' => 'л'],
            'Биоразлагаемость' => ['type' => 'boolean'],
        ]);

        $this->createFilters('Clothing', [
            'Материал' => ['type' => 'multi-select', 'values' => [
                ['value' => 'organic-cotton', 'label' => 'Органический хлопок'],
                ['value' => 'recycled-polyester', 'label' => 'Вторичный полиэстер'],
                ['value' => 'merino-wool', 'label' => 'Шерсть мериноса'],
                ['value' => 'bamboo-fiber', 'label' => 'Бамбуковое волокно'],
                ['value' => 'graphene-layer', 'label' => 'Графеновый слой'],
            ]],
            'Технология терморегуляции' => ['type' => 'select', 'values' => [
                ['value' => 'active-cool-2026', 'label' => 'ActiveCool 2026'],
                ['value' => 'heat-tech-ultra', 'label' => 'HeatTech Ultra'],
                ['value' => 'gore-tex-pro', 'label' => 'Gore-Tex Pro'],
            ]],
            'Размер (EU)' => ['type' => 'select', 'values' => [
                ['value' => 'xxs', 'label' => 'XXS'], ['value' => 'xs', 'label' => 'XS'],
                ['value' => 's', 'label' => 'S'], ['value' => 'm', 'label' => 'M'],
                ['value' => 'l', 'label' => 'L'], ['value' => 'xl', 'label' => 'XL'],
                ['value' => 'xxl', 'label' => 'XXL'],
            ]],
            'Сезон' => ['type' => 'multi-select', 'values' => [
                ['value' => 'winter', 'label' => 'Зима'], ['value' => 'spring', 'label' => 'Весна'],
                ['value' => 'summer', 'label' => 'Лето'], ['value' => 'autumn', 'label' => 'Осень'],
            ]],
        ]);
        
        $this->createFilters('Shoes', [
            'Материал подошвы' => ['type' => 'select', 'values' => [
                ['value' => 'vibram-advanced', 'label' => 'Vibram Advanced'],
                ['value' => 'eva-recycled', 'label' => 'EVA Recycled'],
                ['value' => 'carbon-plate-integrated', 'label' => 'Карбоновая пластина'],
            ]],
            'Поддержка стопы' => ['type' => 'boolean'],
            'Водонепроницаемость' => ['type' => 'boolean'],
        ]);
    }
}


