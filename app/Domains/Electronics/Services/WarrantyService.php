<?php declare(strict_types=1);

namespace App\Domains\Electronics\Services;

use App\Domains\Electronics\Models\WarrantyClaim;
use App\Domains\Electronics\Events\WarrantyClaimSubmitted;
use App\Services\FraudControlService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

final class WarrantyService
{
    public function __construct(
        private readonly FraudControlService $fraudControlService,
    ) {}

    public function createWarrantyClaim(int $productId, int $clientId, string $issueDescription, int $tenantId, string $correlationId): WarrantyClaim
    {
        // Canon 2026: Mandatory Fraud Check & Audit
        
        \App\Services\Security\FraudControlService::check(['method' => 'createWarrantyClaim'], $correlationId ?? 'system');
        \Illuminate\Support\Facades\Log::channel('audit')->info('CALL createWarrantyClaim', ['domain' => __CLASS__]);

        return DB::transaction(function () use ($productId, $clientId, $issueDescription, $tenantId, $correlationId) {
            $this->fraudControlService->check(
                userId: $clientId,
                operationType: 'warranty_claim',
                amount: 0,
                correlationId: $correlationId,
            );

            $claim = WarrantyClaim::create([
                'tenant_id' => $tenantId,
                'uuid' => Str::uuid(),
                'correlation_id' => $correlationId,
                'product_id' => $productId,
                'client_id' => $clientId,
                'issue_description' => $issueDescription,
                'claim_date' => now(),
                'status' => 'pending',
            ]);

            WarrantyClaimSubmitted::dispatch($claim->id, $tenantId, $clientId, $correlationId);
            Log::channel('audit')->info('Warranty claim created', [
                'claim_id' => $claim->id,
                'product_id' => $productId,
                'correlation_id' => $correlationId,
            ]);

            return $claim;
        });
    }

    public function resolveWarrantyClaim(int $claimId, int $tenantId, string $resolutionNotes, string $correlationId): WarrantyClaim
    {
        // Canon 2026: Mandatory Fraud Check & Audit
        
        \App\Services\Security\FraudControlService::check(['method' => 'resolveWarrantyClaim'], $correlationId ?? 'system');
        \Illuminate\Support\Facades\Log::channel('audit')->info('CALL resolveWarrantyClaim', ['domain' => __CLASS__]);

        return DB::transaction(function () use ($claimId, $tenantId, $resolutionNotes, $correlationId) {
            $claim = WarrantyClaim::lockForUpdate()
                ->where('id', $claimId)
                ->where('tenant_id', $tenantId)
                ->firstOrFail();

            if (!$claim->isPending()) {
                throw new \Exception("Claim {$claimId} is already resolved");
            }

            $claim->update([
                'status' => 'resolved',
                'resolution_notes' => $resolutionNotes,
                'resolution_date' => now(),
            ]);

            Log::channel('audit')->info('Warranty claim resolved', [
                'claim_id' => $claim->id,
                'correlation_id' => $correlationId,
            ]);

            return $claim;
        });
    }
}
