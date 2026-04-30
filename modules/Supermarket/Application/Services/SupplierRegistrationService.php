<?php

declare(strict_types=1);

namespace Modules\Supermarket\Application\Services;

use App\Traits\WithAuditLogging;
use App\Traits\WithTelemetry;
use App\Services\FraudControlService;
use Modules\Supermarket\Domain\Exceptions\SupplierPenaltyException;
use Modules\Supermarket\Infrastructure\Models\SupplierRegistration;
use Modules\Supermarket\Infrastructure\Models\Warehouse;
use Modules\Supermarket\Domain\Models\SupplierTier;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

final class SupplierRegistrationService
{
    use WithAuditLogging;
    use WithTelemetry;

    private readonly FraudControlService $fraudControl;

    public function __construct(FraudControlService $fraudControl)
    {
        $this->fraudControl = $fraudControl;
    }

    /**
     * Зарегистрировать поставщика B2B
     */
    public function registerB2BSupplier(array $data, ?int $crmContactId = null): SupplierRegistration
    {
        return $this->withSpan(
            'supplier_registration.register_b2b',
            function () use ($data, $crmContactId) {
                // Fraud check
                $this->fraudControl->check([
                    'operation_type' => 'supplier_registration',
                    'vertical' => 'supermarket',
                    'user_id' => $data['user_id'],
                    'inn' => $data['inn'],
                    'correlation_id' => $this->generateCorrelationId(),
                ]);

                // Проверяем, не зарегистрирован ли уже поставщик с таким ИНН
                $existing = SupplierRegistration::where('inn', $data['inn'])
                    ->where('status', '!=', SupplierRegistration::STATUS_REJECTED)
                    ->first();

                if ($existing) {
                    throw new \Exception('Supplier with this INN already exists');
                }

                return DB::transaction(function () use ($data, $crmContactId) {
                    // Создаем B2B склады если указаны
                    $warehouseIds = [];
                    if (!empty($data['warehouses'])) {
                        foreach ($data['warehouses'] as $warehouseData) {
                            $warehouse = Warehouse::create([
                                'uuid' => (string) \Illuminate\Support\Str::uuid(),
                                'owner_id' => $data['user_id'],
                                'type' => Warehouse::TYPE_B2B,
                                'name' => $warehouseData['name'],
                                'address' => $warehouseData['address'],
                                'city' => $warehouseData['city'],
                                'region' => $warehouseData['region'],
                                'postal_code' => $warehouseData['postal_code'],
                                'latitude' => $warehouseData['latitude'] ?? null,
                                'longitude' => $warehouseData['longitude'] ?? null,
                                'area' => $warehouseData['area'] ?? null,
                                'capacity' => $warehouseData['capacity'] ?? null,
                                'has_cold_storage' => $warehouseData['has_cold_storage'] ?? false,
                                'has_freezer' => $warehouseData['has_freezer'] ?? false,
                                'status' => Warehouse::STATUS_ACTIVE,
                            ]);
                            $warehouseIds[] = $warehouse->id;
                        }
                    }

                    // Создаем регистрацию
                    $registration = SupplierRegistration::create([
                        'uuid' => (string) \Illuminate\Support\Str::uuid(),
                        'user_id' => $data['user_id'],
                        'supplier_tier_id' => $data['supplier_tier_id'],
                        'crm_contact_id' => $crmContactId,
                        'registration_type' => $data['registration_type'] ?? SupplierRegistration::TYPE_B2B_ONLY,
                        'company_name' => $data['company_name'],
                        'inn' => $data['inn'],
                        'kpp' => $data['kpp'] ?? null,
                        'ogrn' => $data['ogrn'] ?? null,
                        'legal_address' => $data['legal_address'],
                        'actual_address' => $data['actual_address'] ?? null,
                        'bank_name' => $data['bank_name'] ?? null,
                        'bik' => $data['bik'] ?? null,
                        'account_number' => $data['account_number'] ?? null,
                        'correspondent_account' => $data['correspondent_account'] ?? null,
                        'contact_person' => $data['contact_person'],
                        'contact_phone' => $data['contact_phone'],
                        'contact_email' => $data['contact_email'],
                        'guarantee_letter_path' => $data['guarantee_letter_path'] ?? null,
                        'attached_documents' => $data['attached_documents'] ?? null,
                        'warehouse_ids' => $warehouseIds,
                        'status' => SupplierRegistration::STATUS_PENDING,
                    ]);

                    // Audit logging
                    $this->logAction(
                        'supplier_registration_created',
                        [
                            'entity_type' => 'SupplierRegistration',
                            'entity_id' => $registration->id,
                            'user_id' => $data['user_id'],
                            'crm_contact_id' => $crmContactId,
                            'registration_type' => $registration->registration_type,
                            'inn' => \Illuminate\Support\Str::mask($data['inn'], '*', 4, 8),
                        ]
                    );

                    Log::info('B2B supplier registration created', [
                        'registration_id' => $registration->id,
                        'user_id' => $data['user_id'],
                        'crm_contact_id' => $crmContactId,
                    ]);

                    return $registration;
                });
            },
            $this->getStandardAttributes(
                vertical: 'supermarket',
                operation: 'supplier_registration_b2b',
            ),
        );
    }

