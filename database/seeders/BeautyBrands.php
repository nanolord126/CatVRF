<?php

declare(strict_types=1);

namespace Database\Seeders;

/**
 * Бренды красоты (НЕ ЗАПУСКАТЬ В PRODUCTION).
 */
final class BeautyBrands extends BaseBrandSeeder
{
    public function run(): void
    {
        $this->seedBrands('Beauty', [
            ['name' => 'L\'Oreal', 'country' => 'France', 'description' => 'World leader in beauty.'],
            ['name' => 'Estée Lauder', 'country' => 'USA'], ['name' => 'Dyson', 'country' => 'UK'],
            ['name' => 'Chanel', 'country' => 'France'], ['name' => 'MAC Cosmetics', 'country' => 'Canada'],
            ['name' => 'Clinique', 'country' => 'USA'], ['name' => 'Shiseido', 'country' => 'Japan'],
            ['name' => 'Lancôme', 'country' => 'France'], ['name' => 'Guerlain', 'country' => 'France'],
            ['name' => 'Dior Beauty', 'country' => 'France'], ['name' => 'Maybelline', 'country' => 'USA'],
            ['name' => 'Revlon', 'country' => 'USA'], ['name' => 'NARS', 'country' => 'France'],
            ['name' => 'Urban Decay', 'country' => 'USA'], ['name' => 'Kiehl\'s', 'country' => 'USA'],
            ['name' => 'La Mer', 'country' => 'USA'], ['name' => 'Fenty Beauty', 'country' => 'USA'],
            ['name' => 'Huda Beauty', 'country' => 'UAE'], ['name' => 'Anastasia Beverly Hills', 'country' => 'USA'],
            ['name' => 'Charlotte Tilbury', 'country' => 'UK'], ['name' => 'Benefit Cosmetics', 'country' => 'USA'],
            ['name' => 'Tarte', 'country' => 'USA'], ['name' => 'Too Faced', 'country' => 'USA'],
            ['name' => 'Glossier', 'country' => 'USA'], ['name' => 'The Ordinary', 'country' => 'Canada'],
            ['name' => 'CeraVe', 'country' => 'USA'], ['name' => 'Vichy', 'country' => 'France'],
            ['name' => 'La Roche-Posay', 'country' => 'France'], ['name' => 'Eucerin', 'country' => 'Germany'],
            ['name' => 'Bioderma', 'country' => 'France'], ['name' => 'Avene', 'country' => 'France'],
            ['name' => 'Clarins', 'country' => 'France'], ['name' => 'Sisley', 'country' => 'France'],
            ['name' => 'Amway (Artistry)', 'country' => 'USA'], ['name' => 'Mary Kay', 'country' => 'USA'],
            ['name' => 'Avon', 'country' => 'UK'], ['name' => 'Oriflame', 'country' => 'Sweden'],
            ['name' => 'The Body Shop', 'country' => 'UK'], ['name' => 'L\'Occitane', 'country' => 'France'],
            ['name' => 'Yves Rocher', 'country' => 'France'], ['name' => 'Nyx', 'country' => 'USA'],
            ['name' => 'Kiko Milano', 'country' => 'Italy'], ['name' => 'Sephora Collection', 'country' => 'France'],
            ['name' => 'Pupa Milano', 'country' => 'Italy'], ['name' => 'Catrice', 'country' => 'Germany'],
            ['name' => 'Essence', 'country' => 'Germany'], ['name' => 'Givenchy Beauty', 'country' => 'France'],
            ['name' => 'Armani Beauty', 'country' => 'Italy'], ['name' => 'Tom Ford Beauty', 'country' => 'USA'],
            ['name' => 'Kots-Beauty (Partner)', 'country' => 'Russia', 'is_platform_partner' => true],
        ]);
    }
}


