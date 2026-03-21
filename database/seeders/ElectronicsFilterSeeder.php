<?php
declare(strict_types=1);

namespace Database\Seeders;

use Database\Seeders\VerticalFilterSeederBase;

/**
 * Фильтры для электроники (НЕ ЗАПУСКАТЬ В PRODUCTION).
 */
final class ElectronicsFilterSeeder extends VerticalFilterSeederBase
{
    public function run(): void
    {
        $this->createFilters('Electronics', [
            'Тип матрицы' => ['type' => 'select', 'values' => [
                ['value' => 'oled', 'label' => 'OLED'],
                ['value' => 'amoled', 'label' => 'AMOLED'],
                ['value' => 'ips', 'label' => 'IPS'],
                ['value' => 'va', 'label' => 'VA'],
                ['value' => 'micro-led', 'label' => 'MicroLED'],
            ]],
            'Объем оперативной памяти' => ['type' => 'range', 'unit' => 'ГБ'],
            'Объем встроенной памяти' => ['type' => 'range', 'unit' => 'ГБ'],
            'Процессор' => ['type' => 'select', 'values' => [
                ['value' => 'snapdragon-8-gen-5', 'label' => 'Snapdragon 8 Gen 5'],
                ['value' => 'apple-a20', 'label' => 'Apple A20 Bionic'],
                ['value' => 'dimensity-9400', 'label' => 'Dimensity 9400'],
                ['value' => 'exynos-2600', 'label' => 'Exynos 2600'],
            ]],
            'Частота обновления экрана' => ['type' => 'range', 'unit' => 'Гц'],
            'Емкость аккумулятора' => ['type' => 'range', 'unit' => 'мАч'],
            'Мощность зарядки' => ['type' => 'range', 'unit' => 'Вт'],
            'Количество ядер' => ['type' => 'range'],
            'Поддержка 6G' => ['type' => 'boolean'],
            'Степень защиты' => ['type' => 'select', 'values' => [
                ['value' => 'ip68', 'label' => 'IP68'],
                ['value' => 'ip69k', 'label' => 'IP69K'],
                ['value' => 'mil-std-810h', 'label' => 'MIL-STD-810H'],
            ]],
            'Материал корпуса' => ['type' => 'select', 'values' => [
                ['value' => 'titanium', 'label' => 'Титан'],
                ['value' => 'ceramics', 'label' => 'Керамика'],
                ['value' => 'glass', 'label' => 'Стекло'],
                ['value' => 'recycled-aluminum', 'label' => 'Вторичный алюминий'],
            ]],
            'Тип порта' => ['type' => 'multi-select', 'values' => [
                ['value' => 'usb-c-4', 'label' => 'USB-C 4.0'],
                ['value' => 'thunderbolt-5', 'label' => 'Thunderbolt 5'],
                ['value' => 'magsafe-4', 'label' => 'MagSafe 4'],
            ]],
        ]);
    }
}


