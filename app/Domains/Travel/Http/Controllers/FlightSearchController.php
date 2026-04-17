<?php declare(strict_types=1);

namespace App\Domains\Travel\Http\Controllers;

use App\Domains\Travel\Services\ExternalFlightSearchService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Psr\Log\LoggerInterface;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;

/**
 * Flight Search Controller
 * 
 * API controller for external flight search integration.
 * Provides endpoints for searching flights across multiple providers
 * (Amadeus, Sabre, Skyscanner) with caching and fallback support.
 */
final class FlightSearchController
{
    public function __construct(
        private readonly ExternalFlightSearchService $flightSearchService,
        private readonly LoggerInterface $logger,
    ) {}

    /**
     * Search for flights.
     * 
     * @param Request $request
     * @return JsonResponse
     */
    public function search(Request $request): JsonResponse
    {
        $correlationId = $request->header('X-Correlation-ID', Str::uuid()->toString());

        $validator = Validator::make($request->all(), [
            'origin' => 'required|string|max:3',
            'destination' => 'required|string|max:3',
            'date' => 'required|date|after:today',
            'return_date' => 'nullable|date|after:date',
            'passengers' => 'nullable|integer|min:1|max:9',
            'currency' => 'nullable|string|max:3',
            'class' => 'nullable|in:economy,business,first',
            'direct_only' => 'nullable|boolean',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors(),
                'correlation_id' => $correlationId,
            ], 422);
        }

        try {
            $params = [
                'origin' => strtoupper($request->input('origin')),
                'destination' => strtoupper($request->input('destination')),
                'date' => $request->input('date'),
                'return_date' => $request->input('return_date'),
                'passengers' => $request->input('passengers', 1),
                'currency' => $request->input('currency', 'RUB'),
                'class' => $request->input('class', 'economy'),
                'direct_only' => $request->input('direct_only', false),
            ];

            $results = $this->flightSearchService->searchFlights($params, $correlationId);

            $this->logger->info('Flight search completed', [
                'origin' => $params['origin'],
                'destination' => $params['destination'],
                'date' => $params['date'],
                'passengers' => $params['passengers'],
                'results_count' => $results['count'] ?? 0,
                'correlation_id' => $correlationId,
            ]);

            return response()->json([
                'success' => true,
                'data' => $results,
                'correlation_id' => $correlationId,
            ]);
        } catch (\Throwable $e) {
            $this->logger->error('Flight search failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'correlation_id' => $correlationId,
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Flight search failed',
                'error' => $e->getMessage(),
                'correlation_id' => $correlationId,
            ], 500);
        }
    }

    /**
     * Get available airports for autocomplete.
     * 
     * @param Request $request
     * @return JsonResponse
     */
    public function airports(Request $request): JsonResponse
    {
        $correlationId = $request->header('X-Correlation-ID', Str::uuid()->toString());
        $query = $request->input('q', '');

        if (strlen($query) < 2) {
            return response()->json([
                'success' => false,
                'message' => 'Query too short',
                'correlation_id' => $correlationId,
            ], 400);
        }

        try {
            // This would typically call an external airport database API
            // For now, return mock data
            $airports = $this->getAirportsMock($query);

            return response()->json([
                'success' => true,
                'data' => $airports,
                'correlation_id' => $correlationId,
            ]);
        } catch (\Throwable $e) {
            $this->logger->error('Airport search failed', [
                'error' => $e->getMessage(),
                'correlation_id' => $correlationId,
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Airport search failed',
                'correlation_id' => $correlationId,
            ], 500);
        }
    }

    /**
     * Get flight details by ID.
     * 
     * @param Request $request
     * @param string $id
     * @return JsonResponse
     */
    public function show(Request $request, string $id): JsonResponse
    {
        $correlationId = $request->header('X-Correlation-ID', Str::uuid()->toString());

        try {
            // This would fetch detailed flight information
            // For now, return a placeholder response
            return response()->json([
                'success' => true,
                'data' => [
                    'id' => $id,
                    'message' => 'Flight details endpoint - to be implemented',
                ],
                'correlation_id' => $correlationId,
            ]);
        } catch (\Throwable $e) {
            $this->logger->error('Flight details fetch failed', [
                'error' => $e->getMessage(),
                'correlation_id' => $correlationId,
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch flight details',
                'correlation_id' => $correlationId,
            ], 500);
        }
    }

    /**
     * Get mock airport data for autocomplete.
     * 
     * @param string $query
     * @return array
     */
    private function getAirportsMock(string $query): array
    {
        $allAirports = [
            ['code' => 'SVO', 'name' => 'Sheremetyevo International Airport', 'city' => 'Moscow', 'country' => 'Russia'],
            ['code' => 'DME', 'name' => 'Domodedovo International Airport', 'city' => 'Moscow', 'country' => 'Russia'],
            ['code' => 'LED', 'name' => 'Pulkovo Airport', 'city' => 'Saint Petersburg', 'country' => 'Russia'],
            ['code' => 'JFK', 'name' => 'John F. Kennedy International Airport', 'city' => 'New York', 'country' => 'United States'],
            ['code' => 'LAX', 'name' => 'Los Angeles International Airport', 'city' => 'Los Angeles', 'country' => 'United States'],
            ['code' => 'LHR', 'name' => 'Heathrow Airport', 'city' => 'London', 'country' => 'United Kingdom'],
            ['code' => 'CDG', 'name' => 'Charles de Gaulle Airport', 'city' => 'Paris', 'country' => 'France'],
            ['code' => 'FRA', 'name' => 'Frankfurt Airport', 'city' => 'Frankfurt', 'country' => 'Germany'],
            ['code' => 'DXB', 'name' => 'Dubai International Airport', 'city' => 'Dubai', 'country' => 'United Arab Emirates'],
            ['code' => 'IST', 'name' => 'Istanbul Airport', 'city' => 'Istanbul', 'country' => 'Turkey'],
            ['code' => 'HND', 'name' => 'Haneda Airport', 'city' => 'Tokyo', 'country' => 'Japan'],
            ['code' => 'PEK', 'name' => 'Beijing Capital International Airport', 'city' => 'Beijing', 'country' => 'China'],
            ['code' => 'BKK', 'name' => 'Suvarnabhumi Airport', 'city' => 'Bangkok', 'country' => 'Thailand'],
            ['code' => 'SIN', 'name' => 'Singapore Changi Airport', 'city' => 'Singapore', 'country' => 'Singapore'],
            ['code' => 'SYD', 'name' => 'Sydney Airport', 'city' => 'Sydney', 'country' => 'Australia'],
        ];

        $query = strtoupper($query);
        
        return array_filter($allAirports, function ($airport) use ($query) {
            return str_contains($airport['code'], $query) || 
                   str_contains(strtoupper($airport['name']), $query) ||
                   str_contains(strtoupper($airport['city']), $query);
        });
    }
}
