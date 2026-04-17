<?php

declare(strict_types=1);

namespace App\Domains\Electronics\Http\Controllers;

use App\Domains\Electronics\DTOs\AnalyticsDto;
use App\Domains\Electronics\Services\ElectronicsAnalyticsService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

final readonly class AnalyticsController
{
    public function __construct(
        private ElectronicsAnalyticsService $analyticsService,
    ) {
    }

    public function getAnalytics(Request $request): JsonResponse
    {
        $request->validate([
            'period' => 'nullable|string|in:1d,7d,30d,90d,1y',
            'type' => 'nullable|string|in:smartphones,laptops,tablets,headphones,tv,cameras,smartwatches,gaming,audio,networking,accessories,wearable,home_automation,car_electronics,appliances,e_readers,mobile_phones,desktops,monitors,all_in_one,mini_pc,workstations,motherboards,processors,video_cards,ram,storage,power_supplies,computer_cases,cooling,keyboards,mice,webcams,microphones,speakers,printers,scanners,projectors,tv_accessories,home_theater,media_players,streaming_devices,lenses,camera_accessories,binoculars,telescopes,drones,action_cameras,home_audio,car_audio,portable_audio,hi_fi,dj_equipment,fitness_trackers,smart_rings,smart_glasses,vr_ar,game_consoles,video_games,gaming_accessories,gaming_chairs,gaming_desks,routers,switches,network_cables,wifi_equipment,network_storage,smart_lighting,smart_thermostats,smart_locks,smart_security,smart_plugs,home_sensors,kitchen_appliances,climate_control,vacuum_cleaners,ironing,laundry,dishwashers,car_audio_systems,car_navigation,car_video,car_accessories,dash_cams,car_radar,cables,adapters,chargers,batteries,power_banks,phone_cases,screen_protectors,stands,mounts,office_equipment,shredders,laminators,calculators,label_makers,software,operating_systems,antivirus,office_software,creative_software,health_tech,beauty_devices,massage_devices,medical_devices,hobby_electronics,arduino,raspberry_pi,3d_printers,3d_scanners,tools,soldering',
        ]);

        $period = $request->input('period', '7d');
        $type = $request->input('type');

        $analytics = $this->analyticsService->getAnalytics($period, $type);

        return response()->json($analytics->toArray());
    }

    public function getSalesData(Request $request): JsonResponse
    {
        $request->validate([
            'period' => 'nullable|string|in:1d,7d,30d,90d,1y',
            'type' => 'nullable|string',
        ]);

        $period = $request->input('period', '7d');
        $type = $request->input('type');

        $analytics = $this->analyticsService->getAnalytics($period, $type);

        return response()->json([
            'sales_data' => $analytics->salesData,
            'period' => $analytics->period,
        ]);
    }

    public function getTrafficData(Request $request): JsonResponse
    {
        $request->validate([
            'period' => 'nullable|string|in:1d,7d,30d,90d,1y',
            'type' => 'nullable|string',
        ]);

        $period = $request->input('period', '7d');
        $type = $request->input('type');

        $analytics = $this->analyticsService->getAnalytics($period, $type);

        return response()->json([
            'traffic_data' => $analytics->trafficData,
            'period' => $analytics->period,
        ]);
    }

    public function getConversionData(Request $request): JsonResponse
    {
        $request->validate([
            'period' => 'nullable|string|in:1d,7d,30d,90d,1y',
            'type' => 'nullable|string',
        ]);

        $period = $request->input('period', '7d');
        $type = $request->input('type');

        $analytics = $this->analyticsService->getAnalytics($period, $type);

        return response()->json([
            'conversion_data' => $analytics->conversionData,
            'period' => $analytics->period,
        ]);
    }

    public function getTopProducts(Request $request): JsonResponse
    {
        $request->validate([
            'period' => 'nullable|string|in:1d,7d,30d,90d,1y',
            'type' => 'nullable|string',
            'limit' => 'nullable|integer|min:1|max:50',
        ]);

        $period = $request->input('period', '7d');
        $type = $request->input('type');
        $limit = (int) $request->input('limit', 10);

        $analytics = $this->analyticsService->getAnalytics($period, $type);

        $topProducts = array_slice($analytics->topProducts, 0, $limit);

        return response()->json([
            'top_products' => $topProducts,
            'period' => $analytics->period,
        ]);
    }

    public function getBrandStats(Request $request): JsonResponse
    {
        $request->validate([
            'period' => 'nullable|string|in:1d,7d,30d,90d,1y',
            'type' => 'nullable|string',
        ]);

        $period = $request->input('period', '7d');
        $type = $request->input('type');

        $analytics = $this->analyticsService->getAnalytics($period, $type);

        return response()->json([
            'brand_stats' => $analytics->brandStats,
            'period' => $analytics->period,
        ]);
    }

    public function getCategoryStats(Request $request): JsonResponse
    {
        $request->validate([
            'period' => 'nullable|string|in:1d,7d,30d,90d,1y',
            'type' => 'nullable|string',
        ]);

        $period = $request->input('period', '7d');
        $type = $request->input('type');

        $analytics = $this->analyticsService->getAnalytics($period, $type);

        return response()->json([
            'category_stats' => $analytics->categoryStats,
            'period' => $analytics->period,
        ]);
    }

    public function getPriceDistribution(Request $request): JsonResponse
    {
        $request->validate([
            'period' => 'nullable|string|in:1d,7d,30d,90d,1y',
            'type' => 'nullable|string',
        ]);

        $period = $request->input('period', '7d');
        $type = $request->input('type');

        $analytics = $this->analyticsService->getAnalytics($period, $type);

        return response()->json([
            'price_distribution' => $analytics->priceDistribution,
            'period' => $analytics->period,
        ]);
    }

    public function getInventoryStats(Request $request): JsonResponse
    {
        $request->validate([
            'type' => 'nullable|string',
        ]);

        $type = $request->input('type');

        $analytics = $this->analyticsService->getAnalytics('7d', $type);

        return response()->json([
            'inventory_stats' => $analytics->inventoryStats,
        ]);
    }

    public function getCustomerBehavior(Request $request): JsonResponse
    {
        $request->validate([
            'period' => 'nullable|string|in:1d,7d,30d,90d,1y',
            'type' => 'nullable|string',
        ]);

        $period = $request->input('period', '7d');
        $type = $request->input('type');

        $analytics = $this->analyticsService->getAnalytics($period, $type);

        return response()->json([
            'customer_behavior' => $analytics->customerBehavior,
            'period' => $analytics->period,
        ]);
    }

    public function clearCache(Request $request): JsonResponse
    {
        $request->validate([
            'type' => 'nullable|string',
        ]);

        $type = $request->input('type');

        $this->analyticsService->clearCache($type);

        return response()->json([
            'message' => 'Analytics cache cleared successfully',
            'type' => $type,
        ]);
    }
}
