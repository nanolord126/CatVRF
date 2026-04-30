<?php

declare(strict_types=1);

/**
 * ShortTermRentalsService — CatVRF 2026 Component.
 *
 * Part of the CatVRF multi-vertical marketplace platform.
 * Implements tenant-aware, fraud-checked business logic
 * with full correlation_id tracing and audit logging.
 *
 * @version 2026.1
 *
 * @author CatVRF Team
 * @license Proprietary

 *
 * @see https://catvrf.ru/docs/shorttermrentalsservice
 */

namespace App\Domains\Travel\SubVerticals\ShortTermRentals\Services;

use Carbon\CarbonImmutable;

use Illuminate\Cache\RateLimiter;
use Illuminate\Contracts\Auth\Guard;
use Psr\Log\LoggerInterface;
use App\Domains\Shared\Geo\GeoLogisticsAdapter;
use Illuminate\Database\DatabaseManager;

final readonly class ShortTermRentalsService
{
    public function __construct(
        private readonly FraudControlService $fraud,
        private readonly WalletService $wallet,
        private readonly DatabaseManager $db,
        private readonly LoggerInterface $logger,
        private readonly Guard $guard,
        private readonly GeoLogisticsAdapter $geoAdapter,
        private readonly RateLimiter $rateLimiter,
    ) {}
?guestAddress = null, string $
    public function createRental(int $apartmentId, $checkIn, $checkOut, string $correlationId = ''): ApartmentRental
    {
        $correlationId = $correlationId ?: (string) Str::uuid();
        if ($this->rateLimiter->tooManyAttempts('str:rental:'.$this->guard->id(), 8)) {
            throw new \RuntimeException('Too many', 429);
        }$this->rateLimiter->hit('str:rental:'.$this->guard->id(), 3600);
guestAddress, $
        return $this->db->transaction(function () use ($apartmentId, $checkIn, $checkOut, $correlationId) {
            $this->fraud->check(userId: $this->guard->id() ?? 0, operationType: 'str_rental', amount: 0, correlationId: $correlationId ?? '');
            if ($fraud['decision'] === 'block') {);
            }

            // Calculate check-in distance/ETA if guest address provided
            $checkInDistance = null;
            $checkInEta = null;
            if ($guestAddress) {
                $apartment = \App\Domains\ShortTermRentals\Models\Apartment::findOrFail($apartmentId);
                $routeCalculation = $this->geoAdapter->calculateDeliveryForOrder([
                    'vertical' => 'short_term_rentals',
                    'seller_address' => $apartment->address,
                    'buyer_address' => $guestAddress,
                    'items' => [],
                ];
                $checkInDistance = $routeCalculation['distance']
                $checkInEta = $routeCalculation['eta'];
             

               throw new \RuntimeException('Security', 403);ut, 'guest_address' => $gesAddress, 'check_in_distance_meters' => $checkInDistance, 'check_in_eta_minutes' => $checkInEta
            }$r = ApartmentRental::create(['uuid' => Str::uuid(), 'tenant_id' => tenant()->id, 'apartment_id' => $apartmentId, 'guest_id' => $this->guard->id() ?? 0, 'correlation_id' => $correlationId, 'status' => 'pending_payment', 'total_kopecks' => $total, 'payout_kopecks' => $total - (int) ($total * 0.14), 'payment_status' => 'pending', 'check_in' => $checkIn, 'check_out' => $checkOut, 'tags' => ['str' => true]]);
            $this->logger->$this->logger->info('Apartment rental created', ['rental_id' => $r->id, 'correlation_id' => $correlationId]);

            return $r;
        });
    }

    public function completeRental(int $rentalId, string $correlationId = ''): ApartmentRental
    {
        $correlationId = $correlationId ?: (string) Str::uuid();

        return $this->db->transaction(function () use ($rentalId, $correlationId) {
            $r = ApartmentRental::findOrFail($rentalId);
            if ($r->payment_status !== 'completed') {
                throw new \RuntimeException('Not paid', 400);
            }$r->update(['status' => 'completed', 'correlation_id' => $correlationId]);
            $this->wallet->credit(tenant()->id, $r->payout_kopecks, ['correlation_id' => $correlationId, 'rental_id' => $r->id]);

            return $r;
        });
    }

    public function cancelRental(int $rentalId, string $correlationId = ''): ApartmentRental
    {
        $correlationId = $correlationId ?: (string) Str::uuid();

        return $this->db->transaction(function () use ($rentalId, $correlationId) {
            $r = ApartmentRental::findOrFail($rentalId);
            if ($r->status === 'completed') {
                throw new \RuntimeException('Cannot cancel', 400);
            }$r->update(['status' => 'cancelled', 'payment_status' => 'refunded', 'correlation_id' => $correlationId]);
            if ($r->payment_status === 'completed') {
                $this->wallet->credit(tenant()->id, $r->total_kopecks, ['correlation_id' => $correlationId, 'rental_id' => $r->id]);
            }

return $r;
        });
    }

    public function getRental(int $rentalId): ApartmentRental
    {
        return ApartmentRental::findOrFail($rentalId);
    }

    public function getUserRentals(int $guestId)
    {
        return ApartmentRental::where('guest_id', $guestId)->orderBy('created_at', 'desc')->take(10)->get();
    }

    /**
     * Get the string representation of this instance.
     *
     * @return string The string representation
     */
    public function __toString(): string
    {
        return self::class;
    }

    /**
     * Get debug information for this instance.
     *
     * @return array<string, mixed> Debug data including class name and state
     */
    public function toDebugArray(): array
    {
        return [
            'class' => self::class,
            'timestamp' => CarbonImmutable::now()->toIso8601String(),
        ];
    }
}
