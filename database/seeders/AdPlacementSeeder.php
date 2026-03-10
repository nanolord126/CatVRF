<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Domains\Advertising\Models\AdPlacement;

class AdPlacementSeeder extends Seeder
{
    public function run(): void
    {
        AdPlacement::updateOrCreate(['code' => 'main_top_banner'], [
            'name' => 'Главный верхний баннер',
            'allowed_types' => ['banner', 'html'],
            'dimensions' => ['width' => 1200, 'height' => 300],
            'description' => 'Отображается в шапке главной страницы всех тенантов.',
            'is_active' => true
        ]);

        AdPlacement::updateOrCreate(['code' => 'sidebar_recommendation'], [
            'name' => 'Боковой блок рекомендаций',
            'allowed_types' => ['native', 'card'],
            'dimensions' => ['width' => 400, 'height' => 600],
            'description' => 'Блок нативного продвижения товаров и услуг в боковой панели.',
            'is_active' => true
        ]);

        AdPlacement::updateOrCreate(['code' => 'footer_sticky'], [
            'name' => 'Закрепленный футер',
            'allowed_types' => ['native'],
            'dimensions' => ['width' => '100vw', 'height' => 80],
            'description' => 'Узкий баннер в нижней части экрана для мобильных устройств.',
            'is_active' => true
        ]);
    }
}
