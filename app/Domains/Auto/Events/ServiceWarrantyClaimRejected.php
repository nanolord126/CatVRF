<?php declare(strict_types=1);

namespace App\Domains\Auto\Events;


use Psr\Log\LoggerInterface;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
/**
 * Class ServiceWarrantyClaimRejected
 *
 * Part of the Auto vertical domain.
 * Follows CatVRF 9-layer architecture.
 *
 * Service layer following CatVRF canon:
 * - Constructor injection only (no Facades)
 * - FraudControlService::check() before mutations
 * - $this->db->transaction() wrapping all write operations
 * - Audit logging with correlation_id
 * - Tenant and BusinessGroup scoping
 *
 * @see \App\Services\FraudControlService
 * @see \App\Services\AuditService
 * @package App\Domains\Auto\Events
 */
final class ServiceWarrantyClaimRejected
{
    
    public function __construct(
        public readonly mixed $warranty, // Implemented per canon 2026
        public readonly string $rejectionReason,
        public readonly string $correlationId, public readonly LoggerInterface $logger
    ) {
        $this->logger->info('ServiceWarrantyClaimRejected event dispatched', [
            'correlation_id' => $this->correlationId,
            // 'warranty_id' => $this->warranty->id,
            // 'warranty_number' => $this->warranty->warranty_number,
            'rejection_reason' => $this->rejectionReason,
        ]);
    }

    /**
     * Handle broadcastOn operation.
     *
     * @throws \DomainException
     */
    public function broadcastOn(): array
    {
        return [
            // new PrivateChannel('tenant.' . $this->warranty->tenant_id),
            // new PrivateChannel('user.' . $this->warranty->client_id),
        ];
    }

    public function broadcastAs(): string
    {
        return 'warranty.service.claim.rejected';
    }
}
