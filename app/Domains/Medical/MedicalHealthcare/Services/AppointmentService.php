<?php declare(strict_types=1);

namespace App\Domains\Medical\MedicalHealthcare\Services;

use App\Domains\Medical\Models\MedicalAppointment;
use App\Domains\Medical\Models\Doctor;
use App\Services\FraudControlService;
use App\Services\Payment\PaymentService;
use Illuminate\Database\DatabaseManager;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Redis;
use Illuminate\Support\Str;

final class AppointmentService
{
    private const SLOT_HOLD_MINUTES = 15;
    private const SLOT_HOLD_EXTENDED_MINUTES = 60;

    public function __construct(
        private FraudControlService $fraud,
        private PaymentServiceAdapter $payment,
        private CircuitBreakerService $circuitBreaker,
        private PaymentMetricsService $paymentMetrics,
        private AtomicWalletOperationsService $atomicWallet,
        private PricingEngineService $pricingEngine,
        private DatabaseManager $db,
    ) {
    }

    public function holdAppointmentSlot(int $doctorId, string $dateTime, int $userId, bool $extendedHold = false, string $correlationId = ''): array
    {
        $this->fraud->check(
            userId: $userId,
            operationType: 'slot_hold',
            amount: 0,
            correlationId: $correlationId,
        );

        $holdMinutes = $extendedHold ? self::SLOT_HOLD_EXTENDED_MINUTES : self::SLOT_HOLD_MINUTES;
        $holdKey = "healthcare:slot:hold:{$doctorId}:{$dateTime}";

        if (Redis::exists($holdKey)) {
            return [
                'success' => false,
                'message' => 'Слот уже забронирован другим пользователем',
                'hold_until' => null,
            ];
        }

        $holdUntil = now()->addMinutes($holdMinutes);
        Redis::setex($holdKey, $holdMinutes * 60, json_encode([
            'user_id' => $userId,
            'held_at' => now()->toIso8601String(),
            'correlation_id' => $correlationId,
        ]));

        Log::channel('audit')->info('Appointment slot held', [
            'user_id' => $userId,
            'doctor_id' => $doctorId,
            'datetime' => $dateTime,
            'hold_until' => $holdUntil->toIso8601String(),
            'correlation_id' => $correlationId,
            'extended' => $extendedHold,
        ]);

        return [
            'success' => true,
            'message' => 'Слот успешно забронирован',
            'hold_until' => $holdUntil->toIso8601String(),
            'hold_id' => $holdKey,
        ];
    }

    public function confirmAppointmentWithPayment(int $userId, int $doctorId, string $dateTime, array $paymentData, string $correlationId = '', callable $calculatePrice): MedicalAppointment
    {
        $this->fraud->check(
            userId: $userId,
            operationType: 'appointment_booking',
            amount: intval($paymentData['amount'] ?? 0),
            correlationId: $correlationId,
        );

        return $this->db->transaction(function () use ($userId, $doctorId, $dateTime, $paymentData, $correlationId, $calculatePrice) {
            $holdKey = "healthcare:slot:hold:{$doctorId}:{$dateTime}";
            $holdData = Redis::get($holdKey);

            if ($holdData === null) {
                throw new \RuntimeException('Время удержания слота истекло. Пожалуйста, выберите другое время.');
            }

            $holdInfo = json_decode($holdData, true);
            if (intval($holdInfo['user_id']) !== $userId) {
                throw new \RuntimeException('Этот слот забронирован другим пользователем.');
            }

            $doctor = Doctor::findOrFail($doctorId);

            // Use unified PricingEngine instead of local calculatePrice callback
            $pricingResult = $this->pricingEngine->calculatePrice(
                'medical',
                $doctor->base_price ?? 500000,
                [
                    'business_group_id' => $paymentData['business_group_id'] ?? null,
                    'demand_factor' => $paymentData['demand_factor'] ?? 1.0,
                    'supply_factor' => $paymentData['supply_factor'] ?? 1.0,
                    'timestamp' => now(),
                ]
            );
            $finalPrice = $pricingResult['final_price'];

            // Check circuit breaker before payment
            $provider = $paymentData['provider'] ?? 'tinkoff';
            if ($this->circuitBreaker->isOpen($provider)) {
                throw new \RuntimeException('Payment gateway temporarily unavailable. Please try again later.');
            }

            $startTime = microtime(true);

            // Initiate payment through PaymentService (now async)
            $payment = $this->payment->initPayment(
                amount: $finalPrice,
                tenantId: $this->getTenantId(),
                userId: $userId,
                paymentMethod: $paymentData['payment_method'] ?? 'card',
                hold: true,
                idempotencyKey: $paymentData['idempotency_key'] ?? null,
                correlationId: $correlationId,
                metadata: array_merge($paymentData, [
                    'doctor_id' => $doctorId,
                    'appointment_datetime' => $dateTime,
                    'provider' => $provider,
                ])
            );

            $duration = microtime(true) - $startTime;

            // Record metrics
            $this->paymentMetrics->recordPaymentAttempt($provider);
            $this->paymentMetrics->recordPaymentLatency($provider, 'init', $duration);
            $this->circuitBreaker->recordSuccess($provider);

            $appointment = MedicalAppointment::create([
                'uuid' => Str::uuid()->toString(),
                'tenant_id' => $this->getTenantId(),
                'business_group_id' => $paymentData['business_group_id'] ?? null,
                'user_id' => $userId,
                'doctor_id' => $doctorId,
                'clinic_id' => $doctor->clinic_id,
                'appointment_datetime' => $dateTime,
                'status' => 'confirmed',
                'consultation_type' => $paymentData['consultation_type'] ?? 'in_person',
                'price' => $finalPrice,
                'payment_transaction_id' => $payment->id,
                'correlation_id' => $correlationId,
                'tags' => json_encode(['ai_diagnostic_flow', 'dynamic_pricing']),
            ]);

            Redis::del($holdKey);

            Log::channel('audit')->info('Appointment confirmed with payment', [
                'appointment_id' => $appointment->id,
                'user_id' => $userId,
                'doctor_id' => $doctorId,
                'amount' => $finalPrice,
                'payment_uuid' => $payment->uuid,
                'correlation_id' => $correlationId,
                'pricing_rules' => $pricingResult['applied_rules'],
            ]);

            return $appointment;
        });
    }

