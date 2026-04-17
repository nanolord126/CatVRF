<?php declare(strict_types=1);

namespace App\Domains\Travel\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Contracts\Cache\Repository as Cache;
use Psr\Log\LoggerInterface;

/**
 * External Flight Search Service
 * 
 * Integrates with external flight search APIs (Amadeus, Sabre, Skyscanner)
 * to provide real-time flight availability and pricing.
 * 
 * Supports multiple providers with fallback and caching.
 */
final readonly class ExternalFlightSearchService
{
    public function __construct(
        private LoggerInterface $logger,
        private Cache $cache,
    ) {}

    /**
     * Search for flights across multiple providers.
     * 
     * @param array $params Search parameters (origin, destination, date, passengers, etc.)
     * @param string $correlationId Correlation ID for tracing
     * @return array Flight search results
     */
    public function searchFlights(array $params, string $correlationId = ''): array
    {
$cacheKey = $this->getCacheKey($params);
        
        return $this->cache->tags(['travel', 'flight_search'])->remember($cacheKey, now()->addMinutes(30), function () use ($params, $correlationId) {
            $provider = config('services.flight_search.default_provider', 'amadeus');
            
            try {
                $results = match ($provider) {
                    'amadeus' => $this->searchAmadeus($params, $correlationId),
                    'sabre' => $this->searchSabre($params, $correlationId),
                    'skyscanner' => $this->searchSkyscanner($params, $correlationId),
                    default => $this->searchAmadeus($params, $correlationId),
                };
                
                $this->logger->info('External flight search completed', [
                    'provider' => $provider,
                    'origin' => $params['origin'] ?? null,
                    'destination' => $params['destination'] ?? null,
                    'results_count' => count($results['flights'] ?? []),
                    'correlation_id' => $correlationId,
                ]);
                
                return $results;
            } catch (\Throwable $e) {
                $this->logger->error('External flight search failed', [
                    'provider' => $provider,
                    'error' => $e->getMessage(),
                    'correlation_id' => $correlationId,
                ]);
                
                return $this->getFallbackResults($params);
            }
        });
    }

    /**
     * Search flights using Amadeus API.
     */
    private function searchAmadeus(array $params, string $correlationId = ''): array
    {
        $apiKey = config('services.flight_search.amadeus.api_key');
        $apiSecret = config('services.flight_search.amadeus.api_secret');
        
        if (!$apiKey || !$apiSecret) {
            throw new \RuntimeException('Amadeus API credentials not configured');
        }
        
        // Get access token
        $token = $this->getAmadeusToken($apiKey, $apiSecret);
        
        // Build search URL
        $url = 'https://test.api.amadeus.com/v2/shopping/flight-offers';
        $queryParams = [
            'originLocationCode' => $params['origin'],
            'destinationLocationCode' => $params['destination'],
            'departureDate' => $params['date'],
            'adults' => $params['passengers'] ?? 1,
            'currencyCode' => $params['currency'] ?? 'RUB',
        ];
        
        if (!empty($params['return_date'])) {
            $queryParams['returnDate'] = $params['return_date'];
        }
        
        $response = Http::withToken($token)
            ->get($url, $queryParams);
        
        if (!$response->successful()) {
            throw new \RuntimeException('Amadeus API request failed: ' . $response->status());
        }
        
        $data = $response->json();
        
        return $this->normalizeAmadeusResults($data);
    }

    /**
     * Search flights using Sabre API.
     */
    private function searchSabre(array $params, string $correlationId = ''): array
    {
        $apiKey = config('services.flight_search.sabre.api_key');
        $apiSecret = config('services.flight_search.sabre.api_secret');
        
        if (!$apiKey || !$apiSecret) {
            throw new \RuntimeException('Sabre API credentials not configured');
        }
        
        // Get access token
        $token = $this->getSabreToken($apiKey, $apiSecret);
        
        // Build search request
        $url = 'https://api.test.sabre.com/v2/shop/flights';
        
        $requestBody = [
            'OTA_AirLowFareSearchRQ' => [
                'OriginDestinationInformation' => [
                    [
                        'DepartureDateTime' => $params['date'],
                        'OriginLocation' => ['LocationCode' => $params['origin']],
                        'DestinationLocation' => ['LocationCode' => $params['destination']],
                    ]
                ],
                'PassengerTypeQuantity' => [
                    ['Code' => 'ADT', 'Quantity' => $params['passengers'] ?? 1],
                ],
                'TPA_Extensions' => [
                    'IntelliSellTransaction' => ['RequestType' => ['Name' => '50ITINS']],
                ],
            ],
        ];
        
        if (!empty($params['return_date'])) {
            $requestBody['OTA_AirLowFareSearchRQ']['OriginDestinationInformation'][] = [
                'DepartureDateTime' => $params['return_date'],
                'OriginLocation' => ['LocationCode' => $params['destination']],
                'DestinationLocation' => ['LocationCode' => $params['origin']],
            ];
        }
        
        $response = Http::withToken($token)
            ->post($url, $requestBody);
        
        if (!$response->successful()) {
            throw new \RuntimeException('Sabre API request failed: ' . $response->status());
        }
        
        $data = $response->json();
        
        return $this->normalizeSabreResults($data);
    }

    /**
     * Search flights using Skyscanner API.
     */
    private function searchSkyscanner(array $params, string $correlationId = ''): array
    {
        $apiKey = config('services.flight_search.skyscanner.api_key');
        
        if (!$apiKey) {
            throw new \RuntimeException('Skyscanner API key not configured');
        }
        
        $url = 'https://partners.api.skyscanner.net/apiservices/v3/flights/live/search/create';
        
        $requestBody = [
            'query' => [
                'market' => 'RU',
                'locale' => 'ru-RU',
                'currency' => $params['currency'] ?? 'RUB',
                'queryLegs' => [
                    [
                        'originPlaceId' => ['iata' => $params['origin']],
                        'destinationPlaceId' => ['iata' => $params['destination']],
                        'date' => ['year' => date('Y', strtotime($params['date'])), 'month' => date('n', strtotime($params['date'])), 'day' => date('j', strtotime($params['date']))],
                    ]
                ],
                'adults' => $params['passengers'] ?? 1,
            ],
        ];
        
        if (!empty($params['return_date'])) {
            $requestBody['query']['queryLegs'][] = [
                'originPlaceId' => ['iata' => $params['destination']],
                'destinationPlaceId' => ['iata' => $params['origin']],
                'date' => ['year' => date('Y', strtotime($params['return_date'])), 'month' => date('n', strtotime($params['return_date'])), 'day' => date('j', strtotime($params['return_date']))],
            ];
        }
        
        $response = Http::withHeaders([
            'x-api-key' => $apiKey,
        ])->post($url, $requestBody);
        
        if (!$response->successful()) {
            throw new \RuntimeException('Skyscanner API request failed: ' . $response->status());
        }
        
        $data = $response->json();
        
        return $this->normalizeSkyscannerResults($data);
    }

    /**
     * Get Amadeus access token.
     */
    private function getAmadeusToken(string $apiKey, string $apiSecret): string
    {
        $response = Http::asForm()->post('https://test.api.amadeus.com/v1/security/oauth2/token', [
            'grant_type' => 'client_credentials',
            'client_id' => $apiKey,
            'client_secret' => $apiSecret,
        ]);
        
        if (!$response->successful()) {
            throw new \RuntimeException('Failed to get Amadeus token');
        }
        
        return $response->json()['access_token'];
    }

    /**
     * Get Sabre access token.
     */
    private function getSabreToken(string $apiKey, string $apiSecret): string
    {
        $response = Http::asForm()->post('https://api.test.sabre.com/v2/auth/token', [
            'grant_type' => 'client_credentials',
            'client_id' => $apiKey,
            'client_secret' => $apiSecret,
        ]);
        
        if (!$response->successful()) {
            throw new \RuntimeException('Failed to get Sabre token');
        }
        
        return $response->json()['access_token'];
    }

    /**
     * Normalize Amadeus results to standard format.
     */
    private function normalizeAmadeusResults(array $data): array
    {
        $flights = [];
        
        foreach ($data['data'] ?? [] as $offer) {
            $flights[] = [
                'id' => $offer['id'] ?? null,
                'provider' => 'amadeus',
                'airline' => $offer['offerItems'][0]['services'][0]['segments'][0]['flightSegment']['carrierCode'] ?? 'Unknown',
                'flight_number' => $offer['offerItems'][0]['services'][0]['segments'][0]['flightSegment']['number'] ?? 'Unknown',
                'origin' => $offer['offerItems'][0]['services'][0]['segments'][0]['flightSegment']['departure']['iataCode'] ?? null,
                'destination' => $offer['offerItems'][0]['services'][0]['segments'][0]['flightSegment']['arrival']['iataCode'] ?? null,
                'departure_time' => $offer['offerItems'][0]['services'][0]['segments'][0]['flightSegment']['departure']['at'] ?? null,
                'arrival_time' => $offer['offerItems'][0]['services'][0]['segments'][0]['flightSegment']['arrival']['at'] ?? null,
                'duration' => $offer['offerItems'][0]['services'][0]['segments'][0]['duration'] ?? null,
                'price' => $offer['price']['total'] ?? 0,
                'currency' => $offer['price']['currency'] ?? 'RUB',
                'stops' => count($offer['offerItems'][0]['services'][0]['segments']) - 1,
                'class' => $offer['offerItems'][0]['services'][0]['segments'][0]['flightSegment']['cabinClass'] ?? 'Economy',
            ];
        }
        
        return [
            'flights' => $flights,
            'count' => count($flights),
            'currency' => 'RUB',
        ];
    }

    /**
     * Normalize Sabre results to standard format.
     */
    private function normalizeSabreResults(array $data): array
    {
        $flights = [];
        
        foreach ($data['OTA_AirLowFareSearchRS']['PricedItineraries'] ?? [] as $itinerary) {
            $flights[] = [
                'id' => $itinerary['SequenceNumber'] ?? null,
                'provider' => 'sabre',
                'airline' => $itinerary['AirItinerary']['OriginDestinationOptions'][0]['FlightSegment'][0]['OperatingAirline']['Code'] ?? 'Unknown',
                'flight_number' => $itinerary['AirItinerary']['OriginDestinationOptions'][0]['FlightSegment'][0]['FlightNumber'] ?? 'Unknown',
                'origin' => $itinerary['AirItinerary']['OriginDestinationOptions'][0]['FlightSegment'][0]['DepartureAirport']['LocationCode'] ?? null,
                'destination' => $itinerary['AirItinerary']['OriginDestinationOptions'][0]['FlightSegment'][0]['ArrivalAirport']['LocationCode'] ?? null,
                'departure_time' => $itinerary['AirItinerary']['OriginDestinationOptions'][0]['FlightSegment'][0]['DepartureDateTime'] ?? null,
                'arrival_time' => $itinerary['AirItinerary']['OriginDestinationOptions'][0]['FlightSegment'][0]['ArrivalDateTime'] ?? null,
                'duration' => $itinerary['AirItinerary']['OriginDestinationOptions'][0]['FlightSegment'][0]['ElapsedTime'] ?? null,
                'price' => $itinerary['AirItineraryPricingInfo']['ItinTotalFare']['TotalFare']['Amount'] ?? 0,
                'currency' => $itinerary['AirItineraryPricingInfo']['ItinTotalFare']['TotalFare']['CurrencyCode'] ?? 'RUB',
                'stops' => count($itinerary['AirItinerary']['OriginDestinationOptions'][0]['FlightSegment']) - 1,
                'class' => 'Economy',
            ];
        }
        
        return [
            'flights' => $flights,
            'count' => count($flights),
            'currency' => 'RUB',
        ];
    }

    /**
     * Normalize Skyscanner results to standard format.
     */
    private function normalizeSkyscannerResults(array $data): array
    {
        $flights = [];
        
        foreach ($data['content']['results']['itineraries']['buckets'] ?? [] as $bucket) {
            foreach ($bucket['items'] ?? [] as $item) {
                $flights[] = [
                    'id' => $item['id'] ?? null,
                    'provider' => 'skyscanner',
                    'airline' => $item['legs'][0]['carriers']['marketing'][0]['name'] ?? 'Unknown',
                    'flight_number' => $item['legs'][0]['segments'][0]['flightNumber'] ?? 'Unknown',
                    'origin' => $item['legs'][0]['origin']['displayCode'] ?? null,
                    'destination' => $item['legs'][0]['destination']['displayCode'] ?? null,
                    'departure_time' => $item['legs'][0]['departure'] ?? null,
                    'arrival_time' => $item['legs'][0]['arrival'] ?? null,
                    'duration' => $item['legs'][0]['duration'] ?? null,
                    'price' => $item['price']['amount'] ?? 0,
                    'currency' => $item['price']['currency'] ?? 'RUB',
                    'stops' => count($item['legs'][0]['segments']) - 1,
                    'class' => 'Economy',
                ];
            }
        }
        
        return [
            'flights' => $flights,
            'count' => count($flights),
            'currency' => 'RUB',
        ];
    }

    /**
     * Get fallback results when external APIs fail.
     */
    private function getFallbackResults(array $params): array
    {
        return [
            'flights' => [],
            'count' => 0,
            'currency' => 'RUB',
            'error' => 'External flight search unavailable. Please try again later.',
        ];
    }

    /**
     * Generate cache key for search parameters.
     */
    private function getCacheKey(array $params): string
    {
        return 'flight_search:' . md5(json_encode($params));
    }
}
