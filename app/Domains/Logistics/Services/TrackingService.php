<?php declare(strict_types=1);

namespace App\Domains\Logistics\Services;



use Illuminate\Contracts\Auth\Guard;
use Psr\Log\LoggerInterface;
use App\Domains\Logistics\Models\Shipment;
use App\Domains\Logistics\Models\ShipmentTracking;
use App\Services\FraudControlService;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Str;

/**
 * Class TrackingService
 *
 * Part of the Logistics vertical domain.
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
 * @package App\Domains\Logistics\Services
 */
final readonly class TrackingService
{
    public function __construct(private FraudControlService $fraud,
        private readonly \Illuminate\Database\DatabaseManager $db, private readonly LoggerInterface $logger, private readonly Guard $guard) {}

    public function addTrackingEvent(
        Shipment $shipment,
        string   $eventType,
        ?string  $location,
        ?string  $notes,
        string   $correlationId = ''
    ): ShipmentTracking {
        $correlationId = $correlationId ?: Str::uuid()->toString();

        $this->fraud->check(userId: $this->guard->id() ?? 0, operationType: 'tracking_event_add', amount: 0, correlationId: $correlationId ?? '');

        return $this->db->transaction(function () use ($shipment, $eventType, $location, $notes, $correlationId): ShipmentTracking {
            $tracking = ShipmentTracking::create([
                'tenant_id'      => $shipment->tenant_id,
                'shipment_id'    => $shipment->id,
                'event_type'     => $eventType,
                'location'       => $location,
                'notes'          => $notes,
                'event_time'     => now(),
                'correlation_id' => $correlationId,
            ]);

            $this->logger->info('Tracking event added', [
                'shipment_id'    => $shipment->id,
                'event_type'     => $eventType,
                'correlation_id' => $correlationId,
            ]);

            return $tracking;
        });
    }

    public function getShipmentHistory(Shipment $shipment): Collection
    {
        return $shipment->tracking()->orderBy('event_time', 'desc')->get();
    }
}
