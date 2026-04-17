<?php

declare(strict_types=1);

namespace Tests\Security;

use App\Domains\Sports\DTOs\RealTimeBookingDto;
use App\Domains\Sports\Services\SportsRealTimeBookingService;
use App\Services\FraudControlService;
use App\Services\AuditService;
use Illuminate\Database\DatabaseManager;
use Illuminate\Contracts\Cache\Repository as Cache;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Redis\Connections\Connection as RedisConnection;
use Tests\TestCase;
use Illuminate\Support\Str;
use App\Exceptions\FraudBlockedException;

final class SportsPaymentAttackTest extends TestCase
{
    use RefreshDatabase;

    private SportsRealTimeBookingService $service;
    private FraudControlService $fraud;
    private RedisConnection $redis;

    protected function setUp(): void
    {
        parent::setUp();

        $this->fraud = $this->app->make(FraudControlService::class);
        $this->audit = $this->app->make(AuditService::class);
        $this->db = $this->app->make(DatabaseManager::class);
        $this->cache = $this->app->make(Cache::class);
        $this->redis = $this->app->make('redis')->connection();

        $this->service = new SportsRealTimeBookingService(
            fraud: $this->fraud,
            audit: $this->audit,
            db: $this->db,
            cache: $this->cache,
            logger: $this->app->make('log'),
            redis: $this->redis,
        );
    }

    public function test_zero_amount_payment_blocked(): void
    {
        $dto = new RealTimeBookingDto(
            userId: 1,
            tenantId: 1,
            businessGroupId: null,
            venueId: 1,
            trainerId: null,
            slotStart: now()->addHours(2)->toDateTimeString(),
            slotEnd: now()->addHours(3)->toDateTimeString(),
            bookingType: 'general',
            biometricData: [],
            extendedHold: false,
            correlationId: Str::uuid()->toString(),
        );

        $holdResult = $this->service->holdSlot($dto);
        $this->assertTrue($holdResult['success']);

        try {
            $confirmResult = $this->service->confirmBooking($dto, [
                'amount' => 0,
                'transaction_id' => null,
                'payment_method' => 'card',
            ]);

            $this->assertFalse($confirmResult['success'] ?? true, 'Zero amount payment should be blocked');
        } catch (FraudBlockedException $e) {
            $this->assertTrue(true, 'Zero amount should trigger fraud detection');
        }
    }

    public function test_negative_amount_payment_blocked(): void
    {
        $dto = new RealTimeBookingDto(
            userId: 1,
            tenantId: 1,
            businessGroupId: null,
            venueId: 1,
            trainerId: null,
            slotStart: now()->addHours(2)->toDateTimeString(),
            slotEnd: now()->addHours(3)->toDateTimeString(),
            bookingType: 'general',
            biometricData: [],
            extendedHold: false,
            correlationId: Str::uuid()->toString(),
        );

        $holdResult = $this->service->holdSlot($dto);
        $this->assertTrue($holdResult['success']);

        try {
            $confirmResult = $this->service->confirmBooking($dto, [
                'amount' => -100,
                'transaction_id' => null,
                'payment_method' => 'card',
            ]);

            $this->assertFalse($confirmResult['success'] ?? true, 'Negative amount should be blocked');
        } catch (FraudBlockedException $e) {
            $this->assertTrue(true, 'Negative amount should trigger fraud detection');
        }
    }

    public function test_extremely_high_amount_blocked(): void
    {
        $dto = new RealTimeBookingDto(
            userId: 1,
            tenantId: 1,
            businessGroupId: null,
            venueId: 1,
            trainerId: null,
            slotStart: now()->addHours(2)->toDateTimeString(),
            slotEnd: now()->addHours(3)->toDateTimeString(),
            bookingType: 'general',
            biometricData: [],
            extendedHold: false,
            correlationId: Str::uuid()->toString(),
        );

        $holdResult = $this->service->holdSlot($dto);
        $this->assertTrue($holdResult['success']);

        try {
            $confirmResult = $this->service->confirmBooking($dto, [
                'amount' => 999999999.99,
                'transaction_id' => null,
                'payment_method' => 'card',
            ]);

            $this->assertFalse($confirmResult['success'] ?? true, 'Extremely high amount should be blocked');
        } catch (FraudBlockedException $e) {
            $this->assertTrue(true, 'Extremely high amount should trigger fraud detection');
        }
    }

