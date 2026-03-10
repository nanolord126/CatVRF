<?php

namespace Database\Seeders;

use Database\Seeders\VerticalFilterSeederBase;

class AutoFilterSeeder extends VerticalFilterSeederBase
{
    public function run(): void
    {
        $this->createFilters('AutoService', [
            'Тип двигателя' => ['type' => 'select', 'values' => [
                ['value' => 'electric-full', 'label' => 'Электрический (EV)'],
                ['value' => 'hydrogen-fcev', 'label' => 'Водородный (FCEV)'],
                ['value' => 'hybrid-phev', 'label' => 'Гибрид (PHEV)'],
                ['value' => 'gasoline-turbo', 'label' => 'Бензиновый турбированный'],
                ['value' => 'diesel-clean', 'label' => 'Дизельный (Clean Diesel)'],
            ]],
            'Запас хода (WLTP)' => ['type' => 'range', 'unit' => 'км'],
            'Крутящий момент' => ['type' => 'range', 'unit' => 'Нм'],
            'Привод' => ['type' => 'select', 'values' => [
                ['value' => 'awd-electric-independent', 'label' => 'Полный (AWD Independent)'],
                ['value' => 'rwd-rear-wheel-drive', 'label' => 'Задний (RWD)'],
                ['value' => 'fwd-front-wheel-drive', 'label' => 'Передний (FWD)'],
                ['value' => '4wd-mechanical-locking', 'label' => 'Полный механический (4WD)'],
            ]],
            'Тип КПП' => ['type' => 'select', 'values' => [
                ['value' => 'single-speed-ev', 'label' => 'Одноступенчатая (EV)'],
                ['value' => 'dual-clutch-9spd', 'label' => 'Робот (9-ступенчатый DCT)'],
                ['value' => 'planetary-gear-set', 'label' => 'Планетарная (Гибрид)'],
                ['value' => 'automatic-torque-converter', 'label' => 'Автомат (Гидротрансформатор)'],
            ]],
            'Клиренс' => ['type' => 'range', 'unit' => 'мм'],
            'Уровень автономного вождения' => ['type' => 'select', 'values' => [
                ['value' => 'level-4-autonomous', 'label' => 'Уровень 4 (Полный автопилот)'],
                ['value' => 'level-3-conditional', 'label' => 'Уровень 3 (Условный автопилот)'],
                ['value' => 'level-2-plus-advanced-adas', 'label' => 'Уровень 2+ (Advanced ADAS)'],
                ['value' => 'level-2-proactive', 'label' => 'Уровень 2 (Профессиональный круиз)'],
            ]],
            'Система ассистентов' => ['type' => 'multi-select', 'values' => [
                ['value' => 'lidar-vision-system', 'label' => 'Лидар + Камеры 360'],
                ['value' => 'lane-keep-assist', 'label' => 'Удержание в полосе'],
                ['value' => 'auto-parking-valet', 'label' => 'Автопарковка (Valet Mode)'],
                ['value' => 'night-vision-thermal', 'label' => 'Ночное видение (Тепловизор)'],
            ]],
            'Время разгона 0-100' => ['type' => 'range', 'unit' => 'сек'],
            'Объем багажника' => ['type' => 'range', 'unit' => 'л'],
            'Экологический класс' => ['type' => 'select', 'values' => [
                ['value' => 'zero-emission', 'label' => 'Zero Emission'],
                ['value' => 'euro-7', 'label' => 'Euro 7'],
                ['value' => 'euro-6-temp-evap', 'label' => 'Euro 6d-temp'],
            ]],
        ]);
    }
}


