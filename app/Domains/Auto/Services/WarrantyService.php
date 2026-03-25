<?php declare(strict_types=1);

namespace App\Domains\Auto\Services;

use App\Domains\Auto\Models\PartWarranty;
use App\Domains\Auto\Models\ServiceWarranty;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Modules\Finances\Services\Security\FraudControlService;

final class WarrantyService
{
    public function __construct(
        private readonly FraudControlService $fraudControl
    ) {}

    public function createPartWarranty(array $data): PartWarranty
    {
        $correlationId = Str::uuid()->toString();

        $this->log->channel('audit')->info('Creating part warranty', [
            'correlation_id' => $correlationId,
            'tenant_id' => tenant()->id,
        ]);

        try {
            $this->fraudControl->check('part_warranty_creation', request()->ip(), [
                'user_id' => auth()->id(),
                'auto_part_id' => $data['auto_part_id'],
            ]);

            $warranty = $this->db->transaction(function () use ($data, $correlationId) {
                $startDate = \Carbon\Carbon::parse($data['start_date']);
                $endDate = $startDate->copy()->addMonths($data['warranty_months']);

                return PartWarranty::create([
                    ...$data,
                    'tenant_id' => tenant()->id,
                    'end_date' => $endDate,
                    'status' => 'active',
                    'warranty_number' => 'PW-' . strtoupper(Str::random(10)),
                    'uuid' => Str::uuid()->toString(),
                    'correlation_id' => $correlationId,
                ]);
            });

            $this->log->channel('audit')->info('Part warranty created', [
                'correlation_id' => $correlationId,
                'warranty_id' => $warranty->id,
            ]);

            return $warranty;
        } catch (\Throwable $e) {
            $this->log->channel('audit')->error('Part warranty creation failed', [
                'correlation_id' => $correlationId,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            throw $e;
        }
    }

    public function createServiceWarranty(array $data): ServiceWarranty
    {
        $correlationId = Str::uuid()->toString();

        $this->log->channel('audit')->info('Creating service warranty', [
            'correlation_id' => $correlationId,
            'tenant_id' => tenant()->id,
        ]);

        try {
            $this->fraudControl->check('service_warranty_creation', request()->ip(), [
                'user_id' => auth()->id(),
                'auto_service_order_id' => $data['auto_service_order_id'],
            ]);

            $warranty = $this->db->transaction(function () use ($data, $correlationId) {
                $startDate = \Carbon\Carbon::parse($data['start_date']);
                $endDate = $startDate->copy()->addMonths($data['warranty_months']);

                return ServiceWarranty::create([
                    ...$data,
                    'tenant_id' => tenant()->id,
                    'end_date' => $endDate,
                    'status' => 'active',
                    'warranty_number' => 'SW-' . strtoupper(Str::random(10)),
                    'uuid' => Str::uuid()->toString(),
                    'correlation_id' => $correlationId,
                ]);
            });

            $this->log->channel('audit')->info('Service warranty created', [
                'correlation_id' => $correlationId,
                'warranty_id' => $warranty->id,
            ]);

            return $warranty;
        } catch (\Throwable $e) {
            $this->log->channel('audit')->error('Service warranty creation failed', [
                'correlation_id' => $correlationId,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            throw $e;
        }
    }

    public function submitPartWarrantyClaim(int $warrantyId, string $reason, ?string $notes = null): PartWarranty
    {
        $correlationId = Str::uuid()->toString();

        try {
            $warranty = PartWarranty::findOrFail($warrantyId);

            if (!$warranty->isActive()) {
                throw new \Exception('Warranty is not active or has expired');
            }

            if ($warranty->claim_status === 'pending') {
                throw new \Exception('Warranty claim already submitted');
            }

            $this->db->transaction(function () use ($warranty, $reason, $notes) {
                $warranty->update([
                    'claim_date' => now(),
                    'claim_reason' => $reason,
                    'claim_status' => 'pending',
                    'notes' => $notes,
                ]);
            });

            $this->log->channel('audit')->info('Part warranty claim submitted', [
                'correlation_id' => $correlationId,
                'warranty_id' => $warrantyId,
            ]);

            return $warranty;
        } catch (\Throwable $e) {
            $this->log->channel('audit')->error('Part warranty claim submission failed', [
                'correlation_id' => $correlationId,
                'error' => $e->getMessage(),
            ]);

            throw $e;
        }
    }

    public function submitServiceWarrantyClaim(
        int $warrantyId,
        string $reason,
        int $claimMileage,
        ?string $notes = null
    ): ServiceWarranty {
        $correlationId = Str::uuid()->toString();

        try {
            $warranty = ServiceWarranty::findOrFail($warrantyId);

            if (!$warranty->isActive()) {
                throw new \Exception('Warranty is not active or has expired');
            }

            if (!$warranty->isValidByMileage($claimMileage)) {
                throw new \Exception('Warranty claim exceeds mileage limit');
            }

            if ($warranty->claim_status === 'pending') {
                throw new \Exception('Warranty claim already submitted');
            }

            $this->db->transaction(function () use ($warranty, $reason, $claimMileage, $notes) {
                $warranty->update([
                    'claim_date' => now(),
                    'claim_reason' => $reason,
                    'claim_mileage' => $claimMileage,
                    'claim_status' => 'pending',
                    'notes' => $notes,
                ]);
            });

            $this->log->channel('audit')->info('Service warranty claim submitted', [
                'correlation_id' => $correlationId,
                'warranty_id' => $warrantyId,
            ]);

            return $warranty;
        } catch (\Throwable $e) {
            $this->log->channel('audit')->error('Service warranty claim submission failed', [
                'correlation_id' => $correlationId,
                'error' => $e->getMessage(),
            ]);

            throw $e;
        }
    }

    public function approvePartWarrantyClaim(
        int $warrantyId,
        ?int $replacementPartId = null,
        ?string $notes = null
    ): PartWarranty {
        $correlationId = Str::uuid()->toString();

        try {
            $warranty = PartWarranty::findOrFail($warrantyId);

            $this->db->transaction(function () use ($warranty, $replacementPartId, $notes) {
                $warranty->update([
                    'claim_status' => 'approved',
                    'replacement_part_id' => $replacementPartId,
                    'notes' => $notes,
                ]);
            });

            $this->log->channel('audit')->info('Part warranty claim approved', [
                'correlation_id' => $correlationId,
                'warranty_id' => $warrantyId,
            ]);

            return $warranty;
        } catch (\Throwable $e) {
            $this->log->channel('audit')->error('Part warranty claim approval failed', [
                'correlation_id' => $correlationId,
                'error' => $e->getMessage(),
            ]);

            throw $e;
        }
    }

    public function approveServiceWarrantyClaim(int $warrantyId, ?string $notes = null): ServiceWarranty
    {
        $correlationId = Str::uuid()->toString();

        try {
            $warranty = ServiceWarranty::findOrFail($warrantyId);

            $this->db->transaction(function () use ($warranty, $notes) {
                $warranty->update([
                    'claim_status' => 'approved',
                    'notes' => $notes,
                ]);
            });

            $this->log->channel('audit')->info('Service warranty claim approved', [
                'correlation_id' => $correlationId,
                'warranty_id' => $warrantyId,
            ]);

            return $warranty;
        } catch (\Throwable $e) {
            $this->log->channel('audit')->error('Service warranty claim approval failed', [
                'correlation_id' => $correlationId,
                'error' => $e->getMessage(),
            ]);

            throw $e;
        }
    }

    public function rejectPartWarrantyClaim(int $warrantyId, string $notes): PartWarranty
    {
        $correlationId = Str::uuid()->toString();

        try {
            $warranty = PartWarranty::findOrFail($warrantyId);

            $this->db->transaction(function () use ($warranty, $notes) {
                $warranty->update([
                    'claim_status' => 'rejected',
                    'notes' => $notes,
                ]);
            });

            $this->log->channel('audit')->info('Part warranty claim rejected', [
                'correlation_id' => $correlationId,
                'warranty_id' => $warrantyId,
            ]);

            return $warranty;
        } catch (\Throwable $e) {
            $this->log->channel('audit')->error('Part warranty claim rejection failed', [
                'correlation_id' => $correlationId,
                'error' => $e->getMessage(),
            ]);

            throw $e;
        }
    }

    public function rejectServiceWarrantyClaim(int $warrantyId, string $notes): ServiceWarranty
    {
        $correlationId = Str::uuid()->toString();

        try {
            $warranty = ServiceWarranty::findOrFail($warrantyId);

            $this->db->transaction(function () use ($warranty, $notes) {
                $warranty->update([
                    'claim_status' => 'rejected',
                    'notes' => $notes,
                ]);
            });

            $this->log->channel('audit')->info('Service warranty claim rejected', [
                'correlation_id' => $correlationId,
                'warranty_id' => $warrantyId,
            ]);

            return $warranty;
        } catch (\Throwable $e) {
            $this->log->channel('audit')->error('Service warranty claim rejection failed', [
                'correlation_id' => $correlationId,
                'error' => $e->getMessage(),
            ]);

            throw $e;
        }
    }
}
