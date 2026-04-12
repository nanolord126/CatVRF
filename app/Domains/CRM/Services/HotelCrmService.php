<?php

declare(strict_types=1);

namespace App\Domains\CRM\Services;


use Illuminate\Support\Facades\DB;
use App\Domains\CRM\DTOs\CreateCrmInteractionDto;
use App\Domains\CRM\Models\CrmClient;
use App\Domains\CRM\Models\CrmHotelGuestProfile;
use App\Services\AuditService;
use App\Services\FraudControlService;
use Illuminate\Database\DatabaseManager;
use Illuminate\Support\Collection;
use Illuminate\Log\LogManager;

/**
 * HotelCrmService — CRM-логика для вертикали Hotels (Отели, Апартаменты).
 *
 * Гостевые профили, предпочтения по номерам, smoking/pets/VIP,
 * напоминания о заезде (48ч/24ч/2ч), отзыв после выезда,
 * реактивация (90/180 дней), предложения апгрейда.
 *
 * Канон CatVRF 2026 — PRODUCTION MANDATORY.
 */
final readonly class HotelCrmService
{
    public function __construct(
        private CrmService $crmService,
        private FraudControlService $fraud,
        private AuditService $audit,
        private DatabaseManager $db,
        private LogManager $logger,
    
    ) {}

    /**
     * Создать гостевой профиль для CRM-клиента.
     */
    public function createGuestProfile(
        int $crmClientId,
        int $tenantId,
        string $correlationId,
        ?string $roomPreference = null,
        ?string $bedPreference = null,
        ?string $floorPreference = null,
        bool $smokingRoom = false,
        bool $petsAllowed = false,
        bool $isVip = false,
        ?array $dietaryPreferences = null,
        ?string $passportNumber = null,
        ?string $notes = null
    ): CrmHotelGuestProfile {
        $this->fraud->check(
            userId: 0,
            operationType: 'crm_hotel_profile_create',
            amount: 0,
            correlationId: $correlationId
    );

        return $this->db->transaction(function () use (
            $crmClientId,
            $tenantId,
            $correlationId,
            $roomPreference,
            $bedPreference,
            $floorPreference,
            $smokingRoom,
            $petsAllowed,
            $isVip,
            $dietaryPreferences,
            $passportNumber,
            $notes
    ): CrmHotelGuestProfile {
            $profile = CrmHotelGuestProfile::query()->create([
                'crm_client_id' => $crmClientId,
                'tenant_id' => $tenantId,
                'correlation_id' => $correlationId,
                'room_preference' => $roomPreference,
                'bed_preference' => $bedPreference,
                'floor_preference' => $floorPreference,
                'smoking_room' => $smokingRoom,
                'pets_allowed' => $petsAllowed,
                'is_vip' => $isVip,
                'dietary_preferences' => $dietaryPreferences ?? [],
                'passport_number' => $passportNumber,
                'total_stays' => 0,
                'total_nights' => 0,
                'average_rating' => null,
                'notes' => $notes,
            ]);

            $this->logger->info('Hotel CRM guest profile created', [
                'profile_id' => $profile->id,
                'client_id' => $crmClientId,
                'is_vip' => $isVip,
                'correlation_id' => $correlationId,
            ]);

            $this->audit->log(
                'crm_hotel_profile_created',
                CrmHotelGuestProfile::class,
                $profile->id,
                [],
                $profile->toArray(),
                $correlationId
    );

            return $profile;
        });
    }

    /**
     * Записать проживание (stay) гостя.
     */
    public function recordStay(
        CrmClient $client,
        string $roomType,
        int $nights,
        float $totalAmount,
        ?float $rating,
        string $correlationId,
        ?string $feedback = null
    ): void {
        $this->db->transaction(function () use (
            $client,
            $roomType,
            $nights,
            $totalAmount,
            $rating,
            $correlationId,
            $feedback
    ): void {
            // Записываем interaction
            $this->crmService->recordInteraction(
                new CreateCrmInteractionDto(
                    crmClientId: $client->id,
                    tenantId: $client->tenant_id,
                    correlationId: $correlationId,
                    type: 'order',
                    channel: 'in_person',
                    direction: 'inbound',
                    content: "Проживание: {$roomType}, {$nights} ночей",
                    metadata: [
                        'room_type' => $roomType,
                        'nights' => $nights,
                        'amount' => $totalAmount,
                        'rating' => $rating,
                        'feedback' => $feedback,
                    ]
    )
    );

            // Обновляем гостевой профиль
            $profile = $client->hotelGuestProfile;

            if ($profile instanceof CrmHotelGuestProfile) {
                $profile->increment('total_stays');
                $profile->increment('total_nights', $nights);
                $profile->update(['last_stay_at' => now()]);

            $this->logger->channel('audit')->info(class_basename(static::class) . ': Record updated', [
                'id' => $profile->id ?? null,
                'correlation_id' => $correlationId,
            ]);

                if ($rating !== null) {
                    $this->updateAverageRating($profile, $rating);
                }
            }

            // Обновляем финансовые данные клиента
            $client->increment('total_orders');
            $client->increment('total_spent', $totalAmount);
            $client->update([
                'last_order_at' => now(),
                'average_order_value' => $client->total_orders > 0
                    ? round(($client->total_spent) / $client->total_orders, 2)
                    : $totalAmount,
            ]);

            $this->logger->channel('audit')->info(class_basename(static::class) . ': Record updated', [
                'id' => $client->id ?? null,
                'correlation_id' => $correlationId,
            ]);

            $this->logger->info('Hotel CRM stay recorded', [
                'client_id' => $client->id,
                'room_type' => $roomType,
                'nights' => $nights,
                'amount' => $totalAmount,
                'correlation_id' => $correlationId,
            ]);
        });
    }

    /**
     * Получить гостей для напоминания о заезде (check-in reminder).
     * Вызывается Scheduler'ом для отправки напоминаний за 48ч, 24ч и 2ч до заезда.
     */
    public function getGuestsForCheckinReminder(
        int $tenantId,
        int $hoursBefore,
        string $correlationId
    ): Collection {
        $targetTime = now()->addHours($hoursBefore);
        $windowStart = $targetTime->copy()->subMinutes(30);
        $windowEnd = $targetTime->copy()->addMinutes(30);

        $this->logger->info('Searching guests for checkin reminder', [
            'tenant_id' => $tenantId,
            'hours_before' => $hoursBefore,
            'window' => [$windowStart->toIso8601String(), $windowEnd->toIso8601String()],
            'correlation_id' => $correlationId,
        ]);

        // Ищем клиентов с запланированным визитом через vertical_data
        return CrmClient::query()
            ->forTenant($tenantId)
            ->byVertical('hotel')
            ->whereNotNull('vertical_data')
            ->with('hotelGuestProfile')
            ->get()
            ->filter(function (CrmClient $client) use ($windowStart, $windowEnd): bool {
                $verticalData = $client->vertical_data ?? [];
                $checkinDate = $verticalData['next_checkin'] ?? null;

                if ($checkinDate === null) {
                    return false;
                }

                $checkin = \Carbon\Carbon::parse($checkinDate);

                return $checkin->between($windowStart, $windowEnd);
            });
    }

    /**
     * Получить гостей для отзыва после выезда (24ч после checkout).
     */
    public function getGuestsForPostCheckoutReview(
        int $tenantId,
        string $correlationId
    ): Collection {
        return CrmClient::query()
            ->forTenant($tenantId)
            ->byVertical('hotel')
            ->whereNotNull('vertical_data')
            ->with('hotelGuestProfile')
            ->get()
            ->filter(function (CrmClient $client): bool {
                $verticalData = $client->vertical_data ?? [];
                $checkoutDate = $verticalData['last_checkout'] ?? null;

                if ($checkoutDate === null) {
                    return false;
                }

                $checkout = \Carbon\Carbon::parse($checkoutDate);
                $hoursSince = $checkout->diffInHours(now());

                return $hoursSince >= 23 && $hoursSince <= 25;
            });
    }

    /**
     * Получить гостей для реактивации (90+ или 180+ дней без визита).
     */
    public function getGuestsForReactivation(int $tenantId, int $daysInactive = 90): Collection
    {
        return CrmClient::query()
            ->forTenant($tenantId)
            ->byVertical('hotel')
            ->sleeping($daysInactive)
            ->with('hotelGuestProfile')
            ->orderByDesc('total_spent')
            ->get();
    }

    /**
     * Определить кандидатов на апгрейд номера (лояльные гости перед заездом).
     */
    public function getUpgradeCandidates(int $tenantId, string $correlationId): Collection
    {
        return CrmClient::query()
            ->forTenant($tenantId)
            ->byVertical('hotel')
            ->where('total_orders', '>=', 3)
            ->whereHas('hotelGuestProfile', function ($q): void {
                $q->where('total_stays', '>=', 2)
                    ->where('average_rating', '>=', 4.0);
            })
            ->with('hotelGuestProfile')
            ->get()
            ->filter(function (CrmClient $client): bool {
                $verticalData = $client->vertical_data ?? [];
                $nextCheckin = $verticalData['next_checkin'] ?? null;

                if ($nextCheckin === null) {
                    return false;
                }

                $hoursUntilCheckin = now()->diffInHours(\Carbon\Carbon::parse($nextCheckin), false);

                return $hoursUntilCheckin > 0 && $hoursUntilCheckin <= 48;
            });
    }

    /**
     * Пересчитать средний рейтинг гостя.
     */
    private function updateAverageRating(CrmHotelGuestProfile $profile, float $newRating): void
    {
        $totalStays = $profile->total_stays;
        $currentAvg = $profile->average_rating ?? $newRating;

        $newAverage = $totalStays > 1
            ? (($currentAvg * ($totalStays - 1)) + $newRating) / $totalStays
            : $newRating;

        $profile->update(['average_rating' => round($newAverage, 2)]);

            $this->logger->channel('audit')->info(class_basename(static::class) . ': Record updated', [
                'id' => $profile->id ?? null,
                'correlation_id' => $correlationId,
            ]);
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
