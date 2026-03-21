<?php

declare(strict_types=1);

namespace Database\Seeders;

/**
 * Бренды розницы (НЕ ЗАПУСКАТЬ В PRODUCTION).
 */
final class RetailBrands extends BaseBrandSeeder
{
    public function run(): void {
        $this->seedBrands('Clothing', [
            ['name' => 'Zara', 'country' => 'Spain'], ['name' => 'H&M', 'country' => 'Sweden'],
            ['name' => 'Uniqlo', 'country' => 'Japan'], ['name' => 'Gap', 'country' => 'USA'],
            ['name' => 'Levi\'s', 'country' => 'USA'], ['name' => 'Adidas Originals', 'country' => 'Germany'],
            ['name' => 'Nike Sportswear', 'country' => 'USA'], ['name' => 'Polo Ralph Lauren', 'country' => 'USA'],
            ['name' => 'Tommy Hilfiger', 'country' => 'USA'], ['name' => 'Calvin Klein', 'country' => 'USA'],
            ['name' => 'Gucci', 'country' => 'Italy'], ['name' => 'Louis Vuitton', 'country' => 'France'],
            ['name' => 'Prada', 'country' => 'Italy'], ['name' => 'Burberry', 'country' => 'UK'],
            ['name' => 'Hermès', 'country' => 'France'], ['name' => 'Balenciaga', 'country' => 'France'],
            ['name' => 'Saint Laurent', 'country' => 'France'], ['name' => 'Valentino', 'country' => 'Italy'],
            ['name' => 'Versace', 'country' => 'Italy'], ['name' => 'Fendi', 'country' => 'Italy'],
            ['name' => 'Armani', 'country' => 'Italy'], ['name' => 'Dolce & Gabbana', 'country' => 'Italy'],
            ['name' => 'Givenchy', 'country' => 'France'], ['name' => 'Dior', 'country' => 'France'],
            ['name' => 'Moncler', 'country' => 'Italy'], ['name' => 'Canada Goose', 'country' => 'Canada'],
            ['name' => 'Stone Island', 'country' => 'Italy'], ['name' => 'Supreme', 'country' => 'USA'],
            ['name' => 'Off-White', 'country' => 'Italy'], ['name' => 'Balmain', 'country' => 'France'],
            ['name' => 'Alexander McQueen', 'country' => 'UK'], ['name' => 'Stella McCartney', 'country' => 'UK'],
            ['name' => 'Victoria\'s Secret', 'country' => 'USA'], ['name' => 'Lululemon Athletica', 'country' => 'Canada'],
            ['name' => 'Mango', 'country' => 'Spain'], ['name' => 'Massimo Dutti', 'country' => 'Spain'],
            ['name' => 'Bershka', 'country' => 'Spain'], ['name' => 'Pull&Bear', 'country' => 'Spain'],
            ['name' => 'Stradivarius', 'country' => 'Spain'], ['name' => 'Primark', 'country' => 'Ireland'],
            ['name' => 'C&A', 'country' => 'Netherlands/Germany'], ['name' => 'Reserved', 'country' => 'Poland'],
            ['name' => 'Gloria Jeans', 'country' => 'Russia'], ['name' => 'O\'Stin', 'country' => 'Russia'],
            ['name' => 'Henderson', 'country' => 'Russia'], ['name' => 'Love Republic', 'country' => 'Russia'],
            ['name' => 'Befree', 'country' => 'Russia'], ['name' => 'Zarina', 'country' => 'Russia'],
            ['name' => 'Finn Flare', 'country' => 'Russia'],
            ['name' => 'Kot-Wear (Partner)', 'country' => 'Russia', 'is_platform_partner' => true],
        ]);
        
        $this->seedBrands('Household', [
            ['name' => 'P&G', 'country' => 'USA'], ['name' => 'Unilever Home', 'country' => 'UK/Netherlands'],
            ['name' => 'Henkel', 'country' => 'Germany'], ['name' => 'Reckitt', 'country' => 'UK'],
            ['name' => 'S. C. Johnson', 'country' => 'USA'], ['name' => 'Colgate-Palmolive', 'country' => 'USA'],
            ['name' => 'Church & Dwight', 'country' => 'USA'], ['name' => 'Clorox', 'country' => 'USA'],
            ['name' => 'Kao Corporation', 'country' => 'Japan'], ['name' => 'Lion Corporation', 'country' => 'Japan'],
            ['name' => 'Ariel', 'country' => 'USA'], ['name' => 'Tide', 'country' => 'USA'],
            ['name' => 'Persil', 'country' => 'Germany'], ['name' => 'Finish', 'country' => 'UK'],
            ['name' => 'Fairy', 'country' => 'USA'], ['name' => 'Domestos', 'country' => 'UK/Netherlands'],
            ['name' => 'Cif', 'country' => 'UK/Netherlands'], ['name' => 'Lysol', 'country' => 'UK'],
            ['name' => 'Dettol', 'country' => 'UK'], ['name' => 'Pampers', 'country' => 'USA'],
            ['name' => 'Huggies', 'country' => 'USA'], ['name' => 'Libero', 'country' => 'Sweden'],
            ['name' => 'Always', 'country' => 'USA'], ['name' => 'Kotex', 'country' => 'USA'],
            ['name' => 'Zewa', 'country' => 'Sweden'], ['name' => 'Kleenex', 'country' => 'USA'],
            ['name' => 'Scott', 'country' => 'USA'], ['name' => 'Vanish', 'country' => 'UK'],
            ['name' => 'Air Wick', 'country' => 'UK'], ['name' => 'Glade', 'country' => 'USA'],
            ['name' => 'Ambi Pur', 'country' => 'USA'], ['name' => 'Mr. Proper', 'country' => 'USA'],
            ['name' => 'Bref', 'country' => 'Germany'], ['name' => 'Somat', 'country' => 'Germany'],
            ['name' => 'Silit Bang', 'country' => 'UK'], ['name' => 'Calgon', 'country' => 'UK'],
            ['name' => 'Woolite', 'country' => 'UK'], ['name' => 'Lenor', 'country' => 'USA'],
            ['name' => 'Downy', 'country' => 'USA'], ['name' => 'Comfort', 'country' => 'UK/Netherlands'],
            ['name' => 'Aquarelle', 'country' => 'Global'], ['name' => 'Papia', 'country' => 'Turkey'],
            ['name' => 'Familia', 'country' => 'Turkey'], ['name' => 'Syal', 'country' => 'Russia'],
            ['name' => 'Sinergetic', 'country' => 'Russia'], ['name' => 'Grass', 'country' => 'Russia'],
            ['name' => 'Natura Siberica', 'country' => 'Russia'], ['name' => 'Splat', 'country' => 'Russia'],
            ['name' => 'R.O.C.S.', 'country' => 'Russia'],
            ['name' => 'Kot-Clean (Partner)', 'country' => 'Russia', 'is_platform_partner' => true],
        ]);
    }
}


