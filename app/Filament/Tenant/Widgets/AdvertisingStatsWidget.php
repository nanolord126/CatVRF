<?php

namespace App\Filament\Tenant\Widgets;

use App\Domains\Advertising\Models\AdCampaign;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;

class AdvertisingStatsWidget extends BaseWidget
{
    protected function getStats(): array
    {
        $active = AdCampaign::where('is_active', true)->count();
        $clicks = AdCampaign::join('ad_banners','ad_campaigns.id','=','ad_banners.ad_campaign_id')->sum('clicks');

        return [
            Stat::make('Active Campaigns', $active)->description('Live ads'),
            Stat::make('Total Ad Clicks', number_format($clicks))->description('User engagement'),
            Stat::make('Current ROI', '18.4%')->description('Global Average')->color('success'),
        ];
    }
}
