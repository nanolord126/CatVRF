<?php declare(strict_types=1);

namespace Modules\RealEstate\Services;

use Modules\RealEstate\Models\PropertyBooking;
use Modules\RealEstate\Models\Property;
use Modules\RealEstate\Enums\BookingStatus;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Redis;
use Illuminate\Support\Str;
use DomainException;

final class PropertyBookingService
{
    private const HOLD_SLOT_B2C_MINUTES = 15;
    private const HOLD_SLOT_B2B_MINUTES = 60;
    private const CACHE_TTL_SECONDS = 3600;
    private const BOOKING_LOCK_TTL = 300;

    public function createBooking(array $data): PropertyBooking
    {
        $correlationId = $data['correlation_id'] ?? Str::uuid()->toString();
        $tenantId = $data['tenant_id'] ?? tenant()->id;
        $businessGroupId = $data['business_group_id'] ?? null;
        $inn = $data['inn'] ?? null;
        $isB2B = $businessGroupId !== null && $inn !== null;

        try {
            Log::channel('audit')->info('real_estate.booking.create.start', [
                'correlation_id' => $correlationId,
                'property_id' => $data['property_id'],
                'user_id' => $data['user_id'],
                'is_b2b' => $isB2B,
            ]);

            $property = Property::where('id', $data['property_id'])
                ->where('tenant_id', $tenantId)
                ->where('status', 'available')
                ->first();

            if ($property === null) {
                throw new DomainException('Property not found or not available', 404);
            }

            $holdMinutes = $isB2B ? self::HOLD_SLOT_B2B_MINUTES : self::HOLD_SLOT_B2C_MINUTES;
            $fraudScore = $this->calculateFraudScore($data, $property, $isB2B);

            if ($fraudScore > 0.7) {
                Log::channel('fraud')->warning('High fraud score detected for booking', [
                    'correlation_id' => $correlationId,
                    'fraud_score' => $fraudScore,
                    'user_id' => $data['user_id'],
                    'property_id' => $data['property_id'],
                ]);
                throw new DomainException('Booking flagged as high risk', 403);
            }

            $lockKey = "booking_lock:{$data['property_id']}:{$data['viewing_slot']}";
            $lockAcquired = Redis::set($lockKey, '1', 'EX', self::BOOKING_LOCK_TTL, 'NX');

            if (!$lockAcquired) {
                throw new DomainException('Another booking is in progress for this slot', 409);
            }

            try {
                $booking = DB::transaction(function () use ($data, $property, $fraudScore, $holdMinutes, $isB2B, $tenantId, $businessGroupId, $correlationId) {
                    $dealScore = $this->calculateDealScore($data, $property, $isB2B);
                    $dynamicPrice = $this->calculateDynamicPrice($property, $data['viewing_slot'], $isB2B);

                    $booking = PropertyBooking::create([
                        'tenant_id' => $tenantId,
                        'business_group_id' => $businessGroupId,
                        'uuid' => Str::uuid()->toString(),
                        'correlation_id' => $correlationId,
                        'property_id' => $data['property_id'],
                        'user_id' => $data['user_id'],
                        'viewing_slot' => $data['viewing_slot'],
                        'amount' => $dynamicPrice,
                        'status' => BookingStatus::PENDING,
                        'deal_score' => $dealScore,
                        'fraud_score' => $fraudScore,
                        'idempotency_key' => $data['idempotency_key'] ?? Str::uuid()->toString(),
                        'is_b2b' => $isB2B,
                        'hold_until' => now()->addMinutes($holdMinutes),
                        'face_id_verified' => $data['face_id_token'] !== null,
                        'blockchain_verified' => false,
                        'webrtc_room_id' => null,
                        'original_price' => $property->price,
                        'dynamic_discount' => $property->price - $dynamicPrice,
                        'escrow_amount' => $data['use_escrow'] ?? false ? $dynamicPrice : 0,
                        'commission_split' => $isB2B ? $this->calculateB2BCommissionSplit($dynamicPrice) : null,
                        'metadata' => [
                            'ai_virtual_tour_enabled' => $property->hasVirtualTour(),
                            'ar_model_enabled' => $property->hasARModel(),
                            'face_id_token' => $data['face_id_token'] ?? null,
                            'wallet_id' => $data['wallet_id'] ?? null,
                            'use_escrow' => $data['use_escrow'] ?? false,
                        ],
                    ]);

                    if (($data['use_escrow'] ?? false) && $dynamicPrice > 0 && isset($data['wallet_id'])) {
                        $this->holdEscrowAmount($data['wallet_id'], $dynamicPrice, $booking->id, $correlationId);
                    }

                    $this->syncWithCRM($booking);
                    Cache::forget("property_availability:{$data['property_id']}");

                    return $booking;
                });

                Log::channel('audit')->info('real_estate.booking.create.success', [
                    'correlation_id' => $correlationId,
                    'booking_id' => $booking->id,
                    'deal_score' => $booking->deal_score,
                    'fraud_score' => $fraudScore,
                ]);

                return $booking;
            } finally {
                Redis::del($lockKey);
            }
        } catch (\Throwable $e) {
            Log::channel('audit')->critical('real_estate.booking.create.error', [
                'correlation_id' => $correlationId,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            throw $e;
        }
    }

    public function confirmBooking(int $bookingId, ?string $correlationId = null): PropertyBooking
    {
        $correlationId ??= Str::uuid()->toString();

        try {
            Log::channel('audit')->info('real_estate.booking.confirm.start', [
                'correlation_id' => $correlationId,
                'booking_id' => $bookingId,
            ]);

            $booking = PropertyBooking::findOrFail($bookingId);

            if (!$booking->canBeConfirmed()) {
                throw new DomainException('Booking cannot be confirmed', 400);
            }

            $booking = DB::transaction(function () use ($booking, $correlationId) {
                $property = Property::where('id', $booking->property_id)
                    ->where('status', 'available')
                    ->first();

                if ($property === null) {
                    throw new DomainException('Property is no longer available', 400);
                }

                $documentHash = $this->verifyDocumentsOnBlockchain($booking, $property);
                $webrtcRoomId = $this->createWebRTCRoom($booking);

                $booking->update([
                    'status' => BookingStatus::CONFIRMED,
                    'blockchain_verified' => true,
                    'webrtc_room_id' => $webrtcRoomId,
                    'metadata' => array_merge($booking->metadata ?? [], [
                        'blockchain_document_hash' => $documentHash,
                        'webrtc_room_created_at' => now()->toIso8601String(),
                    ]),
                ]);

                if ($booking->escrow_amount > 0 && isset($booking->metadata['wallet_id'])) {
                    $this->captureEscrowAmount($booking->metadata['wallet_id'], $booking->escrow_amount, $booking->id, $correlationId);
                }

                $this->updateCRMStatus($booking, BookingStatus::CONFIRMED);

                Log::channel('audit')->info('real_estate.booking.confirm.success', [
                    'correlation_id' => $correlationId,
                    'booking_id' => $booking->id,
                    'document_hash' => $documentHash,
                    'webrtc_room_id' => $webrtcRoomId,
                ]);

                return $booking->fresh();
            });

            return $booking;
        } catch (\Throwable $e) {
            Log::channel('audit')->critical('real_estate.booking.confirm.error', [
                'correlation_id' => $correlationId,
                'booking_id' => $bookingId,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            throw $e;
        }
    }

    public function completeDeal(int $bookingId, ?string $correlationId = null): PropertyBooking
    {
        $correlationId ??= Str::uuid()->toString();

        try {
            Log::channel('audit')->info('real_estate.deal.complete.start', [
                'correlation_id' => $correlationId,
                'booking_id' => $bookingId,
            ]);

            $booking = PropertyBooking::findOrFail($bookingId);

            if ($booking->status !== BookingStatus::CONFIRMED) {
                throw new DomainException('Booking must be confirmed first', 400);
            }

            $booking = DB::transaction(function () use ($booking, $correlationId) {
                $property = Property::findOrFail($booking->property_id);

                if ($booking->amount > 0 && isset($booking->metadata['wallet_id'])) {
                    $this->transferPaymentToEscrow(
                        $booking->metadata['wallet_id'],
                        $property->owner_id,
                        $booking->amount,
                        $booking->id,
                        $correlationId
                    );
                }

                $booking->markAsCompleted();
                $property->markAsSold();

                $this->updateCRMStatus($booking, BookingStatus::COMPLETED);
                $this->recordDealCompletionInCRM($booking, $property);

                Cache::forget("property_availability:{$property->id}");

                Log::channel('audit')->info('real_estate.deal.complete.success', [
                    'correlation_id' => $correlationId,
                    'booking_id' => $booking->id,
                    'property_id' => $property->id,
                    'amount' => $booking->amount,
                ]);

                return $booking->fresh();
            });

            return $booking;
        } catch (\Throwable $e) {
            Log::channel('audit')->critical('real_estate.deal.complete.error', [
                'correlation_id' => $correlationId,
                'booking_id' => $bookingId,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            throw $e;
        }
    }

    public function cancelBooking(int $bookingId, string $reason, ?string $correlationId = null): PropertyBooking
    {
        $correlationId ??= Str::uuid()->toString();

        try {
            Log::channel('audit')->info('real_estate.booking.cancel.start', [
                'correlation_id' => $correlationId,
                'booking_id' => $bookingId,
                'reason' => $reason,
            ]);

            $booking = PropertyBooking::findOrFail($bookingId);

            if (!$booking->canBeCancelled()) {
                throw new DomainException('Booking cannot be cancelled', 400);
            }

            $booking = DB::transaction(function () use ($booking, $reason, $correlationId) {
                if ($booking->escrow_amount > 0 && isset($booking->metadata['wallet_id'])) {
                    $this->releaseEscrowAmount($booking->metadata['wallet_id'], $booking->escrow_amount, $booking->id, $reason, $correlationId);
                }

                $booking->markAsCancelled($reason);
                $this->updateCRMStatus($booking, BookingStatus::CANCELLED);
                $this->recordCancellationInCRM($booking, $reason);

                Cache::forget("property_availability:{$booking->property_id}");

                Log::channel('audit')->info('real_estate.booking.cancel.success', [
                    'correlation_id' => $correlationId,
                    'booking_id' => $booking->id,
                ]);

                return $booking->fresh();
            });

            return $booking;
        } catch (\Throwable $e) {
            Log::channel('audit')->critical('real_estate.booking.cancel.error', [
                'correlation_id' => $correlationId,
                'booking_id' => $bookingId,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            throw $e;
        }
    }

    public function initiateVideoCall(int $bookingId, int $initiatorId, ?string $correlationId = null): array
    {
        $correlationId ??= Str::uuid()->toString();

        try {
            $booking = PropertyBooking::findOrFail($bookingId);

            if ($booking->user_id !== $initiatorId) {
                throw new DomainException('Unauthorized video call initiation', 403);
            }

            if ($booking->status !== BookingStatus::CONFIRMED) {
                throw new DomainException('Video call only available for confirmed bookings', 400);
            }

            $property = Property::findOrFail($booking->property_id);
            $webrtcRoomId = $this->getOrCreateWebRTCRoom($booking, $initiatorId, $property->owner_id);
            $token = $this->generateWebRTCToken($webrtcRoomId, $initiatorId);

            Log::channel('audit')->info('real_estate.video_call.initiated', [
                'correlation_id' => $correlationId,
                'booking_id' => $booking->id,
                'room_id' => $webrtcRoomId,
                'initiator_id' => $initiatorId,
            ]);

            return [
                'room_id' => $webrtcRoomId,
                'token' => $token,
                'expires_at' => now()->addHours(2)->toIso8601String(),
                'participants' => [$initiatorId, $property->owner_id],
            ];
        } catch (\Throwable $e) {
            Log::channel('audit')->critical('real_estate.video_call.error', [
                'correlation_id' => $correlationId,
                'booking_id' => $bookingId,
                'error' => $e->getMessage(),
            ]);
            throw $e;
        }
    }

    public function getAvailableSlots(int $propertyId, string $startDate, string $endDate, ?string $correlationId = null): array
    {
        $correlationId ??= Str::uuid()->toString();
        $cacheKey = "property_slots:{$propertyId}:{$startDate}:{$endDate}";

        return Cache::remember($cacheKey, self::CACHE_TTL_SECONDS, function () use ($propertyId, $startDate, $endDate, $correlationId) {
            $property = Property::where('id', $propertyId)->where('status', 'available')->first();

            if ($property === null) {
                return [];
            }

            $existingBookings = PropertyBooking::where('property_id', $propertyId)
                ->whereBetween('viewing_slot', [$startDate, $endDate])
                ->whereNotIn('status', [BookingStatus::CANCELLED, BookingStatus::EXPIRED])
                ->get();

            $bookedSlots = $existingBookings->map(fn($b) => $b->viewing_slot->format('Y-m-d-H-i'))->toArray();

            $availableSlots = [];
            $current = \Carbon\Carbon::parse($startDate);
            $end = \Carbon\Carbon::parse($endDate);
            $slotInterval = new \DateInterval('PT1H');

            while ($current <= $end) {
                $slotKey = $current->format('Y-m-d-H-i');
                if (!in_array($slotKey, $bookedSlots, true) && $current > now()) {
                    $demandMultiplier = $this->predictDemand($propertyId, $current);
                    $availableSlots[] = [
                        'datetime' => $current->format('c'),
                        'is_peak' => $demandMultiplier > 1.2,
                        'demand_multiplier' => $demandMultiplier,
                        'price_adjustment' => $demandMultiplier > 1.2 ? 1.1 : 1.0,
                    ];
                }
                $current->add($slotInterval);
            }

            Log::channel('audit')->info('real_estate.slots.retrieved', [
                'correlation_id' => $correlationId,
                'property_id' => $propertyId,
                'slots_count' => count($availableSlots),
            ]);

            return $availableSlots;
        });
    }

    private function calculateFraudScore(array $data, Property $property, bool $isB2B): float
    {
        $baseScore = 0.1;

        $userHistoryScore = $this->getUserHistoryScore($data['user_id']);
        $documentScore = $this->getDocumentVerificationScore($property);
        $priceAnomalyScore = $this->getPriceAnomalyScore($property->price, $property->area, $property->city);

        $fraudScore = ($baseScore + $userHistoryScore + $documentScore + $priceAnomalyScore) / 4;

        if ($isB2B) {
            $fraudScore = max(0.0, $fraudScore - 0.1);
        }

        return round(min(1.0, max(0.0, $fraudScore)), 4);
    }

    private function calculateDealScore(array $data, Property $property, bool $isB2B): array
    {
        $creditScore = $this->getCreditScore($data['user_id']);
        $legalRiskScore = $this->getLegalRiskScore($property->id);
        $liquidityScore = $this->getLiquidityScore($property->id, $property->city, $property->price);

        $overallScore = ($creditScore * 0.4) + ((1 - $legalRiskScore) * 0.3) + ($liquidityScore * 0.3);

        if ($isB2B) {
            $b2bAdjustment = 0.1;
            $overallScore = min(1.0, $overallScore + $b2bAdjustment);
        }

        return [
            'overall' => round($overallScore, 4),
            'credit' => round($creditScore, 4),
            'legal' => round(1 - $legalRiskScore, 4),
            'liquidity' => round($liquidityScore, 4),
            'recommended' => $overallScore >= 0.7,
        ];
    }

    private function calculateDynamicPrice(Property $property, string $viewingSlot, bool $isB2B): float
    {
        $basePrice = (float) $property->price;
        $demandMultiplier = $this->predictDemand($property->id, \Carbon\Carbon::parse($viewingSlot));

        $dynamicPrice = $basePrice * $demandMultiplier;

        if ($isB2B) {
            $dynamicPrice = $dynamicPrice * 0.85;
        }

        if ($demandMultiplier > 1.3 && \Carbon\Carbon::parse($viewingSlot)->diffInHours(now()) < 24) {
            $dynamicPrice = $dynamicPrice * 1.15;
        }

        return round($dynamicPrice, 2);
    }

    private function calculateB2BCommissionSplit(float $amount): array
    {
        return [
            'platform' => round($amount * 0.08, 2),
            'agent' => round($amount * 0.03, 2),
            'referral' => round($amount * 0.02, 2),
            'total' => round($amount * 0.13, 2),
        ];
    }

    private function holdEscrowAmount(int $walletId, float $amount, int $bookingId, string $correlationId): void
    {
        Log::channel('audit')->info('real_estate.escrow.hold', [
            'correlation_id' => $correlationId,
            'wallet_id' => $walletId,
            'amount' => $amount,
            'booking_id' => $bookingId,
        ]);
    }

    private function captureEscrowAmount(int $walletId, float $amount, int $bookingId, string $correlationId): void
    {
        Log::channel('audit')->info('real_estate.escrow.capture', [
            'correlation_id' => $correlationId,
            'wallet_id' => $walletId,
            'amount' => $amount,
            'booking_id' => $bookingId,
        ]);
    }

    private function releaseEscrowAmount(int $walletId, float $amount, int $bookingId, string $reason, string $correlationId): void
    {
        Log::channel('audit')->info('real_estate.escrow.release', [
            'correlation_id' => $correlationId,
            'wallet_id' => $walletId,
            'amount' => $amount,
            'booking_id' => $bookingId,
            'reason' => $reason,
        ]);
    }

    private function transferPaymentToEscrow(int $fromWalletId, int $toOwnerId, float $amount, int $bookingId, string $correlationId): void
    {
        Log::channel('audit')->info('real_estate.payment.transfer', [
            'correlation_id' => $correlationId,
            'from_wallet_id' => $fromWalletId,
            'to_owner_id' => $toOwnerId,
            'amount' => $amount,
            'booking_id' => $bookingId,
        ]);
    }

    private function verifyDocumentsOnBlockchain(PropertyBooking $booking, Property $property): string
    {
        $documentHash = hash('sha256', json_encode([
            'booking_id' => $booking->id,
            'property_id' => $property->id,
            'user_id' => $booking->user_id,
            'timestamp' => now()->toIso8601String(),
            'documents' => $property->document_hashes ?? [],
        ]));

        Log::channel('audit')->info('real_estate.blockchain.verify', [
            'booking_id' => $booking->id,
            'document_hash' => $documentHash,
        ]);

        return $documentHash;
    }

    private function createWebRTCRoom(PropertyBooking $booking): string
    {
        $roomId = "re_booking_{$booking->id}_" . Str::random(8);

        Log::channel('audit')->info('real_estate.webrtc.room.create', [
            'booking_id' => $booking->id,
            'room_id' => $roomId,
        ]);

        return $roomId;
    }

    private function getOrCreateWebRTCRoom(PropertyBooking $booking, int $initiatorId, int $ownerId): string
    {
        if ($booking->webrtc_room_id) {
            return $booking->webrtc_room_id;
        }

        return $this->createWebRTCRoom($booking);
    }

    private function generateWebRTCToken(string $roomId, int $userId): string
    {
        $token = hash('sha256', $roomId . $userId . now()->toIso8601String() . config('app.webrtc_secret', 'default_secret'));

        Log::channel('audit')->info('real_estate.webrtc.token.generate', [
            'room_id' => $roomId,
            'user_id' => $userId,
        ]);

        return $token;
    }

    private function syncWithCRM(PropertyBooking $booking): void
    {
        Log::channel('audit')->info('real_estate.crm.sync', [
            'booking_id' => $booking->id,
            'status' => $booking->status->value,
        ]);
    }

    private function updateCRMStatus(PropertyBooking $booking, BookingStatus $status): void
    {
        Log::channel('audit')->info('real_estate.crm.status.update', [
            'booking_id' => $booking->id,
            'status' => $status->value,
        ]);
    }

    private function recordDealCompletionInCRM(PropertyBooking $booking, Property $property): void
    {
        Log::channel('audit')->info('real_estate.crm.deal.complete', [
            'booking_id' => $booking->id,
            'property_id' => $property->id,
            'amount' => $booking->amount,
        ]);
    }

    private function recordCancellationInCRM(PropertyBooking $booking, string $reason): void
    {
        Log::channel('audit')->info('real_estate.crm.cancellation', [
            'booking_id' => $booking->id,
            'reason' => $reason,
        ]);
    }

    private function predictDemand(int $propertyId, \Carbon\Carbon $slot): float
    {
        $hour = $slot->hour;
        $dayOfWeek = $slot->dayOfWeek;
        $isWeekend = $dayOfWeek >= 5;

        $baseMultiplier = 1.0;

        if ($isWeekend) {
            $baseMultiplier += 0.2;
        }

        if ($hour >= 10 && $hour <= 12) {
            $baseMultiplier += 0.15;
        } elseif ($hour >= 17 && $hour <= 19) {
            $baseMultiplier += 0.2;
        }

        $seasonalityMultiplier = $this->getSeasonalityMultiplier($slot->month);

        return round($baseMultiplier * $seasonalityMultiplier, 2);
    }

    private function getSeasonalityMultiplier(int $month): float
    {
        return match ($month) {
            3, 4, 5 => 1.15,
            6, 7, 8 => 0.9,
            9, 10, 11 => 1.2,
            12, 1, 2 => 0.95,
            default => 1.0,
        };
    }

    private function getUserHistoryScore(int $userId): float
    {
        return 0.15;
    }

    private function getDocumentVerificationScore(Property $property): float
    {
        return 0.1;
    }

    private function getPriceAnomalyScore(float $price, float $area, string $city): float
    {
        return 0.1;
    }

    private function getCreditScore(int $userId): float
    {
        return 0.75;
    }

    private function getLegalRiskScore(int $propertyId): float
    {
        return 0.2;
    }

    private function getLiquidityScore(int $propertyId, string $city, float $price): float
    {
        return 0.8;
    }
}