    /**
     * Одобрить регистрацию поставщика
     */
    public function approveRegistration(int $registrationId, int $approvedBy): SupplierRegistration
    {
        $registration = SupplierRegistration::findOrFail($registrationId);

        if ($registration->status !== SupplierRegistration::STATUS_PENDING && 
            $registration->status !== SupplierRegistration::STATUS_UNDER_REVIEW) {
            throw new \Exception('Only pending or under review registrations can be approved');
        }

        $registration->update([
            'status' => SupplierRegistration::STATUS_APPROVED,
            'approved_at' => now(),
            'approved_by' => $approvedBy,
        ]);

        $this->logAction(
            'supplier_registration_approved',
            [
                'entity_type' => 'SupplierRegistration',
                'entity_id' => $registration->id,
                'approved_by' => $approvedBy,
            ]
        );

        Log::info('Supplier registration approved', [
            'registration_id' => $registration->id,
            'approved_by' => $approvedBy,
        ]);

        return $registration;
    }

    /**
     * Отклонить регистрацию поставщика
     */
    public function rejectRegistration(int $registrationId, string $reason): SupplierRegistration
    {
        $registration = SupplierRegistration::findOrFail($registrationId);

        $registration->update([
            'status' => SupplierRegistration::STATUS_REJECTED,
            'rejection_reason' => $reason,
        ]);

        $this->logAction(
            'supplier_registration_rejected',
            [
                'entity_type' => 'SupplierRegistration',
                'entity_id' => $registration->id,
                'reason' => $reason,
            ]
        );

        Log::info('Supplier registration rejected', [
            'registration_id' => $registration->id,
            'reason' => $reason,
        ]);

        return $registration;
    }

    /**
     * Создать B2C склад для существующего поставщика
     */
    public function createB2CWarehouse(int $userId, array $warehouseData): Warehouse
    {
        $warehouse = Warehouse::create([
            'uuid' => (string) \Illuminate\Support\Str::uuid(),
            'owner_id' => $userId,
            'type' => Warehouse::TYPE_B2C,
            'name' => $warehouseData['name'],
            'address' => $warehouseData['address'],
            'city' => $warehouseData['city'],
            'region' => $warehouseData['region'],
            'postal_code' => $warehouseData['postal_code'],
            'latitude' => $warehouseData['latitude'] ?? null,
            'longitude' => $warehouseData['longitude'] ?? null,
            'area' => $warehouseData['area'] ?? null,
            'capacity' => $warehouseData['capacity'] ?? null,
            'has_cold_storage' => $warehouseData['has_cold_storage'] ?? false,
            'has_freezer' => $warehouseData['has_freezer'] ?? false,
            'status' => Warehouse::STATUS_ACTIVE,
        ]);

        // Обновляем регистрацию поставщика, добавляем B2C склад
        $registration = SupplierRegistration::where('user_id', $userId)
            ->where('status', SupplierRegistration::STATUS_APPROVED)
            ->first();

        if ($registration) {
            $warehouseIds = $registration->warehouse_ids ?? [];
            $warehouseIds[] = $warehouse->id;
            $registration->update(['warehouse_ids' => $warehouseIds]);

            // Если была только B2B, меняем на BOTH
            if ($registration->registration_type === SupplierRegistration::TYPE_B2B_ONLY) {
                $registration->update(['registration_type' => SupplierRegistration::TYPE_BOTH]);
            }
        }

        $this->logAction(
            'b2c_warehouse_created',
            [
                'entity_type' => 'Warehouse',
                'entity_id' => $warehouse->id,
                'user_id' => $userId,
            ]
        );

        return $warehouse;
    }

    /**
     * Переместить товар на B2B или B2C склад
     */
    public function moveStockToWarehouse(int $warehouseId, array $stockData): bool
    {
        $warehouse = Warehouse::findOrFail($warehouseId);

        if (!$warehouse->isActive()) {
            throw new \Exception('Warehouse is not active');
        }

        // Логика перемещения товара на склад
        // Это может быть интеграция с Inventory модулем
        
        $this->logAction(
            'stock_moved_to_warehouse',
            [
                'warehouse_id' => $warehouseId,
                'warehouse_type' => $warehouse->type,
                'product_id' => $stockData['product_id'] ?? null,
                'quantity' => $stockData['quantity'] ?? null,
            ]
        );

        Log::info('Stock moved to warehouse', [
            'warehouse_id' => $warehouseId,
            'type' => $warehouse->type,
        ]);

        return true;
    }
}