    public function test_duplicate_transaction_id_blocked(): void
    {
        $transactionId = 'TXN-' . Str::random(32);

        $dto1 = new RealTimeBookingDto(
            userId: 1,
            tenantId: 1,
            businessGroupId: null,
            venueId: 1,
            trainerId: null,
            slotStart: now()->addHours(2)->toDateTimeString(),
            slotEnd: now()->addHours(3)->toDateTimeString(),
            bookingType: 'general',
            biometricData: [],
            extendedHold: false,
            correlationId: Str::uuid()->toString(),
        );

        $holdResult1 = $this->service->holdSlot($dto1);
        $this->assertTrue($holdResult1['success']);

        $confirmResult1 = $this->service->confirmBooking($dto1, [
            'amount' => 100,
            'transaction_id' => $transactionId,
            'payment_method' => 'card',
        ]);

        $dto2 = new RealTimeBookingDto(
            userId: 2,
            tenantId: 1,
            businessGroupId: null,
            venueId: 1,
            trainerId: null,
            slotStart: now()->addHours(4)->toDateTimeString(),
            slotEnd: now()->addHours(5)->toDateTimeString(),
            bookingType: 'general',
            biometricData: [],
            extendedHold: false,
            correlationId: Str::uuid()->toString(),
        );

        $holdResult2 = $this->service->holdSlot($dto2);
        $this->assertTrue($holdResult2['success']);

        try {
            $confirmResult2 = $this->service->confirmBooking($dto2, [
                'amount' => 100,
                'transaction_id' => $transactionId,
                'payment_method' => 'card',
            ]);

            $this->assertFalse($confirmResult2['success'] ?? true, 'Duplicate transaction ID should be blocked');
        } catch (FraudBlockedException $e) {
            $this->assertTrue(true, 'Duplicate transaction ID should trigger fraud detection');
        }
    }

    public function test_suspicious_payment_methods_blocked(): void
    {
        $suspiciousPaymentMethods = [
            'crypto_mixer',
            'anonymous_card',
            'prepaid_vpn',
            'shell_company',
        ];

        foreach ($suspiciousPaymentMethods as $paymentMethod) {
            $dto = new RealTimeBookingDto(
                userId: 1,
                tenantId: 1,
                businessGroupId: null,
                venueId: 1,
                trainerId: null,
                slotStart: now()->addHours(2)->toDateTimeString(),
                slotEnd: now()->addHours(3)->toDateTimeString(),
                bookingType: 'general',
                biometricData: [],
                extendedHold: false,
                correlationId: Str::uuid()->toString(),
            );

            $holdResult = $this->service->holdSlot($dto);
            $this->assertTrue($holdResult['success']);

            try {
                $confirmResult = $this->service->confirmBooking($dto, [
                    'amount' => 100,
                    'transaction_id' => null,
                    'payment_method' => $paymentMethod,
                ]);

                $this->assertFalse($confirmResult['success'] ?? true, 'Suspicious payment method should be blocked');
            } catch (FraudBlockedException $e) {
                $this->assertTrue(true, 'Suspicious payment method should trigger fraud detection');
            }
        }
    }

    public function test_rapid_payment_attempts_blocked(): void
    {
        $userId = 1;
        $rapidAttempts = 20;

        for ($i = 0; $i < $rapidAttempts; $i++) {
            $dto = new RealTimeBookingDto(
                userId: $userId,
                tenantId: 1,
                businessGroupId: null,
                venueId: 1,
                trainerId: null,
                slotStart: now()->addHours(2 + $i)->toDateTimeString(),
                slotEnd: now()->addHours(3 + $i)->toDateTimeString(),
                bookingType: 'general',
                biometricData: [],
                extendedHold: false,
                correlationId: Str::uuid()->toString(),
            );

            try {
                $holdResult = $this->service->holdSlot($dto);
                
                if ($holdResult['success']) {
                    $confirmResult = $this->service->confirmBooking($dto, [
                        'amount' => 100,
                        'transaction_id' => 'TXN-' . Str::random(32),
                        'payment_method' => 'card',
                    ]);

                    if ($i >= 10) {
                        $this->assertFalse($confirmResult['success'] ?? true, 'Should block rapid payment attempts');
                    }
                }
            } catch (FraudBlockedException $e) {
                $this->assertTrue($i >= 10, 'Should trigger fraud detection after rapid attempts');
                break;
            }
        }
    }

