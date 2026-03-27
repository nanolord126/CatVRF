<?php

declare(strict_types=1);

namespace App\Services\Dental;

use App\Models\Dental\DentalService as DentalModel;
use App\Models\Dental\DentalClinic;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

/**
 * Dental Service (Main Orchestrator).
 * Strictly follows CANON 2026: DB::transaction, correlation_id, and FraudControl.
 */
final readonly class DentalService
{
    public function __construct(
        private \App\Services\FraudControlService $fraudControl,
        private PricingService $pricingService,
        private string $correlation_id = ''
    ) {
        $this->correlation_id = empty($correlation_id) ? (string) Str::uuid() : $correlation_id;
    }

    /**
     * Get all available services for a clinic with tenant scoping.
     */
    public function getClinicServices(int $clinicId): Collection
    {
        return DentalModel::where('clinic_id', $clinicId)
            ->where('is_active', true)
            ->orderBy('category')
            ->orderBy('name')
            ->get();
    }

    /**
     * Create a new dental service with thorough validation and auditing.
     */
    public function createService(array $data): DentalModel
    {
        return DB::transaction(function () use ($data) {
            // 1. Fraud Check
            $this->fraudControl->check(['operation' => 'create_dental_service', 'data' => $data]);

            // 2. Audit Log
            Log::channel('audit')->info('Creating dental service', [
                'name' => $data['name'],
                'clinic_id' => $data['clinic_id'],
                'correlation_id' => $this->correlation_id,
            ]);

            // 3. Create Service
            $service = DentalModel::create(array_merge($data, [
                'correlation_id' => $this->correlation_id,
                'uuid' => (string) Str::uuid(),
            ]));

            if (!$service) {
                throw new \RuntimeException('Failed to create dental service (DB error)');
            }

            return $service;
        });
    }

    /**
     * Update an existing dental service with audit trail.
     */
    public function updateService(int $id, array $data): DentalModel
    {
        return DB::transaction(function () use ($id, $data) {
            $service = DentalModel::findOrFail($id);

            // Audit
            Log::channel('audit')->info('Updating dental service', [
                'service_id' => $id,
                'old_price' => $service->base_price,
                'new_price' => $data['base_price'] ?? $service->base_price,
                'correlation_id' => $this->correlation_id,
            ]);

            $service->update(array_merge($data, [
                'correlation_id' => $this->correlation_id,
            ]));

            return $service;
        });
    }

    /**
     * Calculate aggregate costs for a group of services.
     */
    public function calculateBatchPrice(array $serviceIds): int
    {
        $services = DentalModel::whereIn('id', $serviceIds)->get();
        if ($services->isEmpty()) {
            throw new \InvalidArgumentException('No dental services found for the provided IDs');
        }

        return $this->pricingService->calculateTotal($services);
    }

    /**
     * Deactivate a service.
     */
    public function deactivateService(int $id): bool
    {
        return DB::transaction(function () use ($id) {
            $service = DentalModel::findOrFail($id);
            Log::channel('audit')->warning('Deactivating dental service', [
                'service_id' => $id,
                'correlation_id' => $this->correlation_id,
            ]);
            
            return $service->delete();
        });
    }
}
