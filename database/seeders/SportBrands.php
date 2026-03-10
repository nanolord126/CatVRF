<?php
namespace Database\Seeders;
class SportBrands extends BaseBrandSeeder {
    public function run(): void {
        $this->seedBrands('Sports', [
            ['name' => 'Nike', 'country' => 'USA'], ['name' => 'Adidas', 'country' => 'Germany'],
            ['name' => 'Puma', 'country' => 'Germany'], ['name' => 'Under Armour', 'country' => 'USA'],
            ['name' => 'Reebok', 'country' => 'USA'], ['name' => 'New Balance', 'country' => 'USA'],
            ['name' => 'Asics', 'country' => 'Japan'], ['name' => 'Mizuno', 'country' => 'Japan'],
            ['name' => 'Yonex', 'country' => 'Japan'], ['name' => 'Decathlon', 'country' => 'France'],
            ['name' => 'Columbia', 'country' => 'USA'], ['name' => 'The North Face', 'country' => 'USA'],
            ['name' => 'Patagonia', 'country' => 'USA'], ['name' => 'Lululemon', 'country' => 'Canada'],
            ['name' => 'Wilson', 'country' => 'USA'], ['name' => 'Rawlings', 'country' => 'USA'],
            ['name' => 'Spalding', 'country' => 'USA'], ['name' => 'Everlast', 'country' => 'USA'],
            ['name' => 'Titleist', 'country' => 'USA'], ['name' => 'Callaway', 'country' => 'USA'],
            ['name' => 'TaylorMade', 'country' => 'USA'], ['name' => 'Speedo', 'country' => 'UK'],
            ['name' => 'Arena', 'country' => 'Italy'], ['name' => 'Babolat', 'country' => 'France'],
            ['name' => 'Head', 'country' => 'Austria'], ['name' => 'Salomon', 'country' => 'France'],
            ['name' => 'Atomic', 'country' => 'Austria'], ['name' => 'Burton', 'country' => 'USA'],
            ['name' => 'Specialized', 'country' => 'USA'], ['name' => 'Trek Bicycles', 'country' => 'USA'],
            ['name' => 'Giant Bicycles', 'country' => 'Taiwan'], ['name' => 'Shimano', 'country' => 'Japan'],
            ['name' => 'Oakley', 'country' => 'USA'], ['name' => 'Garmin Sports', 'country' => 'USA'],
            ['name' => 'Polar', 'country' => 'Finland'], ['name' => 'Suunto', 'country' => 'Finland'],
            ['name' => 'Technogym', 'country' => 'Italy'], ['name' => 'Life Fitness', 'country' => 'USA'],
            ['name' => 'Peloton', 'country' => 'USA'], ['name' => 'Bowflex', 'country' => 'USA'],
            ['name' => 'Hammer Strength', 'country' => 'USA'], ['name' => 'Eleiko', 'country' => 'Sweden'],
            ['name' => 'Rogue Fitness', 'country' => 'USA'], ['name' => 'Fila', 'country' => 'South Korea'],
            ['name' => 'Kappa', 'country' => 'Italy'], ['name' => 'Umbro', 'country' => 'UK'],
            ['name' => 'Converse', 'country' => 'USA'], ['name' => 'Vans', 'country' => 'USA'],
            ['name' => 'Skechers', 'country' => 'USA'],
            ['name' => 'Kot-Sport (Partner)', 'country' => 'Russia', 'is_platform_partner' => true],
        ]);
    }
}


