<?php

declare(strict_types=1);

namespace App\Domains\Electronics\Http\Controllers;

use App\Domains\Electronics\Services\ElectronicsFilterConfigService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

final readonly class FilterConfigController
{
    public function __construct(
        private ElectronicsFilterConfigService $filterConfigService,
    ) {
    }

    public function getAllTypes(): JsonResponse
    {
        $types = $this->filterConfigService->getAllTypes();

        return response()->json([
            'types' => $types,
        ]);
    }

    public function getPopularTypes(): JsonResponse
    {
        $limit = (int) request()->query('limit', 6);
        $types = $this->filterConfigService->getPopularTypes($limit);

        return response()->json([
            'types' => $types,
        ]);
    }

    public function getFilterConfig(Request $request, string $type): JsonResponse
    {
        $config = $this->filterConfigService->getFilterConfig($type);

        if (!$config) {
            return response()->json([
                'error' => 'Type not found',
            ], 404);
        }

        return response()->json([
            'config' => $config->toArray(),
        ]);
    }

    public function getSearchPatterns(Request $request, string $type): JsonResponse
    {
        $patterns = $this->filterConfigService->getSearchPatterns($type);

        if (empty($patterns)) {
            return response()->json([
                'error' => 'Type not found',
            ], 404);
        }

        return response()->json($patterns);
    }

    public function getTypeSuggestions(Request $request, string $type): JsonResponse
    {
        $request->validate([
            'query' => 'required|string|min:1|max:100',
            'limit' => 'nullable|integer|min:1|max:20',
        ]);

        $query = $request->input('query');
        $limit = (int) ($request->input('limit') ?? 10);

        $suggestions = $this->filterConfigService->getTypeSearchSuggestions($type, $query, $limit);

        return response()->json([
            'suggestions' => $suggestions,
            'query' => $query,
        ]);
    }

    public function getTypeHierarchy(): JsonResponse
    {
        $hierarchy = $this->filterConfigService->getTypeHierarchy();

        return response()->json([
            'hierarchy' => $hierarchy,
        ]);
    }

    public function validateFilters(Request $request, string $type): JsonResponse
    {
        $filters = $request->all();

        $validation = $this->filterConfigService->validateFilterValues($type, $filters);

        return response()->json($validation);
    }
}
