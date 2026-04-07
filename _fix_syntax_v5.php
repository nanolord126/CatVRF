<?php
/**
 * Phase 5 — Final fix for remaining 16 broken files
 * 
 * Strategy:
 * A) Minified services: replace ]);}return with ]);return (move return inside closure)
 * B) Truncated files: rewrite completely
 * C) Individual broken files: targeted fixes
 */

$fixed = 0;

// ======================================================================
// GROUP A: Fix ]);}return → ]);return in minified/formatted service files
// This removes the spurious } that closes the closure body too early
// ======================================================================

$serviceFiles = [
    'app/Domains/OfficeCatering/Services/OfficeCateringService.php',
    'app/Domains/PartySupplies/Gifts/Services/GiftsService.php',
    'app/Domains/PartySupplies/Services/PartySuppliesService.php',
    'app/Domains/Pharmacy/MedicalSupplies/Services/MedicalSuppliesService.php',
    'app/Domains/RealEstate/OfficeRentals/Services/OfficeRentalsService.php',
    'app/Domains/RealEstate/ShopRentals/Services/ShopRentalsService.php',
    'app/Domains/ShortTermRentals/Services/ShortTermRentalsService.php',
    'app/Domains/Tickets/EntertainmentBooking/Services/EntertainmentBookingService.php',
    'app/Domains/ToysAndGames/Toys/Services/ToysService.php',
    'app/Domains/Travel/Services/TravelBookingService.php',
    'app/Domains/VeganProducts/Services/VeganProductService.php',
];

foreach ($serviceFiles as $f) {
    if (!file_exists($f)) { echo "SKIP: $f\n"; continue; }
    $c = file_get_contents($f);
    $orig = $c;
    
    // Fix: ]);}return → ]);return  (remove } between ]); and return)
    $c = str_replace(']);}return', ']);return', $c);
    
    // Also fix formatted version with newlines:
    // ]);
    //                 }
    // return
    $c = preg_replace('/\]\);\s*\}\s*\n\s*return/', ']);\n                return', $c);
    
    if ($c !== $orig) {
        file_put_contents($f, $c);
        $fixed++;
        echo "FIX A: " . basename($f) . "\n";
    } else {
        echo "NO CHANGE: " . basename($f) . "\n";
    }
}

// ======================================================================
// GROUP B: MedicalAppointmentService — fix brace structure near end
// ======================================================================
$f = 'app/Domains/Medical/Services/MedicalAppointmentService.php';
if (file_exists($f)) {
    $c = file_get_contents($f);
    
    // Fix: remove standalone } and }); and replace with just });
    // Current: ...info(...);\n            return true;\n        }\n});\n        }
    // Should:  ...info(...);\n                return true;\n            });\n        }
    $c = preg_replace(
        '/(\$this->logger->info\("Medical: appointment finished \+ payout".*?\]);\s*\n\s*return true;\s*\n\s*\}\s*\n\}\);\s*\n\s*\}/',
        '$1;' . "\r\n                return true;\r\n            });\r\n        }",
        $c
    );
    
    // Fallback simpler fix
    $c = str_replace("return true;\r\n        }\r\n});\r\n        }", "return true;\r\n            });\r\n        }", $c);
    $c = str_replace("return true;\n        }\n});\n        }", "return true;\n            });\n        }", $c);
    
    file_put_contents($f, $c);
    echo "FIX B: MedicalAppointmentService\n";
    $fixed++;
}

// ======================================================================
// GROUP C: PetBoardingService — rewrite garbled getAvailableRooms + cancel
// ======================================================================
$f = 'app/Domains/Pet/PetServices/Services/PetBoardingService.php';
if (file_exists($f)) {
    $c = file_get_contents($f);
    
    // Replace the garbled section from "public function getAvailableRooms" to end of class
    $garbledStart = strpos($c, 'public function getAvailableRooms');
    if ($garbledStart !== false) {
        // Find the last } of the class
        $replacement = <<<'PHP'
public function getAvailableRooms(string $checkInDate, string $checkOutDate): Collection
        {
            $roomTypes = ['standard', 'premium', 'vip'];
            $available = collect();

            foreach ($roomTypes as $type) {
                $bookedCount = PetBoarding::where('room_type', $type)
                    ->where('status', '!=', 'cancelled')
                    ->where('check_in_date', '<=', $checkOutDate)
                    ->where('check_out_date', '>=', $checkInDate)
                    ->count();

                $available->push([
                    'room_type' => $type,
                    'booked' => $bookedCount,
                ]);
            }

            return $available;
        }

        public function cancelReservation(int $reservationId, string $reason = ''): bool
        {
            $reservation = PetBoarding::findOrFail($reservationId);

            $this->logger->info('PetBoardingService: Cancelling reservation', [
                'correlation_id' => $reservation->correlation_id,
                'reservation_id' => $reservationId,
                'reason' => $reason,
            ]);

            $this->fraud->check(userId: $this->guard->id() ?? 0, operationType: 'mutation', amount: 0, correlationId: $reservation->correlation_id ?? '');

            return $this->db->transaction(function () use ($reservation, $reason) {
                $reservation->update([
                    'status' => 'cancelled',
                    'cancellation_reason' => $reason,
                ]);

                return true;
            });
        }
}
PHP;
        $c = substr($c, 0, $garbledStart) . $replacement . "\n";
        file_put_contents($f, $c);
        echo "FIX C: PetBoardingService\n";
        $fixed++;
    }
}

