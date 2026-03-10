<?php

namespace App\Http\Controllers\Public;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Services\Common\AI\HybridSearchEngine;
use App\Services\Common\AI\RecommendationService;
use App\Models\MarketplaceVerticals;
use Illuminate\Support\Facades\Response;
use Illuminate\Http\JsonResponse;

/**
 * 2026 Public AI Facade.
 * Main entry point for Global Hybrid Search across all marketplace verticals.
 */
class PublicAIFacadeController extends Controller
{
    protected HybridSearchEngine $searchEngine;
    protected RecommendationService $recService;

    public function __construct(HybridSearchEngine $searchEngine, RecommendationService $recService)
    {
        $this->searchEngine = $searchEngine;
        $this->recService = $recService;
    }

    /**
     * AI-Powered Hybrid Search: Full-Text + Semantic Vector + Geo + Behavioral Boost.
     */
    public function search(Request $request): JsonResponse
    {
        $request->validate([
            'q' => 'required|string|min:2',
            'lat' => 'nullable|numeric',
            'lng' => 'nullable|numeric',
            'radius' => 'nullable|integer|max:50000', // max 50km
        ]);

        $query = $request->input('q');
        $user = $request->user();
        $geo = [
            'lat' => (float) ($request->input('lat') ?? $request->header('X-Geo-Lat') ?? 0.0),
            'lng' => (float) ($request->input('lng') ?? $request->header('X-Geo-Lng') ?? 0.0),
            'radius' => (int) $request->input('radius', 5000), // Default 5km
        ];

        // Pipeline: Hybrid Search Engine with 40/30/20/10 Weights
        $engineMode = config('app.heavy_features_enabled', true) ? 'full' : 'lightweight';
        $results = $this->searchEngine->search($query, $user, $geo, $engineMode);

        return Response::json([
            'success' => true,
            'query' => $query,
            'results' => $results,
            'metadata' => [
                'count' => count($results),
                'latency' => now()->diffInMilliseconds($request->start_time ?? now()),
                'engine' => 'CatVRF-AI-2026'
            ]
        ]);
    }

    /**
     * Global Personalized Recommendations for Home/Landing Page.
     */
    public function homeRecommendations(Request $request): JsonResponse
    {
        if (!$request->user()) {
            // Unauthenticated: return geo-based trending nearby
            $recs = $this->recService->geoNearby(
                (float) ($request->input('lat') ?? 0.0), 
                (float) ($request->input('lng') ?? 0.0)
            );
        } else {
            // Personalized based on AI behavioral telemetry
            $recs = $this->recService->forUser($request->user());
        }

        return Response::json([
            'success' => true,
            'recommendations' => $recs,
            'meta' => [
                'engine' => 'CatVRF-AI-2026',
                'timestamp' => now()->toIso8601String()
            ]
        ]);
    }
}
