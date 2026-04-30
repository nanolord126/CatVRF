<?php

declare(strict_types=1);

namespace Modules\BeautyMasters\Filament\Widgets;

use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Modules\BeautyMasters\Infrastructure\Models\BeautyCertificationModel;
use Modules\BeautyMasters\Infrastructure\Models\MakeupCertificationModel;
use Modules\BeautyMasters\Infrastructure\Models\BrowCertificationModel;
use Modules\BeautyMasters\Infrastructure\Models\LashCertificationModel;

final class CertificationStatsWidget extends BaseWidget
{
    protected function getStats(): array
    {
        $beautyActive = BeautyCertificationModel::where('status', 'active')->count();
        $beautyExpiring = BeautyCertificationModel::where('status', 'active')
            ->where('expiry_date', '>', now())
            ->where('expiry_date', '<=', now()->addDays(60))
            ->count();

        $makeupActive = MakeupCertificationModel::where('status', 'active')->count();
        $makeupExpiring = MakeupCertificationModel::where('status', 'active')
            ->where('expiry_date', '>', now())
            ->where('expiry_date', '<=', now()->addDays(60))
            ->count();

        $browActive = BrowCertificationModel::where('status', 'active')->count();
        $browExpiring = BrowCertificationModel::where('status', 'active')
            ->where('expiry_date', '>', now())
            ->where('expiry_date', '<=', now()->addDays(60))
            ->count();

        $lashActive = LashCertificationModel::where('status', 'active')->count();
        $lashExpiring = LashCertificationModel::where('status', 'active')
            ->where('expiry_date', '>', now())
            ->where('expiry_date', '<=', now()->addDays(60))
            ->count();

        return [
            Stat::make('Beauty Active Certifications', $beautyActive)
                ->description("{$beautyExpiring} expiring soon")
                ->descriptionIcon('heroicon-m-exclamation-triangle')
                ->color('success'),

            Stat::make('Makeup Active Certifications', $makeupActive)
                ->description("{$makeupExpiring} expiring soon")
                ->descriptionIcon('heroicon-m-exclamation-triangle')
                ->color('success'),

            Stat::make('Brow Active Certifications', $browActive)
                ->description("{$browExpiring} expiring soon")
                ->descriptionIcon('heroicon-m-exclamation-triangle')
                ->color('success'),

            Stat::make('Lash Active Certifications', $lashActive)
                ->description("{$lashExpiring} expiring soon")
                ->descriptionIcon('heroicon-m-exclamation-triangle')
                ->color('success'),
        ];
    }
}
