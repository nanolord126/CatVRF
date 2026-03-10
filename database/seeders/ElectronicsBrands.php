<?php
namespace Database\Seeders;
class ElectronicsBrands extends BaseBrandSeeder {
    public function run(): void {
        $this->seedBrands('Electronics', [
            ['name' => 'Apple', 'country' => 'USA'], ['name' => 'Samsung', 'country' => 'South Korea'],
            ['name' => 'Xiaomi', 'country' => 'China'], ['name' => 'Sony', 'country' => 'Japan'],
            ['name' => 'LG', 'country' => 'South Korea'], ['name' => 'Asus', 'country' => 'Taiwan'],
            ['name' => 'HP', 'country' => 'USA'], ['name' => 'Dell', 'country' => 'USA'],
            ['name' => 'Lenovo', 'country' => 'China'], ['name' => 'Microsoft', 'country' => 'USA'],
            ['name' => 'Huawei', 'country' => 'China'], ['name' => 'Realme', 'country' => 'China'],
            ['name' => 'Oppo', 'country' => 'China'], ['name' => 'Vivo', 'country' => 'China'],
            ['name' => 'OnePlus', 'country' => 'China'], ['name' => 'Google (Pixel)', 'country' => 'USA'],
            ['name' => 'Acer', 'country' => 'Taiwan'], ['name' => 'MSI', 'country' => 'Taiwan'],
            ['name' => 'Razer', 'country' => 'USA/Singapore'], ['name' => 'Logitech', 'country' => 'Switzerland'],
            ['name' => 'Intel', 'country' => 'USA'], ['name' => 'AMD', 'country' => 'USA'],
            ['name' => 'NVIDIA', 'country' => 'USA'], ['name' => 'Canon', 'country' => 'Japan'],
            ['name' => 'Nikon', 'country' => 'Japan'], ['name' => 'DJI', 'country' => 'China'],
            ['name' => 'GoPro', 'country' => 'USA'], ['name' => 'Bose', 'country' => 'USA'],
            ['name' => 'JBL', 'country' => 'USA'], ['name' => 'Sennheiser', 'country' => 'Germany'],
            ['name' => 'Bang & Olufsen', 'country' => 'Denmark'], ['name' => 'Philips', 'country' => 'Netherlands'],
            ['name' => 'Panasonic', 'country' => 'Japan'], ['name' => 'Toshiba', 'country' => 'Japan'],
            ['name' => 'Fujifilm', 'country' => 'Japan'], ['name' => 'Brother', 'country' => 'Japan'],
            ['name' => 'Epson', 'country' => 'Japan'], ['name' => 'Western Digital', 'country' => 'USA'],
            ['name' => 'Seagate', 'country' => 'USA'], ['name' => 'Kingston', 'country' => 'USA'],
            ['name' => 'TP-Link', 'country' => 'China'], ['name' => 'Keenetic', 'country' => 'Global'],
            ['name' => 'Garmin', 'country' => 'USA'], ['name' => 'Fitbit', 'country' => 'USA'],
            ['name' => 'Amazfit', 'country' => 'China'], ['name' => 'Nintendo', 'country' => 'Japan'],
            ['name' => 'Honor', 'country' => 'China'], ['name' => 'Beko', 'country' => 'Turkey'],
            ['name' => 'Haier', 'country' => 'China'],
            ['name' => 'Kotvrf-Tech (Partner)', 'country' => 'Russia', 'is_platform_partner' => true],
        ]);
    }
}