    public function test_payment_from_suspicious_countries_blocked(): void
    {
        $suspiciousCountries = ['XX', 'YY', 'ZZ', 'AA', 'BB'];

        foreach ($suspiciousCountries as $country) {
            $dto = new RealTimeBookingDto(
                userId: 1,
                tenantId: 1,
                businessGroupId: null,
                venueId: 1,
                trainerId: null,
                slotStart: now()->addHours(2)->toDateTimeString(),
                slotEnd: now()->addHours(3)->toDateTimeString(),
                bookingType: 'general',
                biometricData: ['payment_country' => $country],
                extendedHold: false,
                correlationId: Str::uuid()->toString(),
            );

            $holdResult = $this->service->holdSlot($dto);
            $this->assertTrue($holdResult['success']);

            try {
                $confirmResult = $this->service->confirmBooking($dto, [
                    'amount' => 100,
                    'transaction_id' => null,
                    'payment_method' => 'card',
                ]);

                $this->assertFalse($confirmResult['success'] ?? true, 'Payment from suspicious country should be blocked');
            } catch (FraudBlockedException $e) {
                $this->assertTrue(true, 'Suspicious country should trigger fraud detection');
            }
        }
    }

    public function test_payment_with_invalid_card_format_blocked(): void
    {
        $invalidCardNumbers = [
            '0000000000000000',
            '1111111111111111',
            '1234567890123456',
            '9999999999999999',
            str_repeat('0', 20),
        ];

        foreach ($invalidCardNumbers as $cardNumber) {
            $dto = new RealTimeBookingDto(
                userId: 1,
                tenantId: 1,
                businessGroupId: null,
                venueId: 1,
                trainerId: null,
                slotStart: now()->addHours(2)->toDateTimeString(),
                slotEnd: now()->addHours(3)->toDateTimeString(),
                bookingType: 'general',
                biometricData: ['card_number' => $cardNumber],
                extendedHold: false,
                correlationId: Str::uuid()->toString(),
            );

            $holdResult = $this->service->holdSlot($dto);
            $this->assertTrue($holdResult['success']);

            try {
                $confirmResult = $this->service->confirmBooking($dto, [
                    'amount' => 100,
                    'transaction_id' => null,
                    'payment_method' => 'card',
                ]);

                $this->assertFalse($confirmResult['success'] ?? true, 'Invalid card format should be blocked');
            } catch (FraudBlockedException $e) {
                $this->assertTrue(true, 'Invalid card format should trigger fraud detection');
            }
        }
    }

    public function test_refund_after_cancellation_blocked(): void
    {
        $dto = new RealTimeBookingDto(
            userId: 1,
            tenantId: 1,
            businessGroupId: null,
            venueId: 1,
            trainerId: null,
            slotStart: now()->addHours(2)->toDateTimeString(),
            slotEnd: now()->addHours(3)->toDateTimeString(),
            bookingType: 'general',
            biometricData: [],
            extendedHold: false,
            correlationId: Str::uuid()->toString(),
        );

        $holdResult = $this->service->holdSlot($dto);
        $this->assertTrue($holdResult['success']);

        $confirmResult = $this->service->confirmBooking($dto, [
            'amount' => 100,
            'transaction_id' => 'TXN-' . Str::random(32),
            'payment_method' => 'card',
        ]);

        $this->service->releaseSlot(1, null, $dto->slotStart, 1, $dto->correlationId);

        try {
            $refundResult = $this->service->processRefund($dto['booking_id'] ?? 0, [
                'amount' => 100,
                'reason' => 'cancellation',
            ]);

            $this->assertFalse($refundResult['success'] ?? true, 'Refund after cancellation should be blocked');
        } catch (FraudBlockedException $e) {
            $this->assertTrue(true, 'Refund after cancellation should trigger fraud detection');
        }
    }

