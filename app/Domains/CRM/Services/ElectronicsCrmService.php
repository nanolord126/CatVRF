<?php

declare(strict_types=1);

namespace App\Domains\CRM\Services;


use Illuminate\Support\Facades\DB;
use App\Domains\CRM\DTOs\CreateCrmInteractionDto;
use App\Domains\CRM\Models\CrmClient;
use App\Domains\CRM\Models\CrmElectronicsProfile;
use App\Services\AuditService;
use App\Services\FraudControlService;
use Illuminate\Database\DatabaseManager;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;
use Illuminate\Log\LogManager;

/**
 * ElectronicsCrmService — CRM-логика для вертикали Электроника.
 *
 * Устройства, гарантии, trade-in, ремонт, предпочтения ОС/брендов,
 * уведомления о новинках, расширенная гарантия.
 *
 * Канон CatVRF 2026 — PRODUCTION MANDATORY.
 */
final readonly class ElectronicsCrmService
{
    public function __construct(
        private CrmService $crmService,
        private FraudControlService $fraud,
        private AuditService $audit,
        private DatabaseManager $db,
        private LogManager $logger,
    
    ) {}

    /**
     * Создать electronics-профиль CRM-клиента.
     */
    public function createElectronicsProfile(
        int $crmClientId,
        int $tenantId,
        string $correlationId,
        array $preferredBrands = [],
        array $preferredCategories = [],
        ?string $techLevel = null,
        ?string $preferredOs = null,
        bool $interestedInTradeIn = false,
        bool $wantsExtendedWarranty = false,
        bool $subscribedToNewReleases = false,
        ?string $notes = null
    ): CrmElectronicsProfile {
        $this->fraud->check(
            userId: 0,
            operationType: 'crm_electronics_profile_create',
            amount: 0,
            correlationId: $correlationId
    );

        return $this->db->transaction(function () use (
            $crmClientId, $tenantId, $correlationId, $preferredBrands,
            $preferredCategories, $techLevel, $preferredOs,
            $interestedInTradeIn, $wantsExtendedWarranty, $subscribedToNewReleases, $notes
    ): CrmElectronicsProfile {
            $profile = CrmElectronicsProfile::query()->create([
                'crm_client_id' => $crmClientId,
                'tenant_id' => $tenantId,
                'correlation_id' => $correlationId,
                'preferred_brands' => $preferredBrands,
                'preferred_categories' => $preferredCategories,
                'tech_level' => $techLevel,
                'preferred_os' => $preferredOs,
                'interested_in_trade_in' => $interestedInTradeIn,
                'wants_extended_warranty' => $wantsExtendedWarranty,
                'subscribed_to_new_releases' => $subscribedToNewReleases,
                'owned_devices' => [],
                'wishlist' => [],
                'warranty_tracking' => [],
                'trade_in_history' => [],
                'repair_history' => [],
                'notes' => $notes,
            ]);

            $this->logger->info('Electronics CRM profile created', [
                'profile_id' => $profile->id,
                'client_id' => $crmClientId,
                'preferred_os' => $preferredOs,
                'correlation_id' => $correlationId,
            ]);

            $this->audit->log(
                'crm_electronics_profile_created',
                CrmElectronicsProfile::class,
                $profile->id,
                [],
                $profile->toArray(),
                $correlationId
    );

            return $profile;
        });
    }

    /**
     * Зарегистрировать устройство клиента.
     */
    public function registerDevice(
        CrmElectronicsProfile $profile,
        string $deviceName,
        string $brand,
        string $correlationId,
        ?string $serialNumber = null,
        ?string $purchaseDate = null,
        ?string $warrantyExpiresAt = null
    ): CrmElectronicsProfile {
        return $this->db->transaction(function () use (
            $profile, $deviceName, $brand, $correlationId,
            $serialNumber, $purchaseDate, $warrantyExpiresAt
    ): CrmElectronicsProfile {
            $devices = $profile->owned_devices ?? [];
            $devices[] = [
                'name' => $deviceName,
                'brand' => $brand,
                'serial_number' => $serialNumber,
                'purchase_date' => $purchaseDate ?? now()->toDateString(),
                'warranty_expires_at' => $warrantyExpiresAt,
            ];

            $updateData = ['owned_devices' => $devices];

            if ($warrantyExpiresAt !== null) {
                $warranties = $profile->warranty_tracking ?? [];
                $warranties[] = [
                    'device' => $deviceName,
                    'serial_number' => $serialNumber,
                    'expires_at' => $warrantyExpiresAt,
                ];
                $updateData['warranty_tracking'] = $warranties;
            }

            $profile->update($updateData);

            $this->logger->info('Device registered', [
                'profile_id' => $profile->id,
                'device' => $deviceName,
                'brand' => $brand,
                'correlation_id' => $correlationId,
            ]);

            return $profile->fresh() ?? $profile;
        });
    }

    /**
     * Записать ремонт устройства.
     */
    public function recordRepair(
        CrmClient $client,
        string $deviceName,
        string $issue,
        float $amount,
        string $correlationId,
        ?string $resolution = null
    ): void {
        $this->db->transaction(function () use ($client, $deviceName, $issue, $amount, $correlationId, $resolution): void {
            $this->crmService->recordInteraction(
                new CreateCrmInteractionDto(
                    crmClientId: $client->id,
                    tenantId: $client->tenant_id,
                    correlationId: $correlationId,
                    type: 'visit',
                    channel: 'in_person',
                    direction: 'inbound',
                    content: "Ремонт: {$deviceName} — {$issue}",
                    metadata: [
                        'device' => $deviceName,
                        'issue' => $issue,
                        'resolution' => $resolution,
                        'amount' => $amount,
                    ]
    )
    );

            $profile = CrmElectronicsProfile::query()
                ->where('crm_client_id', $client->id)
                ->first();

            if ($profile instanceof CrmElectronicsProfile) {
                $history = $profile->repair_history ?? [];
                $history[] = [
                    'device' => $deviceName,
                    'issue' => $issue,
                    'resolution' => $resolution,
                    'amount' => $amount,
                    'date' => now()->toDateString(),
                ];
                $profile->update(['repair_history' => $history]);

            $this->logger->channel('audit')->info(class_basename(static::class) . ': Record updated', [
                'id' => $profile->id ?? null,
                'correlation_id' => $correlationId,
            ]);
            }

            $client->increment('total_orders');
            $client->increment('total_spent', $amount);
            $client->update(['last_order_at' => now()]);

            $this->logger->channel('audit')->info(class_basename(static::class) . ': Record updated', [
                'id' => $client->id ?? null,
                'correlation_id' => $correlationId,
            ]);
        });
    }

    /**
     * Записать trade-in.
     */
    public function recordTradeIn(
        CrmElectronicsProfile $profile,
        string $oldDevice,
        string $newDevice,
        float $tradeInValue,
        string $correlationId
    ): CrmElectronicsProfile {
        return $this->db->transaction(function () use ($profile, $oldDevice, $newDevice, $tradeInValue, $correlationId): CrmElectronicsProfile {
            $history = $profile->trade_in_history ?? [];
            $history[] = [
                'old_device' => $oldDevice,
                'new_device' => $newDevice,
                'trade_in_value' => $tradeInValue,
                'date' => now()->toDateString(),
            ];

            $profile->update(['trade_in_history' => $history]);

            $this->logger->channel('audit')->info(class_basename(static::class) . ': Record updated', [
                'id' => $profile->id ?? null,
                'correlation_id' => $correlationId,
            ]);

            $this->audit->log(
                'crm_electronics_trade_in',
                CrmElectronicsProfile::class,
                $profile->id,
                [],
                ['old_device' => $oldDevice, 'new_device' => $newDevice, 'value' => $tradeInValue],
                $correlationId
    );

            return $profile->fresh() ?? $profile;
        });
    }

    /**
     * Клиенты с истекающей гарантией.
     */
    public function getExpiringWarranties(int $tenantId, int $daysAhead = 30): Collection
    {
        return CrmElectronicsProfile::query()
            ->where('tenant_id', $tenantId)
            ->with('client')
            ->get()
            ->filter(function (CrmElectronicsProfile $profile) use ($daysAhead): bool {
                foreach ($profile->warranty_tracking ?? [] as $warranty) {
                    if (isset($warranty['expires_at'])) {
                        $exp = \Carbon\Carbon::parse($warranty['expires_at']);
                        if ($exp->isBetween(now(), now()->addDays($daysAhead))) {
                            return true;
                        }
                    }
                }
                return false;
            });
    }

    /**
     * Клиенты, подписанные на новинки.
     */
    public function getNewReleaseSubscribers(int $tenantId): Collection
    {
        return CrmElectronicsProfile::query()
            ->where('tenant_id', $tenantId)
            ->where('subscribed_to_new_releases', true)
            ->with('client')
            ->get();
    }

    /**
     * «Спящие» electronics-клиенты.
     */
    public function getSleepingClients(int $tenantId, int $daysInactive = 60): Collection
    {
        return CrmClient::query()
            ->forTenant($tenantId)
            ->byVertical('electronics')
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
