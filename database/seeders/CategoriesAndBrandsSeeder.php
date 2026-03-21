<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\{Category, Brand, Filter, FilterValue};
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

/**
 * Категории и бренды (НЕ ЗАПУСКАТЬ В PRODUCTION).
 */
final class CategoriesAndBrandsSeeder extends Seeder
{
    public function run(): void
    {
        $verticals = [
            'Hotels' => ['Stay', 'Resort', 'Business'],
            'Beauty' => ['Cosmetics', 'HairCare', 'Skincare'],
            'FoodDelivery' => ['Groceries', 'ReadyMeals', 'Drinks'],
            'Flowers' => ['Bouquets', 'Indoor', 'Seeds'],
            'TaxiAuto' => ['Economy', 'Business', 'Cargo'],
            'HumanClinics' => ['Therapy', 'Surgery', 'Diagnostics'],
            'VetClinics' => ['VetCare', 'AnimalTherapy', 'VetSurgery'],
            'Events' => ['Corporate', 'Wedding', 'Concert'],
            'Sports' => ['Equipment', 'Apparel', 'Nutrition'],
            'Education' => ['Courses', 'Tutors', 'Schools'],
            'AutoService' => ['Repair', 'Maintenance', 'Tuning'],
            'Construction' => ['Materials', 'Tools', 'Planning'],
            'RealEstate' => ['Residential', 'Commercial', 'Land'],
            'Household' => ['Cleaning', 'Hygiene', 'Paper'],
            'KidsToys' => ['Educational', 'Outdoor', 'Robots'],
            'Clothing' => ['Men', 'Women', 'Kids'],
            'Electronics' => ['Mobile', 'Laptops', 'SmartHome'],
        ];

        foreach ($verticals as $v => $subs) {
            foreach ($subs as $sub) {
                Category::create([
                    'name' => $sub, 'vertical' => $v, 'slug' => Str::slug($v.'-'.$sub),
                    'is_active' => true, 'order' => 0
                ]);
            }
        }

        $brands = [
            'L\'Oreal' => 'Beauty', 'Bosch' => 'Construction', 'Toyota' => 'TaxiAuto',
            'Nike' => 'Sports', 'IKEA' => 'RealEstate', 'Apple' => 'Electronics',
            'Samsung' => 'Electronics', 'Dyson' => 'Beauty', 'CAT' => 'Construction',
            'Hilton' => 'Hotels', 'McDonalds' => 'FoodDelivery', 'Pfizer' => 'HumanClinics'
        ];

        foreach ($brands as $name => $v) {
            Brand::create(['name' => $name, 'slug' => Str::slug($name), 'country' => 'Global']);
        }

        $filters = ['Size' => 'select', 'Color' => 'color', 'Power' => 'range', 'Material' => 'select', 'Mileage' => 'range'];
        foreach ($filters as $name => $type) {
            Filter::create(['name' => $name, 'type' => $type, 'vertical' => 'Global']);
        }
    }
}