// ======================================================================
// GROUP D: RateLimitBloggers — rewrite truncated file
// ======================================================================
$f = 'app/Domains/Education/Bloggers/Http/Middleware/RateLimitBloggers.php';
file_put_contents($f, <<<'PHP'
<?php

declare(strict_types=1);

namespace App\Domains\Education\Bloggers\Http\Middleware;

use Closure;
use Illuminate\Config\Repository as ConfigRepository;
use Illuminate\Contracts\Auth\Guard;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Psr\Log\LoggerInterface;

final class RateLimitBloggers
{
    public function __construct(
        private readonly Guard $guard,
        private readonly \Illuminate\Cache\CacheManager $cache,
        private readonly ConfigRepository $config,
        private readonly LoggerInterface $logger,
    ) {}

    public function handle(Request $request, Closure $next): Response
    {
        $userId = $this->guard->id();
        if (!$userId) {
            return $next($request);
        }

        $operation = $this->getOperationType($request);

        if (!$operation) {
            return $next($request);
        }

        $limit = $this->getLimit($operation);
        $window = $this->getWindow($operation);

        $key = "rate_limit:{$operation}:{$userId}";
        $attempts = (int) $this->cache->get($key, 0);

        if ($attempts >= $limit) {
            $this->logger->warning('Blogger rate limit exceeded', [
                'user_id' => $userId,
                'operation' => $operation,
                'attempts' => $attempts,
                'limit' => $limit,
                'correlation_id' => $request->header('X-Correlation-ID', ''),
            ]);

            return new Response(
                json_encode(['error' => 'Too many requests', 'retry_after' => $window]),
                429,
                ['Content-Type' => 'application/json', 'Retry-After' => (string) $window]
            );
        }

        $this->cache->put($key, $attempts + 1, $window);

        return $next($request);
    }

    private function getOperationType(Request $request): ?string
    {
        $method = $request->method();
        $path = $request->path();

        if (str_contains($path, 'posts') && $method === 'POST') {
            return 'blogger_post_create';
        }
        if (str_contains($path, 'comments') && $method === 'POST') {
            return 'blogger_comment_create';
        }

        return null;
    }

    private function getLimit(string $operation): int
    {
        return match ($operation) {
            'blogger_post_create' => 10,
            'blogger_comment_create' => 30,
            default => 60,
        };
    }

    private function getWindow(string $operation): int
    {
        return match ($operation) {
            'blogger_post_create' => 3600,
            'blogger_comment_create' => 600,
            default => 60,
        };
    }
}
PHP
);
echo "FIX D: RateLimitBloggers (rewritten)\n";
$fixed++;

// ======================================================================
// GROUP E: PartnerStoreAPIIntegration — rewrite truncated file
// ======================================================================
$f = 'app/Domains/GroceryAndDelivery/Integrations/PartnerStoreAPIIntegration.php';
file_put_contents($f, <<<'PHP'
<?php

declare(strict_types=1);

namespace App\Domains\GroceryAndDelivery\Integrations;

use Illuminate\Http\Client\Factory;
use Psr\Log\LoggerInterface;

final class PartnerStoreAPIIntegration
{
    private const PROVIDER_ENDPOINTS = [
        'magnit' => 'https://api.magnit.com/v1',
        'pyaterochka' => 'https://api.pyaterochka.com/v1',
        'vkusvill' => 'https://api.vkusvill.com/v1',
    ];

    public function __construct(
        private readonly Factory $http,
        private readonly FraudControlService $fraud,
        private readonly \Illuminate\Database\DatabaseManager $db,
        private readonly LoggerInterface $logger,
    ) {}

    /**
     * Синхронизировать товары и остатки из внешнего магазина.
     */
    public function syncInventory(
        GroceryStore $store,
        string $correlationId,
    ): array {
        if (!$store->api_provider || !$store->api_token) {
            throw new \RuntimeException('Store API credentials not configured');
        }

        $endpoint = self::PROVIDER_ENDPOINTS[$store->api_provider] ?? null;
        if (!$endpoint) {
            throw new \RuntimeException("Unsupported API provider: {$store->api_provider}");
        }

        try {
            $response = $this->http->withToken($store->api_token)
                ->timeout(30)
                ->get("{$endpoint}/catalog/products", [
                    'store_id' => $store->id,
                    'limit' => 1000,
                ]);

            if (!$response->successful()) {
                throw new \RuntimeException("API call failed: {$response->status()}");
            }

            $products = $response->json('data', []);

            $this->logger->info('PartnerStoreAPI: inventory synced', [
                'store_id' => $store->id,
                'products_count' => count($products),
                'correlation_id' => $correlationId,
            ]);

            return [
                'success' => true,
                'products_count' => count($products),
                'products' => $products,
            ];
        } catch (\Throwable $e) {
            $this->logger->error('PartnerStoreAPI: sync failed', [
                'store_id' => $store->id,
                'error' => $e->getMessage(),
                'correlation_id' => $correlationId,
            ]);

            return [
                'success' => false,
                'error' => $e->getMessage(),
            ];
        }
    }
}
PHP
);
echo "FIX E: PartnerStoreAPIIntegration (rewritten)\n";
$fixed++;