    public function processInstantCheckIn(string $qrCode, string $nfcData, int $userId, string $correlationId = ''): array
    {
        $this->fraud->check(
            userId: $userId,
            operationType: 'instant_checkin',
            amount: 0,
            correlationId: $correlationId,
        );

        $checkInData = json_decode(base64_decode($qrCode), true);

        if ($checkInData === null || !isset($checkInData['appointment_id'])) {
            throw new \RuntimeException('Неверный формат QR-кода.');
        }

        $appointment = MedicalAppointment::with(['doctor', 'clinic'])->findOrFail($checkInData['appointment_id']);

        if ($appointment->user_id !== $userId) {
            throw new \RuntimeException('Этот QR-код принадлежит другому пациенту.');
        }

        if ($appointment->status !== 'confirmed') {
            throw new \RuntimeException('Консультация не подтверждена или уже завершена.');
        }

        $allowedTimeWindow = now()->subMinutes(30)->lte($appointment->appointment_datetime)
            && now()->addMinutes(15)->gte($appointment->appointment_datetime);

        if (!$allowedTimeWindow) {
            throw new \RuntimeException('Чек-ин доступен только за 30 минут до начала и в течение 15 минут после.');
        }

        $appointment->update([
            'status' => 'checked_in',
            'check_in_time' => now(),
            'check_in_method' => $nfcData !== '' ? 'nfc' : 'qr',
        ]);

        Log::channel('audit')->info('Instant check-in completed', [
            'appointment_id' => $appointment->id,
            'user_id' => $appointment->user_id,
            'method' => $nfcData !== '' ? 'nfc' : 'qr',
            'correlation_id' => $correlationId,
        ]);

        return [
            'success' => true,
            'appointment_id' => $appointment->id,
            'doctor_name' => $appointment->doctor->name,
            'clinic_name' => $appointment->clinic->name,
            'room_number' => $appointment->doctor->room_number ?? 'Уточните у администратора',
            'estimated_wait_time' => $this->calculateEstimatedWaitTime($appointment->doctor_id),
            'status' => 'checked_in',
        ];
    }

    private function calculateEstimatedWaitTime(int $doctorId): int
    {
        $checkedInCount = MedicalAppointment::where('doctor_id', $doctorId)
            ->where('status', 'checked_in')
            ->whereDate('check_in_time', today())
            ->count();

        return $checkedInCount * 15;
    }

    private function getTenantId(): int
    {
        if (function_exists('tenant') && tenant() !== null) {
            return intval(tenant()->id);
        }
        return 1;
    }
}