    public function test_multiple_refunds_for_same_booking_blocked(): void
    {
        $dto = new RealTimeBookingDto(
            userId: 1,
            tenantId: 1,
            businessGroupId: null,
            venueId: 1,
            trainerId: null,
            slotStart: now()->addHours(2)->toDateTimeString(),
            slotEnd: now()->addHours(3)->toDateTimeString(),
            bookingType: 'general',
            biometricData: [],
            extendedHold: false,
            correlationId: Str::uuid()->toString(),
        );

        $holdResult = $this->service->holdSlot($dto);
        $this->assertTrue($holdResult['success']);

        $confirmResult = $this->service->confirmBooking($dto, [
            'amount' => 100,
            'transaction_id' => 'TXN-' . Str::random(32),
            'payment_method' => 'card',
        ]);

        try {
            $this->service->processRefund($confirmResult['booking_id'] ?? 0, [
                'amount' => 100,
                'reason' => 'refund',
            ]);

            $secondRefundResult = $this->service->processRefund($confirmResult['booking_id'] ?? 0, [
                'amount' => 100,
                'reason' => 'duplicate_refund',
            ]);

            $this->assertFalse($secondRefundResult['success'] ?? true, 'Multiple refunds should be blocked');
        } catch (FraudBlockedException $e) {
            $this->assertTrue(true, 'Multiple refunds should trigger fraud detection');
        }
    }

    public function test_price_manipulation_blocked(): void
    {
        $dto = new RealTimeBookingDto(
            userId: 1,
            tenantId: 1,
            businessGroupId: null,
            venueId: 1,
            trainerId: null,
            slotStart: now()->addHours(2)->toDateTimeString(),
            slotEnd: now()->addHours(3)->toDateTimeString(),
            bookingType: 'general',
            biometricData: [],
            extendedHold: false,
            correlationId: Str::uuid()->toString(),
        );

        $holdResult = $this->service->holdSlot($dto);
        $this->assertTrue($holdResult['success']);

        try {
            $confirmResult = $this->service->confirmBooking($dto, [
                'amount' => 1,
                'transaction_id' => null,
                'payment_method' => 'card',
                'original_amount' => 100,
            ]);

            $this->assertFalse($confirmResult['success'] ?? true, 'Price manipulation should be blocked');
        } catch (FraudBlockedException $e) {
            $this->assertTrue(true, 'Price manipulation should trigger fraud detection');
        }
    }

    public function test_split_payment_abuse_blocked(): void
    {
        $dto = new RealTimeBookingDto(
            userId: 1,
            tenantId: 1,
            businessGroupId: null,
            venueId: 1,
            trainerId: null,
            slotStart: now()->addHours(2)->toDateTimeString(),
            slotEnd: now()->addHours(3)->toDateTimeString(),
            bookingType: 'general',
            biometricData: [],
            extendedHold: false,
            correlationId: Str::uuid()->toString(),
        );

        $holdResult = $this->service->holdSlot($dto);
        $this->assertTrue($holdResult['success']);

        try {
            $confirmResult = $this->service->confirmBooking($dto, [
                'amount' => 100,
                'transaction_id' => null,
                'payment_method' => 'split',
                'split_payments' => array_fill(0, 50, 2),
            ]);

            $this->assertFalse($confirmResult['success'] ?? true, 'Excessive split payments should be blocked');
        } catch (FraudBlockedException $e) {
            $this->assertTrue(true, 'Split payment abuse should trigger fraud detection');
        }
    }

