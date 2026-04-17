<?php declare(strict_types=1);

namespace App\Domains\Beauty\Services;

use App\Domains\Beauty\DTOs\BookAppointmentDto;
use App\Domains\Beauty\Models\Appointment;
use App\Domains\Beauty\Models\BeautyService;
use App\Domains\Beauty\Models\BookingSlot;
use App\Domains\Beauty\Models\Master;
use App\Domains\Beauty\Models\Salon;
use App\Domains\Beauty\Services\AI\BeautyImageConstructorService;
use App\Services\AuditService;
use App\Services\FraudControlService;
use App\Services\SpamProtectionService;
use App\Services\WalletService;
use App\Services\ML\UserTasteAnalyzerService;
use Illuminate\Database\ConnectionInterface;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Log\Logger;
use Illuminate\Support\Str;
use RuntimeException;

final readonly class BeautyBookingService
{
    public function __construct(
        private FraudControlService $fraudControl,
        private AuditService $auditService,
        private BookingSlotHoldService $slotHoldService,
        private BeautyImageConstructorService $aiConstructor,
        private UserTasteAnalyzerService $tasteAnalyzer,
        private WalletService $walletService,
        private SpamProtectionService $spamProtection,
        private ConnectionInterface $db,
        private Logger $logger,
        private Request $request,
    ) {}

    public function bookAppointment(BookAppointmentDto $dto): Appointment
    {
        $correlationId = $dto->correlationId ?? Str::uuid()->toString();

        $this->fraudControl->check(
            userId: $dto->userId,
            operationType: 'beauty_booking',
            amount: 0,
            ipAddress: null,
            deviceFingerprint: null,
            correlationId: $correlationId,
        );

        $spamCheck = $this->spamProtection->checkSpam(
            userId: $dto->userId,
            action: 'be$this->auty_boing',
            ipAddress: request()->ip(),
            correlationId: $correlationId,
        );

        if ($spamCheck['is_blacklisted']) {
            throw new RuntimeException('Spam detected: account temporarily blocked');
        }

        $this->logger->channel('audit')->info('beauty.booking.start', [
            'correlation_id' => $correlationId,
            'tenant_id' => $dto->tenantId,
            'salon_id' => $dto->salonId,
            'master_id' => $dto->masterId,
            'service_id' => $dto->serviceId,
            'user_id' => $dto->userId,
            'is_b2b' => $dto->isB2b,
        ]);

        return $this->db->transaction(function () use ($dto, $correlationId) {
            $salon = Salon::where('id', $dto->salonId)
                ->where('tenant_id', $dto->tenantId)
                ->where('is_active', true)
                ->first();

            if ($salon === null) {
                throw new RuntimeException('Salon not found or inactive');
            }

            $master = Master::where('id', $dto->masterId)
                ->where('salon_id', $dto->salonId)
                ->where('is_active', true)
                ->first();

            if ($master === null) {
                throw new RuntimeException('Master not found or inactive');
            }

            $service = BeautyService::where('id', $dto->serviceId)
                ->where('tenant_id', $dto->tenantId)
                ->where('is_active', true)
                ->first();

            if ($service === null) {
                throw new RuntimeException('Service not found or inactive');
            }

            $dynamicPrice = $this->calculateDynamicPrice($service, $dto->isB2b, $correlationId);
            $flashDiscount = $this->calculateFlashDiscount($dto->userId, $dto->salonId, $correlationId);
            $finalPrice = max(0, $dynamicPrice - $flashDiscount);

            $startsAt = \Carbon\Carbon::parse($dto->startsAt);
            $endsAt = $startsAt->copy()->addMinutes($service->duration_minutes);

            $appointment = Appointment::create([
                'tenant_id' => $dto->tenantId,
                'salon_id' => $dto->salonId,
                'master_id' => $dto->masterId,
                'service_id' => $dto->serviceId,
                'user_id' => $dto->userId,
                'uuid' => Str::uuid()->toString(),
                'correlation_id' => $correlationId,
                'status' => 'pending_payment',
                'starts_at' => $startsAt,
                'ends_at' => $endsAt,
                'total_price' => $finalPrice,
                'is_b2b' => $dto->isB2b,
                'metadata' => [
                    'original_price' => $service->price,
                    'dynamic_price' => $dynamicPrice,
                    'flash_discount' => $flashDiscount,
                    'commission_rate' => $dto->isB2b ? 0.12 : 0.14,
                    'commission_amount' => $finalPrice * ($dto->isB2b ? 0.12 : 0.14),
                ],
            ]);

            $this->auditService->record(
                action: 'beauty_appointment_created',
                subjectType: Appointment::class,
                subjectId: $appointment->id,
                oldValues: [],
                newValues: [
                    'salon_id' => $dto->salonId,
                    'master_id' => $dto->masterId,
                    'service_id' => $dto->serviceId,
                    'user_id' => $dto->userId,
                    'total_price' => $finalPrice,
                    'is_b2b' => $dto->isB2b,
                ],
                correlationId: $correlationId,
            );



            $this->logger->channel('audit')->info('beauty.booking.success', [
                'correlation_id' => $correlationId,
                'appointment_id' => $appointment->id,
                'appointment_uuid' => $appointment->uuid,
                'total_price' => $finalPrice,
            ]);

            return $appointment->fresh();
        });
    }

    public function matchMastersByPhoto(UploadedFile $photo, int $userId, int $tenantId, string $correlationId): array
    {
        $correlationId = $correlationId ?: Str::uuid()->toString();

        $this->fraudControl->check(
            userId: $userId,
            operationType: 'beauty_ai_matching',
            amount: 0,
            ipAddress: null,
            deviceFingerprint: null,
            correlationId: $correlationId,
        );

        $this->logger->channel('audit')->info('beauty.ai_matching.start', [
            'correlation_id' => $correlationId,
            'user_id' => $userId,
            'tenant_id' => $tenantId,
        ]);

        $aiResult = $this->aiConstructor->analyzePhotoAndRecommend($photo, $userId, $correlationId);
        $styleProfile = $aiResult['payload'] ?? [];

        $tasteProfile = $this->tasteAnalyzer->getProfile($userId);
        $mergedProfile = array_merge($styleProfile, $tasteProfile->beauty_preferences ?? []);

        $recommendations = [];

        $masters = Master::where('tenant_id', $tenantId)
            ->where('is_active', true)
            ->whereIn('specialization', $mergedProfile['recommended_styles'] ?? [])
            ->with(['salon', 'services'])
            ->get()
            ->map(function ($master) use ($mergedProfile, $recommendations) {
                $matchScore = $this->calculateMasterMatchScore($master, $mergedProfile);
                $isTopRated = $master->rating >= 4.8;
                $hasFlashDiscount = $this->hasActiveFlashDiscount($master->id);

                return [
                    'master_id' => $master->id,
                    'name' => $master->name,
                    'salon_id' => $master->salon_id,
                    'salon_name' => $master->salon->name ?? '',
                    'specialization' => $master->specialization,
                    'rating' => $master->rating,
                    'experience_years' => $master->experience_years,
                    'match_score' => $matchScore,
                    'is_top_rated' => $isTopRated,
                    'has_flash_discount' => $hasFlashDiscount,
                    'services' => $master->services->map(function ($service) {
                        return [
                            'service_id' => $service->id,
                            'name' => $service->name,
                            'price' => $service->price,
                            'duration_minutes' => $service->duration_minutes,
                        ];
                    })->toArray(),
                ];
            })
            ->sortByDesc('match_score')
            ->values()
            ->take(5)
            ->toArray();

        $this->auditService->record(
                action: 'beauty_ai_matching_completed',
                subjectType: 'User',
                subjectId: $userId,
                oldValues: [],
                newValues: [
                    'masters_count' => count($masters),
                    'style_profile' => $mergedProfile,
                ],
                correlationId: $correlationId,
            );

        $this->logger->channel('audit')->info('beauty.ai_matching.success', [
            'correlation_id' => $correlationId,
            'user_id' => $userId,
            'masters_found' => count($masters),
        ]);

        return [
            'success' => true,
            'style_profile' => $mergedProfile,
            'ar_preview_url' => $aiResult['s3_path'] ? url('/beauty/ar-preview/' . $userId) : null,
            'recommended_masters' => $masters,
            'correlation_id' => $correlationId,
        ];
    }

    public function initiateVideoCall(int $appointmentId, int $userId, int $tenantId, string $correlationId): array
    {
        $correlationId = $correlationId ?: Str::uuid()->toString();

        $this->fraudControl->check(
            userId: $userId,
            operationType: 'beauty_video_call',
            amount: 0,
            ipAddress: null,
            deviceFingerprint: null,
            correlationId: $correlationId,
        );

        $appointment = Appointment::where('id', $appointmentId)
            ->where('user_id', $userId)
            ->where('tenant_id', $tenantId)
            ->where('status', 'pending_payment')
            ->first();

        if ($appointment === null) {
            throw new RuntimeException('Appointment not found or invalid status for video call');
        }

        $webrtcRoomId = 'beauty_call_' . $appointment->uuid;
        $webrtcToken = hash('sha256', $webrtcRoomId . $correlationId . now()->timestamp);
        $callExpiresAt = now()->addMinutes(10);

        $appointment->update([
            'metadata' => array_merge($appointment->metadata ?? [], [
                'webrtc_room_id' => $webrtcRoomId,
                'webrtc_token' => $webrtcToken,
                'video_call_expires_at' => $callExpiresAt->toIso8601String(),
            ]),
        ]);


        $this->auditService->record(
                action: 'beauty_video_call_initiated',
                subjectType: Appointment::class,
                subjectId: $appointment->id,
                oldValues: [],
                newValues: [
                    'webrtc_room_id' => $webrtcRoomId,
                    'expires_at' => $callExpiresAt->toIso8601String(),
                ],
                correlationId: $correlationId,
            );

        $this->logger->channel('audit')->info('beauty.video_call.initiated', [
            'correlation_id' => $correlationId,
            'appointment_id' => $appointmentId,
            'webrtc_room_id' => $webrtcRoomId,
        ]);

        return [
            'success' => true,
            'webrtc_room_id' => $webrtcRoomId,
            'webrtc_token' => $webrtcToken,
            'expires_at' => $callExpiresAt->toIso8601String(),
            'signaling_server' => config('services.webrtc.signaling_server', 'wss://webrtc.catvrf.ru'),
            'turn_servers' => config('services.webrtc.turn_servers', []),
            'correlation_id' => $correlationId,
        ];
    }

    public function processPaymentWithSplit(int $appointmentId, int $userId, int $tenantId, array $paymentSplit, string $correlationId): Appointment
    {
        $correlationId = $correlationId ?: Str::uuid()->toString();

        $this->fraudControl->check(
            userId: $userId,
            operationType: 'beauty_payment_split',
            amount: array_sum($paymentSplit),
            ipAddress: null,
            deviceFingerprint: null,
            correlationId: $correlationId,
        );

        return $this->db->transaction(function () use ($appointmentId, $userId, $tenantId, $paymentSplit, $correlationId) {
            $appointment = Appointment::where('id', $appointmentId)
                ->where('user_id', $userId)
                ->where('tenant_id', $tenantId)
                ->where('status', 'pending_payment')
                ->lockForUpdate()
                ->first();

            if ($appointment === null) {
                throw new RuntimeException('Appointment not found or invalid status');
            }

            $totalAmount = array_sum($paymentSplit);
            if (abs($totalAmount - $appointment->total_price) > 0.01) {
                throw new RuntimeException('Payment amount does not match appointment total');
            }

            $paymentResults = [];
            $paidAmount = 0;

            foreach ($paymentSplit as $method => $amount) {
                if ($amount <= 0) {
                    continue;
                }

                if ($method === 'wallet') {
                    $walletResult = $this->walletService->debit(
                        walletId: $this->getUserWalletId($userId, $tenantId),
                        amount: (int) ($amount * 100),
                        reason: 'beauty_appointment_payment',
                        correlationId: $correlationId,
                    );
                    $paymentResults[$method] = $walletResult;
                    $paidAmount += $amount;
                } elseif ($method === 'card') {
                    throw new RuntimeException('Card payment not implemented in this version');
                }
            }

            if ($paidAmount < $appointment->total_price) {
                throw new RuntimeException('Payment processing failed: insufficient amount paid');
            }

            $appointment->update([
                'status' => 'confirmed',
                'metadata' => array_merge($appointment->metadata ?? [], [
                    'payment_split' => $paymentSplit,
                    'payment_results' => $paymentResults,
                    'paid_at' => now()->toIso8601String(),
                ]),
            ]);

            $commissionAmount = $appointment->total_price * ($appointment->is_b2b ? 0.12 : 0.14);

            $cashbackAmount = 0.0;


            $this->auditService->record(
                action: 'beauty_appointment_paid',
                subjectType: Appointment::class,
                subjectId: $appointment->id,
                oldValues: [],
                newValues: [
                    'payment_split' => $paymentSplit,
                    'total_paid' => $paidAmount,
                    'cashback_awarded' => $cashbackAmount,
                ],
                correlationId: $correlationId,
            );

            $this->logger->channel('audit')->info('beauty.payment.success', [
                'correlation_id' => $correlationId,
                'appointment_id' => $appointment->id,
                'total_paid' => $paidAmount,
                'cashback' => $cashbackAmount,
            ]);

            return $appointment->fresh();
        });
    }

    public function cancelAppointment(int $appointmentId, int $userId, int $tenantId, string $reason, string $correlationId): Appointment
    {
        $correlationId = $correlationId ?: Str::uuid()->toString();

        $this->fraudControl->check(
            userId: $userId,
            operationType: 'beauty_cancellation',
            amount: 0,
            ipAddress: null,
            deviceFingerprint: null,
            correlationId: $correlationId,
        );

        return $this->db->transaction(function () use ($appointmentId, $userId, $tenantId, $reason, $correlationId) {
            $appointment = Appointment::where('id', $appointmentId)
                ->where('user_id', $userId)
                ->where('tenant_id', $tenantId)
                ->whereIn('status', ['pending_payment', 'confirmed'])
                ->lockForUpdate()
                ->first();

            if ($appointment === null) {
                throw new RuntimeException('Appointment not found or cannot be cancelled');
            }

            $previousStatus = $appointment->status;
            $refundAmount = 0.0;

            if ($previousStatus === 'confirmed') {
                $hoursBeforeAppointment = now()->diffInHours($appointment->starts_at, false);
                if ($hoursBeforeAppointment >= 24) {
                    $refundAmount = $appointment->total_price;
                } elseif ($hoursBeforeAppointment >= 4) {
                    $refundAmount = $appointment->total_price * 0.5;
                }

                if ($refundAmount > 0) {
                    $this->walletService->credit(
                        walletId: $this->getUserWalletId($userId, $tenantId),
                        amount: (int) ($refundAmount * 100),
                        reason: 'beauty_appointment_cancellation_refund',
                        correlationId: $correlationId,
                    );
                }
            }

            $appointment->update([
                'status' => 'cancelled',
                'cancellation_reason' => $reason,
                'metadata' => array_merge($appointment->metadata ?? [], [
                    'previous_status' => $previousStatus,
                    'cancelled_at' => now()->toIso8601String(),
                    'refund_amount' => $refundAmount,
                ]),
            ]);


            $this->auditService->record(
                action: 'beauty_appointment_cancelled',
                subjectType: Appointment::class,
                subjectId: $appointment->id,
                oldValues: ['status' => $previousStatus],
                newValues: [
                    'status' => 'cancelled',
                    'reason' => $reason,
                    'refund_amount' => $refundAmount,
                ],
                correlationId: $correlationId,
            );

            $this->logger->channel('audit')->info('beauty.cancellation.success', [
                'correlation_id' => $correlationId,
                'appointment_id' => $appointment->id,
                'refund_amount' => $refundAmount,
            ]);

            return $appointment->fresh();
        });
    }

    private function calculateDynamicPrice(BeautyService $service, bool $isB2b, string $correlationId): float
    {
        $basePrice = $service->price;
        $loadFactor = $this->getCurrentSalonLoad($service->tenant_id, $correlationId);
        $timeMultiplier = $this->getTimeBasedMultiplier();
        $b2bDiscount = $isB2b ? 0.85 : 1.0;

        $dynamicPrice = $basePrice * $loadFactor * $timeMultiplier * $b2bDiscount;

        $this->logger->channel('audit')->info('beauty.dynamic_price.calculated', [
            'correlation_id' => $correlationId,
            'service_id' => $service->id,
            'base_price' => $basePrice,
            'load_factor' => $loadFactor,
            'time_multiplier' => $timeMultiplier,
            'is_b2b' => $isB2b,
            'dynamic_price' => $dynamicPrice,
        ]);

        return round($dynamicPrice, 2);
    }

    private function calculateFlashDiscount(int $userId, int $salonId, string $correlationId): float
    {
        $userBookingCount = Appointment::where('user_id', $userId)
            ->where('status', '!=', 'cancelled')
            ->count();

        $salonLoad = $this->getCurrentSalonLoad($salonId, $correlationId);

        $discount = 0.0;

        if ($userBookingCount === 0 && $salonLoad < 0.5) {
            $discount = 300.0;
        } elseif ($userBookingCount >= 5 && $salonLoad < 0.7) {
            $discount = 200.0;
        } elseif ($salonLoad < 0.3) {
            $discount = 150.0;
        }

        $this->logger->channel('audit')->info('beauty.flash_discount.calculated', [
            'correlation_id' => $correlationId,
            'user_id' => $userId,
            'salon_id' => $salonId,
            'user_booking_count' => $userBookingCount,
            'salon_load' => $salonLoad,
            'discount' => $discount,
        ]);

        return $discount;
    }

    private function getCurrentSalonLoad(int $tenantId, string $correlationId): float
    {
        $totalSlots = BookingSlot::where('tenant_id', $tenantId)
            ->where('starts_at', '>=', now())
            ->where('starts_at', '<=', now()->addHours(24))
            ->count();

        $bookedSlots = BookingSlot::where('tenant_id', $tenantId)
            ->where('status', 'booked')
            ->where('starts_at', '>=', now())
            ->where('starts_at', '<=', now()->addHours(24))
            ->count();

        $loadFactor = $totalSlots > 0 ? $bookedSlots / $totalSlots : 0.0;

        $this->logger->channel('audit')->debug('beauty.salon_load.calculated', [
            'correlation_id' => $correlationId,
            'tenant_id' => $tenantId,
            'total_slots' => $totalSlots,
            'booked_slots' => $bookedSlots,
            'load_factor' => $loadFactor,
        ]);

        return $loadFactor;
    }

    private function getTimeBasedMultiplier(): float
    {
        $hour = now()->hour;

        return match (true) {
            $hour >= 9 && $hour < 12 => 1.0,
            $hour >= 12 && $hour < 17 => 1.2,
            $hour >= 17 && $hour < 21 => 1.3,
            default => 0.9,
        };
    }

    private function calculateMasterMatchScore(Master $master, array $styleProfile): float
    {
        $score = 0.5;

        $specializations = is_array($master->specialization) ? $master->specialization : [$master->specialization];
        $recommendedStyles = $styleProfile['recommended_styles'] ?? [];

        foreach ($specializations as $specialization) {
            if (in_array($specialization, $recommendedStyles, true)) {
                $score += 0.3;
            }
        }

        $score += min($master->rating / 5.0, 1.0) * 0.15;
        $score += min($master->experience_years / 10.0, 1.0) * 0.05;

        return min($score, 1.0);
    }

    private function hasActiveFlashDiscount(int $masterId): bool
    {
        return Appointment::where('master_id', $masterId)
            ->where('starts_at', '>=', now())
            ->where('starts_at', '<=', now()->addHours(24))
            ->count() < 3;
    }

    private function getUserWalletId(int $userId, int $tenantId): int
    {
        $wallet = \App\Models\Wallet::where('user_id', $userId)
            ->where('tenant_id', $tenantId)
            ->first();

        if ($wallet === null) {
            $wallet = \App\Models\Wallet::create([
                'user_id' => $userId,
                'tenant_id' => $tenantId,
                'current_balance' => 0,
                'hold_amount' => 0,
                'correlation_id' => Str::uuid()->toString(),
            ]);
        }

        return $wallet->id;
    }
}
