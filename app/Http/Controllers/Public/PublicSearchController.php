<?php

namespace App\Http\Controllers\Public;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Services\Common\AI\HybridSearchEngine;
use App\Services\Infrastructure\DopplerService;
use Illuminate\Support\Facades\Auth;

class PublicSearchController extends Controller
{
    protected HybridSearchEngine $searchEngine;

    public function __construct(HybridSearchEngine $searchEngine)
    {
        $this->searchEngine = $searchEngine;
    }

    /**
     * Unified Public Entrance: 2026 Semantic + Geo + Behavioral Search
     */
    public function search(Request $request)
    {
        $query = $request->input('q', '');
        $lat = $request->float('lat', (float) DopplerService::get('DEFAULT_LAT', 55.7558));
        $lng = $request->float('lng', (float) DopplerService::get('DEFAULT_LNG', 37.6173));
        $radius = $request->integer('radius', 10000); // 10km default

        // 2026 Canon: Auto-telemetry for every search via background Queue
        if (Auth::check()) {
            \App\Jobs\Common\ProcessSearchTelemetryJob::dispatch([
                'user_id' => Auth::id(),
                'event_type' => 'search_executed',
                'entity_type' => 'SearchQuery',
                'entity_id' => 0,
                'category' => $request->input('collection', 'marketplace_entities'),
                'payload' => [
                    'query' => $query,
                    'lat' => $lat,
                    'lng' => $lng,
                    'correlation_id' => request()->header('X-Correlation-ID')
                ],
                'correlation_id' => request()->header('X-Correlation-ID', (string) \Illuminate\Support\Str::uuid())
            ]);
        }

        $results = $this->searchEngine->search(
            $query,
            Auth::user(),
            ['lat' => $lat, 'lng' => $lng, 'radius' => $radius],
            $request->input('collection', 'marketplace_entities')
        );

        return response()->json([
            'results' => $results,
            'timing' => microtime(true) - LARAVEL_START,
            'meta' => [
                'total_hits' => count($results),
                'weights_applied' => '40:30:20:10',
                'model' => 'text-embedding-3-large',
                'correlation_id' => request()->header('X-Correlation-ID', 'public-'.now()->timestamp)
            ]
        ]);
    }
}
