<?php

declare(strict_types=1);

namespace Database\Seeders;

use Database\Seeders\VerticalFilterSeederBase;

/**
 * Фильтры вертикали недвижимости (НЕ ЗАПУСКАТЬ В PRODUCTION).
 */
final class RealEstateFilterSeeder extends VerticalFilterSeederBase
{
    public function run(): void
    {
        $this->createFilters('RealEstate', [
            'Материал стен' => ['type' => 'select', 'values' => [
                ['value' => 'brick', 'label' => 'Кирпич'],
                ['value' => 'monolith', 'label' => 'Монолит'],
                ['value' => 'panel', 'label' => 'Панель'],
                ['value' => 'aerated-concrete', 'label' => 'Газобетон'],
                ['value' => 'timber-frame', 'label' => 'Каркас'],
            ]],
            'Тип отопления' => ['type' => 'select', 'values' => [
                ['value' => 'central', 'label' => 'Центральное'],
                ['value' => 'autonomous-gas', 'label' => 'Автономное газовое'],
                ['value' => 'electric-geothermal', 'label' => 'Геотермальное электрическое'],
                ['value' => 'heat-pump', 'label' => 'Тепловой насос (Air/Water)'],
            ]],
            'Высота потолков' => ['type' => 'range', 'unit' => 'м'],
            'Умный дом (Smart Home)' => ['type' => 'select', 'values' => [
                ['value' => 'full-ai-integrated', 'label' => 'Полная интеграция AI'],
                ['value' => 'partial-automation', 'label' => 'Частичная автоматизация'],
                ['value' => 'basic-sensors', 'label' => 'Базовые датчики'],
            ]],
            'Расстояние до метро' => ['type' => 'range', 'unit' => 'мин пешком'],
            'Класс энергоэффективности' => ['type' => 'select', 'values' => [
                ['value' => 'a-plus-plus', 'label' => 'A++'],
                ['value' => 'a-plus', 'label' => 'A+'],
                ['value' => 'a', 'label' => 'A'],
                ['value' => 'b', 'label' => 'B'],
            ]],
            'Вид из окна' => ['type' => 'multi-select', 'values' => [
                ['value' => 'city-skyline', 'label' => 'Панорама города'],
                ['value' => 'river-lake', 'label' => 'Водоем'],
                ['value' => 'park-forest', 'label' => 'Парк/Лес'],
                ['value' => 'courtyard', 'label' => 'Двор'],
            ]],
            'Тип лифта' => ['type' => 'select', 'values' => [
                ['value' => 'silent-express', 'label' => 'Бесшумный скоростной'],
                ['value' => 'cargo-passenger', 'label' => 'Грузопассажирский'],
                ['value' => 'smart-call', 'label' => 'Лифт с вызовом через приложение'],
            ]],
            'Тип парковки' => ['type' => 'multi-select', 'values' => [
                ['value' => 'underground-heated', 'label' => 'Подземный теплый'],
                ['value' => 'ground-multi-level', 'label' => 'Наземный многоуровневый'],
                ['value' => 'surface-dedicated', 'label' => 'Наземный выделенный'],
                ['value' => 'ev-integrated', 'label' => 'С зарядкой для электромобилей'],
            ]],
            'Наличие террасы' => ['type' => 'boolean'],
            'Консьерж-сервис 24/7' => ['type' => 'boolean'],
        ]);
    }
}


