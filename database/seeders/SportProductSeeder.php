<?php
declare(strict_types=1);

namespace Database\Seeders;

use App\Models\Tenants\SportProduct;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

/**
 * Спортивные товары (НЕ ЗАПУСКАТЬ В PRODUCTION).
 */
final class SportProductSeeder extends Seeder
{
    public function run(): void
    {
        SportProduct::factory()
            ->count(5)
            ->create(['correlation_id' => (string) Str::uuid(), 'tags' => ['source:seeder']]);
    }
}             [                 'name' => 'Профессиональные кроссовки для бега Nike Air',                 'description' => 'Легкие и удобные кроссовки для марафонов и длительных пробежек',                 'category' => 'footwear',                 'brand' => 'Nike',                 'price' => 12999.99,                 'quantity_in_stock' => 45,                 'unit_of_measure' => 'pcs',                 'manufacturer' => 'Nike Inc.',                 'sport_type' => 'running',                 'size' => '42-45',                 'color' => 'Black/White',                 'material' => 'Mesh/Synthetic',                 'status' => 'published',             ],             [                 'name' => 'Велосипедный шлем Specialized',                 'description' => 'Безопасный и легкий шлем для профессиональных велогонок',                 'category' => 'safety',                 'brand' => 'Specialized',                 'price' => 8500.00,                 'quantity_in_stock' => 30,                 'unit_of_measure' => 'pcs',                 'manufacturer' => 'Specialized Sports',                 'sport_type' => 'cycling',                 'size' => 'M-L',                 'color' => 'Red',                 'material' => 'Carbon Fiber',                 'status' => 'published',             ],             [                 'name' => 'Гимнастический коврик 6мм',                 'description' => 'Толстый и удобный коврик для занятий йогой и растяжкой',                 'category' => 'equipment',                 'brand' => 'Fitness Pro',                 'price' => 3500.00,                 'quantity_in_stock' => 60,                 'unit_of_measure' => 'pcs',                 'manufacturer' => 'Fitness Pro Ltd.',                 'sport_type' => 'gym',                 'size' => '183x61',                 'color' => 'Purple',                 'material' => 'TPE',                 'status' => 'published',             ],             [                 'name' => 'Гантели регулируемые 1-20кг',                 'description' => 'Универсальные гантели для домашних тренировок',                 'category' => 'equipment',                 'brand' => 'PowerTech',                 'price' => 25000.00,                 'quantity_in_stock' => 25,                 'unit_of_measure' => 'pcs',                 'manufacturer' => 'PowerTech Systems',                 'sport_type' => 'gym',                 'size' => '20kg',                 'color' => 'Black',                 'material' => 'Iron/Rubber',                 'status' => 'published',             ],             [                 'name' => 'Теннисная ракетка Wilson Pro',                 'description' => 'Профессиональная ракетка для турниров',                 'category' => 'rackets',                 'brand' => 'Wilson',                 'price' => 15000.00,                 'quantity_in_stock' => 20,                 'unit_of_measure' => 'pcs',                 'manufacturer' => 'Wilson Sporting Goods',                 'sport_type' => 'tennis',                 'size' => 'L4',                 'color' => 'Black/Red',                 'material' => 'Carbon Composite',                 'status' => 'published',             ],         ];          foreach ($products as $product) {             SportProduct::create($product);         }     } }
