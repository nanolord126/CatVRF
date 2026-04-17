<?php

declare(strict_types=1);

namespace App\Domains\CRM\Services;


use Illuminate\Support\Facades\DB;
use App\Domains\CRM\DTOs\CreateCrmInteractionDto;
use App\Domains\CRM\Models\CrmClient;
use App\Domains\CRM\Models\CrmTravelProfile;
use App\Services\AuditService;
use App\Services\FraudControlService;
use Illuminate\Database\DatabaseManager;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;
use Illuminate\Log\LogManager;

/**
 * TravelCrmService — CRM-логика для вертикали Путешествия/Туризм.
 *
 * Паспорт, визы, история поездок, программы лояльности,
 * предпочтения авиакомпаний/отелей, компаньоны.
 *
 * Канон CatVRF 2026 — PRODUCTION MANDATORY.
 */
final readonly class TravelCrmService
{
    public function __construct(
        private CrmService $crmService,
        private FraudControlService $fraud,
        private AuditService $audit,
        private DatabaseManager $db,
        private LogManager $logger,
    
    ) {}

    /**
     * Создать travel-профиль CRM-клиента.
     */
    public function createTravelProfile(
        int $crmClientId,
        int $tenantId,
        string $correlationId,
        ?string $passportCountry = null,
        ?string $passportExpiresAt = null,
        array $travelPreferences = [],
        array $preferredDestinations = [],
        ?string $preferredAirline = null,
        ?string $preferredHotelChain = null,
        ?string $seatPreference = null,
        ?float $avgTripBudget = null,
        ?string $notes = null
    ): CrmTravelProfile {
        $this->fraud->check(
            userId: 0,
            operationType: 'crm_travel_profile_create',
            amount: 0,
            correlationId: $correlationId
    );

        return $this->db->transaction(function () use (
            $crmClientId, $tenantId, $correlationId, $passportCountry,
            $passportExpiresAt, $travelPreferences, $preferredDestinations,
            $preferredAirline, $preferredHotelChain, $seatPreference,
            $avgTripBudget, $notes
    ): CrmTravelProfile {
            $profile = CrmTravelProfile::query()->create([
                'crm_client_id' => $crmClientId,
                'tenant_id' => $tenantId,
                'correlation_id' => $correlationId,
                'passport_country' => $passportCountry,
                'passport_expires_at' => $passportExpiresAt,
                'visas' => [],
                'travel_preferences' => $travelPreferences,
                'preferred_destinations' => $preferredDestinations,
                'visited_countries' => [],
                'trip_history' => [],
                'preferred_airline' => $preferredAirline,
                'preferred_hotel_chain' => $preferredHotelChain,
                'seat_preference' => $seatPreference,
                'loyalty_programs' => [],
                'travel_companions' => [],
                'needs_transfer' => false,
                'needs_insurance' => false,
                'avg_trip_budget' => $avgTripBudget,
                'notes' => $notes,
            ]);

            $this->logger->info('Travel CRM profile created', [
                'profile_id' => $profile->id,
                'client_id' => $crmClientId,
                'correlation_id' => $correlationId,
            ]);

            $this->audit->log(
                'crm_travel_profile_created',
                CrmTravelProfile::class,
                $profile->id,
                [],
                $profile->toArray(),
                $correlationId
    );

            return $profile;
        });
    }

    /**
     * Записать поездку (завершённую).
     */
    public function recordTrip(
        CrmTravelProfile $profile,
        string $destination,
        string $country,
        float $amount,
        string $correlationId,
        ?string $departDate = null,
        ?string $returnDate = null,
        ?int $rating = null
    ): CrmTravelProfile {
        return $this->db->transaction(function () use (
            $profile, $destination, $country, $amount,
            $correlationId, $departDate, $returnDate, $rating
    ): CrmTravelProfile {
            $trips = $profile->trip_history ?? [];
            $trips[] = [
                'destination' => $destination,
                'country' => $country,
                'amount' => $amount,
                'depart_date' => $departDate,
                'return_date' => $returnDate,
                'rating' => $rating,
            ];

            $visited = $profile->visited_countries ?? [];
            if (!in_array($country, $visited, true)) {
                $visited[] = $country;
            }

            $profile->update([
                'trip_history' => $trips,
                'visited_countries' => $visited,
            ]);

            $this->logger->channel('audit')->info(class_basename(static::class) . ': Record updated', [
                'id' => $profile->id ?? null,
                'correlation_id' => $correlationId,
            ]);

            $this->crmService->recordInteraction(
                new CreateCrmInteractionDto(
                    crmClientId: $profile->crm_client_id,
                    tenantId: $profile->tenant_id,
                    correlationId: $correlationId,
                    type: 'order',
                    channel: 'marketplace',
                    direction: 'inbound',
                    content: "Поездка: {$destination}, {$country}",
                    metadata: [
                        'destination' => $destination,
                        'country' => $country,
                        'amount' => $amount,
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
     * Добавить визу.
     */
    public function addVisa(
        CrmTravelProfile $profile,
        string $country,
        string $type,
        string $expiresAt,
        string $correlationId
    ): CrmTravelProfile {
        return $this->db->transaction(function () use ($profile, $country, $type, $expiresAt, $correlationId): CrmTravelProfile {
            $visas = $profile->visas ?? [];
            $visas[] = [
                'country' => $country,
                'type' => $type,
                'expires_at' => $expiresAt,
                'added_at' => now()->toDateString(),
            ];

            $profile->update(['visas' => $visas]);

            $this->logger->channel('audit')->info(class_basename(static::class) . ': Record updated', [
                'id' => $profile->id ?? null,
                'correlation_id' => $correlationId,
            ]);

            $this->logger->info('Travel visa added', [
                'profile_id' => $profile->id,
                'country' => $country,
                'correlation_id' => $correlationId,
            ]);

            return $profile->fresh() ?? $profile;
        });
    }

    /**
     * Добавить программу лояльности.
     */
    public function addLoyaltyProgram(
        CrmTravelProfile $profile,
        string $programName,
        string $memberId,
        string $correlationId,
        ?string $tier = null
    ): CrmTravelProfile {
        return $this->db->transaction(function () use ($profile, $programName, $memberId, $correlationId, $tier): CrmTravelProfile {
            $programs = $profile->loyalty_programs ?? [];
            $programs[] = [
                'program' => $programName,
                'member_id' => $memberId,
                'tier' => $tier,
                'added_at' => now()->toDateString(),
            ];

            $profile->update(['loyalty_programs' => $programs]);

            return $profile->fresh() ?? $profile;
        });
    }

    /**
     * Клиенты с истекающим паспортом.
     */
    public function getExpiringPassports(int $tenantId, int $daysAhead = 90): Collection
    {
        return CrmTravelProfile::query()
            ->where('tenant_id', $tenantId)
            ->whereNotNull('passport_expires_at')
            ->whereBetween('passport_expires_at', [now(), now()->addDays($daysAhead)])
            ->with('client')
            ->orderBy('passport_expires_at')
            ->get();
    }

    /**
     * Клиенты с истекающими визами.
     */
    public function getExpiringVisas(int $tenantId, int $daysAhead = 60): Collection
    {
        return CrmTravelProfile::query()
            ->where('tenant_id', $tenantId)
            ->with('client')
            ->get()
            ->filter(function (CrmTravelProfile $profile) use ($daysAhead): bool {
                foreach ($profile->visas ?? [] as $visa) {
                    if (isset($visa['expires_at'])) {
                        $exp = \Carbon\Carbon::parse($visa['expires_at']);
                        if ($exp->isBetween(now(), now()->addDays($daysAhead))) {
                            return true;
                        }
                    }
                }
                return false;
            });
    }

    /**
     * «Спящие» travel-клиенты.
     */
    public function getSleepingClients(int $tenantId, int $daysInactive = 120): Collection
    {
        return CrmClient::query()
            ->forTenant($tenantId)
            ->byVertical('travel')
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
        return $this->db->transaction($callback);
    }
}
