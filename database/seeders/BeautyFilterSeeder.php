<?php

namespace Database\Seeders;

use Database\Seeders\VerticalFilterSeederBase;

class BeautyFilterSeeder extends VerticalFilterSeederBase
{
    public function run(): void
    {
        $this->createFilters('Beauty', [
            'Тип процедуры' => ['type' => 'select', 'values' => [
                ['value' => 'medical-cosmetology', 'label' => 'Врачебная косметология'],
                ['value' => 'esthetic-cosmetology', 'label' => 'Эстетическая косметология'],
                ['value' => 'laser-therapy', 'label' => 'Лазерная терапия'],
                ['value' => 'injection-plastic', 'label' => 'Инъекционная пластика'],
                ['value' => 'massage-lymph', 'label' => 'Лимфодренажный массаж'],
            ]],
            'Тип кожи' => ['type' => 'multi-select', 'values' => [
                ['value' => 'oily-acne', 'label' => 'Жирная/проблемная'],
                ['value' => 'dry-sensitive', 'label' => 'Сухая/чувствительная'],
                ['value' => 'combination', 'label' => 'Комбинированная'],
                ['value' => 'normal', 'label' => 'Нормальная'],
                ['value' => 'age-related-changes', 'label' => 'Возрастные изменения'],
            ]],
            'Зона коррекции' => ['type' => 'multi-select', 'values' => [
                ['value' => 'face-full', 'label' => 'Лицо полностью'],
                ['value' => 'periocular-zone', 'label' => 'Зона вокруг глаз'],
                ['value' => 'neck-decollete', 'label' => 'Шея и декольте'],
                ['value' => 'body-contour', 'label' => 'Контуринг тела'],
                ['value' => 'hair-scalp', 'label' => 'Кожа головы'],
            ]],
            'Действующее вещество' => ['type' => 'select', 'values' => [
                ['value' => 'hyaluronic-acid', 'label' => 'Гиалуроновая кислота'],
                ['value' => 'collagen-boost', 'label' => 'Коллагеновые стимуляторы'],
                ['value' => 'peptide-complex', 'label' => 'Пептидный комплекс'],
                ['value' => 'retinol-ai-encapsulated', 'label' => 'AI-инкапсулированный ретинол'],
                ['value' => 'vitamin-c-stabilized', 'label' => 'Стабилизированный Витамин С'],
            ]],
            'Концентрация вещества' => ['type' => 'range', 'unit' => '%'],
            'Уровень квалификации мастера' => ['type' => 'select', 'values' => [
                ['value' => 'top-expert-doctor', 'label' => 'Топ-эксперт (Врач)'],
                ['value' => 'senior-specialist', 'label' => 'Старший специалист'],
                ['value' => 'practitioner', 'label' => 'Практикующий специалист'],
                ['value' => 'junior-trainee', 'label' => 'Младший специалист (стажер)'],
            ]],
            'Длительность эффекта' => ['type' => 'range', 'unit' => 'мес'],
            'Аппаратная технология' => ['type' => 'select', 'values' => [
                ['value' => 'rf-lifting-needle', 'label' => 'Микроигольчатый RF'],
                ['value' => 'hifu-ultrasound', 'label' => 'HIFU (Ультразвуковой лифтинг)'],
                ['value' => 'picosecond-laser', 'label' => 'Пикосекундный лазер'],
                ['value' => 'smas-lifting', 'label' => 'SMAS-лифтинг'],
            ]],
            'Гипоаллергенность' => ['type' => 'boolean'],
            'Веган-косметика/Cruelty-Free' => ['type' => 'boolean'],
            'Экологичность упаковки' => ['type' => 'boolean'],
        ]);
    }
}


