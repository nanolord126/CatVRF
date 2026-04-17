<?php declare(strict_types=1);

namespace App\Domains\Electronics\Http\Controllers;

use App\Domains\Electronics\DTOs\SearchRequestDto;
use App\Domains\Electronics\DTOs\SearchResponseDto;
use App\Domains\Electronics\Services\ElectronicsSearchService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

final readonly class SearchController
{
    public function __construct(
        private ElectronicsSearchService $searchService,
    ) {
    }

    public function search(Request $request): JsonResponse
    {
        $request->validate([
            'query' => 'nullable|string|max:255',
            'page' => 'nullable|integer|min:1',
            'per_page' => 'nullable|integer|min:1|max:100',
            'min_price' => 'nullable|numeric|min:0',
            'max_price' => 'nullable|numeric|min:0',
            'brands' => 'nullable|array',
            'brands.*' => 'string',
            'categories' => 'nullable|array',
            'categories.*' => 'string',
            'colors' => 'nullable|array',
            'colors.*' => 'string',
            'specs' => 'nullable|array',
            'in_stock_only' => 'nullable|boolean',
            'with_discount' => 'nullable|boolean',
            'type' => 'nullable|string|in:smartphones,laptops,tablets,headphones,tv,cameras,smartwatches,gaming,audio,networking,accessories,wearable,home_automation,car_electronics,appliances,e_readers,mobile_phones,desktops,monitors,all_in_one,mini_pc,workstations,motherboards,processors,video_cards,ram,storage,power_supplies,computer_cases,cooling,keyboards,mice,webcams,microphones,speakers,printers,scanners,projectors,tv_accessories,home_theater,media_players,streaming_devices,lenses,camera_accessories,binoculars,telescopes,drones,action_cameras,home_audio,car_audio,portable_audio,hi_fi,dj_equipment,fitness_trackers,smart_rings,smart_glasses,vr_ar,game_consoles,video_games,gaming_accessories,gaming_chairs,gaming_desks,routers,switches,network_cables,wifi_equipment,network_storage,smart_lighting,smart_thermostats,smart_locks,smart_security,smart_plugs,home_sensors,kitchen_appliances,climate_control,vacuum_cleaners,ironing,laundry,dishwashers,car_audio_systems,car_navigation,car_video,car_accessories,dash_cams,car_radar,cables,adapters,chargers,batteries,power_banks,phone_cases,screen_protectors,stands,mounts,office_equipment,shredders,laminators,calculators,label_makers,software,operating_systems,antivirus,office_software,creative_software,health_tech,beauty_devices,massage_devices,medical_devices,hobby_electronics,arduino,raspberry_pi,3d_printers,3d_scanners,tools,soldering',
            'sort.field' => 'nullable|string|in:price,rating,reviews,newest,popularity,discount,relevance',
            'sort.direction' => 'nullable|string|in:asc,desc',
        ]);

        $correlationId = (string) Str::uuid();

        $dto = SearchRequestDto::fromRequest($request->all(), $correlationId);

        $result = $this->searchService->search($dto);

        return response()->json($result->toArray());
    }

    public function getFilters(Request $request): JsonResponse
    {
        $request->validate([
            'category' => 'nullable|string',
        ]);

        $category = $request->input('category');

        $filters = $this->searchService->getAvailableFilters($category);

        return response()->json($filters->toArray());
    }

    public function getSuggestions(Request $request): JsonResponse
    {
        $request->validate([
            'query' => 'required|string|min:2|max:100',
            'limit' => 'nullable|integer|min:1|max:20',
        ]);

        $query = $request->input('query');
        $limit = (int) ($request->input('limit') ?? 10);

        $suggestions = $this->searchService->getSuggestions($query, $limit);

        return response()->json([
            'suggestions' => $suggestions,
            'query' => $query,
        ]);
    }

    public function getPopularSearches(): JsonResponse
    {
        $cacheKey = 'electronics_popular_searches';

        $popularSearches = \Illuminate\Support\Facades\Cache::remember($cacheKey, now()->addHours(6), function () {
            return [
                'iPhone 15',
                'Samsung Galaxy S24',
                'MacBook Pro',
                'iPad Pro',
                'AirPods Pro',
                'Sony PlayStation 5',
                'Nintendo Switch',
                'Dyson V15',
                'Bose QuietComfort',
                'GoPro Hero',
            ];
        });

        return response()->json([
            'popular_searches' => $popularSearches,
        ]);
    }
}