// ======================================================================
// GROUP F: RouteOptimizationService — rewrite truncated file
// ======================================================================
$f = 'app/Domains/GroceryAndDelivery/Integrations/RouteOptimizationService.php';
file_put_contents($f, <<<'PHP'
<?php

declare(strict_types=1);

namespace App\Domains\GroceryAndDelivery\Integrations;

use Illuminate\Http\Client\Factory;
use Psr\Log\LoggerInterface;

/**
 * Оптимизация маршрутов доставки.
 * Использует OSRM (Open Source Routing Machine) или Yandex.Maps API.
 */
final readonly class RouteOptimizationService
{
    private const OSRM_ENDPOINT = 'http://router.project-osrm.org/route/v1';

    public function __construct(
        private Factory $http,
        private FraudControlService $fraud,
        private \Illuminate\Database\DatabaseManager $db,
        private LoggerInterface $logger,
    ) {}

    /**
     * Получить оптимальный маршрут для доставки.
     *
     * @param  float  $storeLat    Широта магазина
     * @param  float  $storeLon    Долгота магазина
     * @param  array  $deliveries  Список точек доставки [{lat, lon, order_id}]
     * @param  string $correlationId
     * @return array  Маршрут с дистанцией и временем
     */
    public function optimizeRoute(
        float $storeLat,
        float $storeLon,
        array $deliveries,
        string $correlationId,
    ): array {
        if (count($deliveries) === 0) {
            return [
                'success' => true,
                'route' => [],
                'distance' => 0,
                'duration' => 0,
            ];
        }

        try {
            $coordinates = ["{$storeLon},{$storeLat}"];

            foreach ($deliveries as $delivery) {
                $coordinates[] = "{$delivery['lon']},{$delivery['lat']}";
            }

            $coordinateString = implode(';', $coordinates);

            $response = $this->http->timeout(30)->get(
                self::OSRM_ENDPOINT . "/driving/{$coordinateString}",
                [
                    'overview' => 'full',
                    'steps' => 'true',
                    'geometries' => 'geojson',
                ]
            );

            if (!$response->successful()) {
                throw new \RuntimeException("OSRM request failed: {$response->status()}");
            }

            $data = $response->json();
            $route = $data['routes'][0] ?? [];

            $this->logger->info('RouteOptimization: route calculated', [
                'deliveries_count' => count($deliveries),
                'distance_meters' => $route['distance'] ?? 0,
                'duration_seconds' => $route['duration'] ?? 0,
                'correlation_id' => $correlationId,
            ]);

            return [
                'success' => true,
                'route' => $route,
                'distance' => $route['distance'] ?? 0,
                'duration' => $route['duration'] ?? 0,
                'waypoints' => $data['waypoints'] ?? [],
            ];
        } catch (\Throwable $e) {
            $this->logger->error('RouteOptimization: failed', [
                'error' => $e->getMessage(),
                'correlation_id' => $correlationId,
            ]);

            return [
                'success' => false,
                'error' => $e->getMessage(),
            ];
        }
    }
}
PHP
);
echo "FIX F: RouteOptimizationService (rewritten)\n";
$fixed++;

echo "\n=== Phase 5 fixed: $fixed ===\n";

// VERIFY ALL
echo "\n--- Final verification ---\n";
$broken = file('_broken_files.txt', FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
$ok = 0; $err = 0;
foreach ($broken as $f2) {
    $f2 = trim($f2);
    if (!file_exists($f2)) continue;
    $out = shell_exec("php -l " . escapeshellarg($f2) . " 2>&1");
    if (strpos($out, 'Parse error') !== false || strpos($out, 'Fatal error') !== false) {
        $err++;
        $line = preg_match('/on line (\d+)/', $out, $m) ? " L{$m[1]}" : '';
        $shortErr = preg_replace('/^.*?(Parse error|Fatal error)/', '$1', trim($out));
        $shortErr = preg_replace('/\s+/', ' ', $shortErr);
        echo "ERR: " . basename($f2) . "$line → " . substr($shortErr, 0, 100) . "\n";
    } else {
        $ok++;
    }
}
echo "\n=== RESULT: OK=$ok BROKEN=$err ===\n";
