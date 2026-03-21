<?php
declare(strict_types=1);

namespace Database\Seeders;

/**
 * Гостиничные бренды (НЕ ЗАПУСКАТЬ В PRODUCTION).
 */
final class HotelBrands extends BaseBrandSeeder {
    public function run(): void {
        $this->seedBrands('Hotels', [
            ['name' => 'Marriott', 'country' => 'USA'], ['name' => 'Hilton', 'country' => 'USA'],
            ['name' => 'Hyatt', 'country' => 'USA'], ['name' => 'IHG', 'country' => 'UK'],
            ['name' => 'Accor', 'country' => 'France'], ['name' => 'Wyndham', 'country' => 'USA'],
            ['name' => 'Choice Hotels', 'country' => 'USA'], ['name' => 'Best Western', 'country' => 'USA'],
            ['name' => 'Radisson', 'country' => 'USA/Belgium'], ['name' => 'Four Seasons', 'country' => 'Canada'],
            ['name' => 'Ritz-Carlton', 'country' => 'USA'], ['name' => 'W Hotels', 'country' => 'USA'],
            ['name' => 'Sheraton', 'country' => 'USA'], ['name' => 'Westin', 'country' => 'USA'],
            ['name' => 'InterContinental', 'country' => 'UK'], ['name' => 'Holiday Inn', 'country' => 'UK'],
            ['name' => 'Crowne Plaza', 'country' => 'UK'], ['name' => 'Ibis', 'country' => 'France'],
            ['name' => 'Novotel', 'country' => 'France'], ['name' => 'Pullman', 'country' => 'France'],
            ['name' => 'Mercure', 'country' => 'France'], ['name' => 'Fairmont', 'country' => 'Canada'],
            ['name' => 'Raffles', 'country' => 'Singapore'], ['name' => 'Shangri-La', 'country' => 'Hong Kong'],
            ['name' => 'Mandarin Oriental', 'country' => 'Hong Kong'], ['name' => 'Peninsula Hotels', 'country' => 'Hong Kong'],
            ['name' => 'Rosewood', 'country' => 'USA/Hong Kong'], ['name' => 'Aman Resorts', 'country' => 'Switzerland'],
            ['name' => 'Six Senses', 'country' => 'Thailand'], ['name' => 'Banyan Tree', 'country' => 'Singapore'],
            ['name' => 'Aloft Hotels', 'country' => 'USA'], ['name' => 'Moxy Hotels', 'country' => 'USA'],
            ['name' => 'DoubleTree', 'country' => 'USA'], ['name' => 'Hampton Inn', 'country' => 'USA'],
            ['name' => 'Courtyard by Marriott', 'country' => 'USA'], ['name' => 'Residence Inn', 'country' => 'USA'],
            ['name' => 'Premier Inn', 'country' => 'UK'], ['name' => 'Travelodge', 'country' => 'UK'],
            ['name' => 'Jin Jiang', 'country' => 'China'], ['name' => 'Huazhu Group', 'country' => 'China'],
            ['name' => 'BTG Homeinns', 'country' => 'China'], ['name' => 'Minor Hotels', 'country' => 'Thailand'],
            ['name' => 'Oberoi Hotels', 'country' => 'India'], ['name' => 'Taj Hotels', 'country' => 'India'],
            ['name' => 'Kerzner International', 'country' => 'UAE'], ['name' => 'Jumeirah', 'country' => 'UAE'],
            ['name' => 'Belmond', 'country' => 'UK'], ['name' => 'Melia Hotels', 'country' => 'Spain'],
            ['name' => 'NH Hotels', 'country' => 'Spain'],
            ['name' => 'Kot-Resort (Partner)', 'country' => 'Russia', 'is_platform_partner' => true],
        ]);
    }
}