    public function test_payment_timing_anomaly_blocked(): void
    {
        $dto = new RealTimeBookingDto(
            userId: 1,
            tenantId: 1,
            businessGroupId: null,
            venueId: 1,
            trainerId: null,
            slotStart: now()->addHours(2)->toDateTimeString(),
            slotEnd: now()->addHours(3)->toDateTimeString(),
            bookingType: 'general',
            biometricData: [],
            extendedHold: false,
            correlationId: Str::uuid()->toString(),
        );

        $holdResult = $this->service->holdSlot($dto);
        $this->assertTrue($holdResult['success']);

        $holdTime = microtime(true);

        try {
            $confirmResult = $this->service->confirmBooking($dto, [
                'amount' => 100,
                'transaction_id' => null,
                'payment_method' => 'card',
                'payment_timestamp' => $holdTime,
            ]);

            $paymentTime = microtime(true) - $holdTime;

            if ($paymentTime < 0.001) {
                $this->assertFalse($confirmResult['success'] ?? true, 'Suspiciously fast payment should be blocked');
            }
        } catch (FraudBlockedException $e) {
            $this->assertTrue(true, 'Payment timing anomaly should trigger fraud detection');
        }
    }

    public function test_cross_tenant_payment_blocked(): void
    {
        $dto = new RealTimeBookingDto(
            userId: 1,
            tenantId: 1,
            businessGroupId: null,
            venueId: 1,
            trainerId: null,
            slotStart: now()->addHours(2)->toDateTimeString(),
            slotEnd: now()->addHours(3)->toDateTimeString(),
            bookingType: 'general',
            biometricData: [],
            extendedHold: false,
            correlationId: Str::uuid()->toString(),
        );

        $holdResult = $this->service->holdSlot($dto);
        $this->assertTrue($holdResult['success']);

        try {
            $confirmResult = $this->service->confirmBooking($dto, [
                'amount' => 100,
                'transaction_id' => null,
                'payment_method' => 'card',
                'payment_tenant_id' => 999,
            ]);

            $this->assertFalse($confirmResult['success'] ?? true, 'Cross-tenant payment should be blocked');
        } catch (FraudBlockedException $e) {
            $this->assertTrue(true, 'Cross-tenant payment should trigger fraud detection');
        }
    }

    public function test_payment_with_stolen_card_blocked(): void
    {
        $stolenCardPatterns = [
            'card_number' => '4111111111111111',
            'issuer' => 'blacklisted_bank',
            'cardholder_name' => 'STOLEN CARD',
        ];

        $dto = new RealTimeBookingDto(
            userId: 1,
            tenantId: 1,
            businessGroupId: null,
            venueId: 1,
            trainerId: null,
            slotStart: now()->addHours(2)->toDateTimeString(),
            slotEnd: now()->addHours(3)->toDateTimeString(),
            bookingType: 'general',
            biometricData: $stolenCardPatterns,
            extendedHold: false,
            correlationId: Str::uuid()->toString(),
        );

        $holdResult = $this->service->holdSlot($dto);
        $this->assertTrue($holdResult['success']);

        try {
            $confirmResult = $this->service->confirmBooking($dto, [
                'amount' => 100,
                'transaction_id' => null,
                'payment_method' => 'card',
            ]);

            $this->assertFalse($confirmResult['success'] ?? true, 'Stolen card should be blocked');
        } catch (FraudBlockedException $e) {
            $this->assertTrue(true, 'Stolen card should trigger fraud detection');
        }
    }

    public function test_chargeback_prevention(): void
    {
        $highRiskUserIds = [999, 998, 997];

        foreach ($highRiskUserIds as $userId) {
            $dto = new RealTimeBookingDto(
                userId: $userId,
                tenantId: 1,
                businessGroupId: null,
                venueId: 1,
                trainerId: null,
                slotStart: now()->addHours(2)->toDateTimeString(),
                slotEnd: now()->addHours(3)->toDateTimeString(),
                bookingType: 'general',
                biometricData: [],
                extendedHold: false,
                correlationId: Str::uuid()->toString(),
            );

            $holdResult = $this->service->holdSlot($dto);
            $this->assertTrue($holdResult['success']);

            try {
                $confirmResult = $this->service->confirmBooking($dto, [
                    'amount' => 100,
                    'transaction_id' => null,
                    'payment_method' => 'card',
                ]);

                $this->assertFalse($confirmResult['success'] ?? true, 'High-risk user payment should be blocked');
            } catch (FraudBlockedException $e) {
                $this->assertTrue(true, 'Chargeback prevention should trigger fraud detection');
            }
        }
    }
}
