<?php

declare(strict_types=1);

namespace App\Domains\CRM\Services;


use Illuminate\Support\Facades\DB;
use App\Domains\CRM\DTOs\CreateCrmInteractionDto;
use App\Domains\CRM\Models\CrmClient;
use App\Domains\CRM\Models\CrmTaxiProfile;
use App\Services\AuditService;
use App\Services\FraudControlService;
use Illuminate\Database\DatabaseManager;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;
use Illuminate\Log\LogManager;

/**
 * TaxiCrmService — CRM-логика для вертикали Такси/Трансфер.
 *
 * Частые маршруты, корпоративные аккаунты, предпочтения класса,
 * паттерны поездок, рейтинг водителей.
 *
 * Канон CatVRF 2026 — PRODUCTION MANDATORY.
 */
final readonly class TaxiCrmService
{
    public function __construct(
        private CrmService $crmService,
        private FraudControlService $fraud,
        private AuditService $audit,
        private DatabaseManager $db,
        private LogManager $logger,
    
    ) {}

    /**
     * Создать taxi-профиль CRM-клиента.
     */
    public function createTaxiProfile(
        int $crmClientId,
        int $tenantId,
        string $correlationId,
        ?string $preferredCarClass = null,
        ?string $preferredPayment = null,
        bool $isCorporate = false,
        ?string $corporateAccountId = null,
        ?float $monthlyRideBudget = null,
        bool $needsChildSeat = false,
        bool $needsPetFriendly = false,
        ?string $notes = null
    ): CrmTaxiProfile {
        $this->fraud->check(
            userId: 0,
            operationType: 'crm_taxi_profile_create',
            amount: 0,
            correlationId: $correlationId
    );

        return $this->db->transaction(function () use (
            $crmClientId, $tenantId, $correlationId, $preferredCarClass,
            $preferredPayment, $isCorporate, $corporateAccountId,
            $monthlyRideBudget, $needsChildSeat, $needsPetFriendly, $notes
    ): CrmTaxiProfile {
            $profile = CrmTaxiProfile::query()->create([
                'crm_client_id' => $crmClientId,
                'tenant_id' => $tenantId,
                'correlation_id' => $correlationId,
                'preferred_car_class' => $preferredCarClass,
                'preferred_payment' => $preferredPayment,
                'is_corporate' => $isCorporate,
                'corporate_account_id' => $corporateAccountId,
                'monthly_ride_budget' => $monthlyRideBudget,
                'needs_child_seat' => $needsChildSeat,
                'needs_pet_friendly' => $needsPetFriendly,
                'frequent_routes' => [],
                'home_address' => [],
                'work_address' => [],
                'saved_addresses' => [],
                'preferred_drivers' => [],
                'ride_time_patterns' => [],
                'total_rides' => 0,
                'total_spent_rides' => 0,
                'avg_rating_given' => 0,
                'notes' => $notes,
            ]);

            $this->logger->info('Taxi CRM profile created', [
                'profile_id' => $profile->id,
                'client_id' => $crmClientId,
                'is_corporate' => $isCorporate,
                'correlation_id' => $correlationId,
            ]);

            $this->audit->log(
                'crm_taxi_profile_created',
                CrmTaxiProfile::class,
                $profile->id,
                [],
                $profile->toArray(),
                $correlationId
    );

            return $profile;
        });
    }

    /**
     * Записать поездку.
     */
    public function recordRide(
        CrmTaxiProfile $profile,
        array $pickupLocation,
        array $dropoffLocation,
        float $amount,
        string $correlationId,
        ?string $driverId = null,
        ?int $durationMinutes = null,
        ?float $distanceKm = null,
        ?int $rating = null
    ): CrmTaxiProfile {
        return $this->db->transaction(function () use (
            $profile, $pickupLocation, $dropoffLocation, $amount,
            $correlationId, $driverId, $durationMinutes, $distanceKm, $rating
    ): CrmTaxiProfile {
            $routes = $profile->frequent_routes ?? [];
            $routeKey = md5(json_encode($pickupLocation) . json_encode($dropoffLocation));
            $routeFound = false;

            foreach ($routes as &$route) {
                if (($route['key'] ?? '') === $routeKey) {
                    $route['count'] = ($route['count'] ?? 0) + 1;
                    $route['last_used'] = now()->toDateString();
                    $routeFound = true;
                    break;
                }
            }
            unset($route);

            if (!$routeFound) {
                $routes[] = [
                    'key' => $routeKey,
                    'pickup' => $pickupLocation,
                    'dropoff' => $dropoffLocation,
                    'count' => 1,
                    'last_used' => now()->toDateString(),
                ];
            }

            $patterns = $profile->ride_time_patterns ?? [];
            $hourKey = (string) now()->hour;
            $patterns[$hourKey] = ($patterns[$hourKey] ?? 0) + 1;

            $totalRides = ($profile->total_rides ?? 0) + 1;
            $totalSpent = ($profile->total_spent_rides ?? 0) + $amount;

            $updateData = [
                'frequent_routes' => $routes,
                'ride_time_patterns' => $patterns,
                'total_rides' => $totalRides,
                'total_spent_rides' => $totalSpent,
            ];

            if ($driverId !== null) {
                $drivers = $profile->preferred_drivers ?? [];
                if (!in_array($driverId, $drivers, true) && $rating !== null && $rating >= 4) {
                    $drivers[] = $driverId;
                    $updateData['preferred_drivers'] = $drivers;
                }
            }

            if ($rating !== null) {
                $currentAvg = $profile->avg_rating_given ?? 0.0;
                $updateData['avg_rating_given'] = round(
                    (($currentAvg * ($totalRides - 1)) + $rating) / $totalRides,
                    2
    );
            }

            $profile->update($updateData);

            $this->crmService->recordInteraction(
                new CreateCrmInteractionDto(
                    crmClientId: $profile->crm_client_id,
                    tenantId: $profile->tenant_id,
                    correlationId: $correlationId,
                    type: 'order',
                    channel: 'app',
                    direction: 'inbound',
                    content: 'Поездка на такси',
                    metadata: [
                        'pickup' => $pickupLocation,
                        'dropoff' => $dropoffLocation,
                        'amount' => $amount,
                        'driver_id' => $driverId,
                        'duration_min' => $durationMinutes,
                        'distance_km' => $distanceKm,
                        'rating' => $rating,
                    ]
    )
    );

            $client = $profile->client;

            if ($client instanceof CrmClient) {
                $client->increment('total_orders');
                $client->increment('total_spent', $amount);
                $client->update(['last_order_at' => now()]);

            $this->logger->channel('audit')->info(class_basename(static::class) . ': Record updated', [
                'id' => $client->id ?? null,
                'correlation_id' => $correlationId,
            ]);
            }

            return $profile->fresh() ?? $profile;
        });
    }

    /**
     * Сохранить адрес (дом, работа, избранное).
     */
    public function saveAddress(
        CrmTaxiProfile $profile,
        string $type,
        array $address,
        string $correlationId
    ): CrmTaxiProfile {
        return $this->db->transaction(function () use ($profile, $type, $address, $correlationId): CrmTaxiProfile {
            $data = match ($type) {
                'home' => ['home_address' => $address],
                'work' => ['work_address' => $address],
                default => [],
            };

            if ($type === 'saved') {
                $saved = $profile->saved_addresses ?? [];
                $saved[] = $address;
                $data = ['saved_addresses' => $saved];
            }

            $profile->update($data);

            return $profile->fresh() ?? $profile;
        });
    }

    /**
     * Анализ паттернов поездок (пиковые часы).
     */
    public function analyzeRidePatterns(CrmTaxiProfile $profile): array
    {
        $patterns = $profile->ride_time_patterns ?? [];

        if (count($patterns) === 0) {
            return ['status' => 'insufficient_data'];
        }

        arsort($patterns);
        $peakHours = array_slice(array_keys($patterns), 0, 3);

        $topRoutes = collect($profile->frequent_routes ?? [])
            ->sortByDesc('count')
            ->take(5)
            ->values()
            ->toArray();

        return [
            'total_rides' => $profile->total_rides,
            'total_spent' => $profile->total_spent_rides,
            'avg_ride_cost' => $profile->total_rides > 0
                ? round(($profile->total_spent_rides ?? 0) / $profile->total_rides, 2)
                : 0,
            'peak_hours' => $peakHours,
            'top_routes' => $topRoutes,
            'preferred_class' => $profile->preferred_car_class,
            'is_corporate' => $profile->is_corporate,
        ];
    }

    /**
     * «Спящие» taxi-клиенты.
     */
    public function getSleepingClients(int $tenantId, int $daysInactive = 14): Collection
    {
        return CrmClient::query()
            ->forTenant($tenantId)
            ->byVertical('taxi')
            ->sleeping($daysInactive)
            ->orderByDesc('total_spent')
            ->get();
    }

    /**
     * Выполнить операцию внутри транзакции.
     *
     * @template T
     * @param callable(): T $callback
     * @return T
     */
    protected function executeInTransaction(callable $callback): mixed
    {
        return DB::transaction($callback);
    }
}
