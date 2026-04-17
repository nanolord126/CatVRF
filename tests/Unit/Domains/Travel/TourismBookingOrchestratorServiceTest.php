<?php declare(strict_types=1);

namespace Tests\Unit\Domains\Travel;

use App\Domains\Travel\DTOs\TourismBookingDto;
use App\Domains\Travel\Models\TourBooking;
use App\Domains\Travel\Models\Tour;
use App\Domains\Travel\Services\TourismBookingOrchestratorService;
use App\Services\FraudControlService;
use App\Services\AuditService;
use App\Services\Wallet\WalletService;
use App\Services\Payment\PaymentService;
use App\Services\ML\FraudMLService;
use App\Services\ML\UserTasteAnalyzerService;
use App\Services\CRM\CRMIntegrationService;
use App\Domains\Travel\Services\AI\TravelConstructorService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Redis;
use Illuminate\Support\Str;
use Tests\TestCase;

/**
 * Tourism Booking Orchestrator Service Test
 * 
 * Unit tests for TourismBookingOrchestratorService covering:
 * - Booking creation with hold
 * - Booking confirmation with biometric verification
 * - Booking cancellation with ML-fraud detection
 * - Video call scheduling
 * - Virtual tour marking
 * - Dynamic pricing calculation
 * - Cashback calculation
 * - Refund calculation
 */
final class TourismBookingOrchestratorServiceTest extends TestCase
{
    use RefreshDatabase;

    private TourismBookingOrchestratorService $orchestrator;
    private FraudControlService $fraud;
    private AuditService $audit;
    private WalletService $wallet;
    private PaymentService $payment;
    private FraudMLService $fraudML;
    private UserTasteAnalyzerService $tasteAnalyzer;
    private CRMIntegrationService $crm;
    private TravelConstructorService $aiConstructor;

    protected function setUp(): void
    {
        parent::setUp();

        $this->fraud = $this->createMock(FraudControlService::class);
        $this->fraud->method('check');

        $this->audit = $this->createMock(AuditService::class);
        $this->audit->method('record');

        $this->wallet = $this->createMock(WalletService::class);
        $this->wallet->method('debit');
        $this->wallet->method('credit');
        $this->wallet->method('getOrCreateWallet')->willReturn(1);

        $this->payment = $this->createMock(PaymentService::class);
        $this->payment->method('initPayment');

        $this->fraudML = new FraudMLService(app('log'));

        $this->tasteAnalyzer = $this->createMock(UserTasteAnalyzerService::class);
        $this->tasteAnalyzer->method('getProfile')->willReturn((object) ['travel_preferences' => []]);
        $this->tasteAnalyzer->method('getLoyaltyDiscount')->willReturn(0);
        $this->tasteAnalyzer->method('getCashbackRate')->willReturn(0.05);

        $this->crm = $this->createMock(CRMIntegrationService::class);
        $this->crm->method('updateOrCreateContact')->willReturn('crm_123');

        $this->aiConstructor = $this->createMock(TravelConstructorService::class);
        $this->aiConstructor->method('analyzeAndRecommend')->willReturn([]);

        $this->orchestrator = new TourismBookingOrchestratorService(
            fraud: $this->fraud,
            audit: $this->audit,
            wallet: $this->wallet,
            payment: $this->payment,
            fraudML: $this->fraudML,
            tasteAnalyzer: $this->tasteAnalyzer,
            crm: $this->crm,
            aiConstructor: $this->aiConstructor,
            logger: app('log'),
            db: DB::connection(),
        );
    }

    public function test_create_booking_with_hold(): void
    {
        $tour = Tour::factory()->create([
            'uuid' => Str::uuid()->toString(),
            'base_price' => 10000.00,
            'is_active' => true,
        ]);

        $dto = new TourismBookingDto(
            tenantId: 1,
            businessGroupId: null,
            userId: 1,
            tourUuid: $tour->uuid,
            personCount: 2,
            startDate: now()->addDays(7)->toDateString(),
            endDate: now()->addDays(10)->toDateString(),
            totalAmount: 20000.00,
            paymentMethod: 'card',
            splitPaymentEnabled: false,
            correlationId: Str::uuid()->toString(),
        );

        $booking = $this->orchestrator->createBooking($dto);

        $this->assertInstanceOf(TourBooking::class, $booking);
        $this->assertEquals('held', $booking->status);
        $this->assertEquals(2, $booking->person_count);
        $this->assertNotNull($booking->biometric_token);
        $this->assertFalse($booking->biometric_verified);
        $this->assertNotNull($booking->hold_expires_at);
        $this->assertTrue($booking->hold_expires_at->isFuture());
        $this->assertEquals(1, $booking->tenant_id);
    }

    public function test_confirm_booking_after_biometric_verification(): void
    {
        $tour = Tour::factory()->create([
            'uuid' => Str::uuid()->toString(),
            'base_price' => 10000.00,
            'is_active' => true,
        ]);

        $booking = TourBooking::factory()->create([
            'uuid' => Str::uuid()->toString(),
            'tour_id' => $tour->id,
            'user_id' => 1,
            'person_count' => 2,
            'total_amount' => 20000.00,
            'status' => 'held',
            'biometric_verified' => true,
            'hold_expires_at' => now()->addMinutes(15),
        ]);

        $confirmedBooking = $this->orchestrator->confirmBooking($booking->uuid, Str::uuid()->toString());

        $this->assertEquals('confirmed', $confirmedBooking->status);
        $this->assertNotNull($confirmedBooking->confirmed_at);
        $this->assertEquals(1000.00, $confirmedBooking->cashback_amount); // 5% of 20000
    }

    public function test_confirm_booking_fails_without_biometric_verification(): void
    {
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Biometric verification required before confirmation.');

        $tour = Tour::factory()->create([
            'uuid' => Str::uuid()->toString(),
            'base_price' => 10000.00,
            'is_active' => true,
        ]);

        $booking = TourBooking::factory()->create([
            'uuid' => Str::uuid()->toString(),
            'tour_id' => $tour->id,
            'user_id' => 1,
            'person_count' => 2,
            'total_amount' => 20000.00,
            'status' => 'held',
            'biometric_verified' => false,
            'hold_expires_at' => now()->addMinutes(15),
        ]);

        $this->orchestrator->confirmBooking($booking->uuid, Str::uuid()->toString());
    }

    public function test_cancel_booking_with_fraud_detection(): void
    {
        $tour = Tour::factory()->create([
            'uuid' => Str::uuid()->toString(),
            'base_price' => 10000.00,
            'is_active' => true,
        ]);

        $booking = TourBooking::factory()->create([
            'uuid' => Str::uuid()->toString(),
            'tour_id' => $tour->id,
            'user_id' => 1,
            'person_count' => 2,
            'total_amount' => 20000.00,
            'status' => 'confirmed',
            'confirmed_at' => now()->subHours(1),
        ]);

        $cancelledBooking = $this->orchestrator->cancelBooking(
            $booking->uuid,
            'Schedule conflict',
            Str::uuid()->toString()
        );

        $this->assertEquals('cancelled', $cancelledBooking->status);
        $this->assertNotNull($cancelledBooking->cancelled_at);
        $this->assertEquals('Schedule conflict', $cancelledBooking->cancellation_reason);
        $this->assertNotNull($cancelledBooking->fraud_score);
        $this->assertGreaterThanOrEqual(0, $cancelledBooking->fraud_score);
        $this->assertLessThanOrEqual(1, $cancelledBooking->fraud_score);
    }

    public function test_schedule_video_call(): void
    {
        $tour = Tour::factory()->create([
            'uuid' => Str::uuid()->toString(),
            'base_price' => 10000.00,
            'is_active' => true,
        ]);

        $booking = TourBooking::factory()->create([
            'uuid' => Str::uuid()->toString(),
            'tour_id' => $tour->id,
            'user_id' => 1,
            'person_count' => 2,
            'total_amount' => 20000.00,
            'status' => 'confirmed',
        ]);

        $scheduledTime = now()->addDays(1)->toIso8601String();

        $updatedBooking = $this->orchestrator->scheduleVideoCall($booking->uuid, $scheduledTime, Str::uuid()->toString());

        $this->assertTrue($updatedBooking->video_call_scheduled);
        $this->assertEquals($scheduledTime, $updatedBooking->video_call_time->toIso8601String());
        $this->assertNotNull($updatedBooking->video_call_link);
    }

    public function test_mark_virtual_tour_viewed(): void
    {
        $tour = Tour::factory()->create([
            'uuid' => Str::uuid()->toString(),
            'base_price' => 10000.00,
            'is_active' => true,
        ]);

        $booking = TourBooking::factory()->create([
            'uuid' => Str::uuid()->toString(),
            'tour_id' => $tour->id,
            'user_id' => 1,
            'person_count' => 2,
            'total_amount' => 20000.00,
            'status' => 'confirmed',
            'virtual_tour_viewed' => false,
        ]);

        $updatedBooking = $this->orchestrator->markVirtualTourViewed($booking->uuid, Str::uuid()->toString());

        $this->assertTrue($updatedBooking->virtual_tour_viewed);
        $this->assertNotNull($updatedBooking->virtual_tour_viewed_at);
    }

    public function test_dynamic_pricing_with_demand_multiplier(): void
    {
        $tour = Tour::factory()->create([
            'uuid' => Str::uuid()->toString(),
            'base_price' => 10000.00,
            'is_active' => true,
        ]);

        $dto = new TourismBookingDto(
            tenantId: 1,
            businessGroupId: null,
            userId: 1,
            tourUuid: $tour->uuid,
            personCount: 1,
            startDate: now()->addDays(1)->toDateString(), // High demand (within 3 days)
            endDate: now()->addDays(3)->toDateString(),
            totalAmount: 10000.00,
            paymentMethod: 'card',
            splitPaymentEnabled: false,
            correlationId: Str::uuid()->toString(),
        );

        $booking = $this->orchestrator->createBooking($dto);

        $this->assertGreaterThan($booking->base_price, $booking->dynamic_price);
    }

    public function test_b2b_booking_gets_lower_commission(): void
    {
        $tour = Tour::factory()->create([
            'uuid' => Str::uuid()->toString(),
            'base_price' => 10000.00,
            'is_active' => true,
        ]);

        $dto = new TourismBookingDto(
            tenantId: 1,
            businessGroupId: 1,
            userId: 1,
            tourUuid: $tour->uuid,
            personCount: 2,
            startDate: now()->addDays(7)->toDateString(),
            endDate: now()->addDays(10)->toDateString(),
            totalAmount: 20000.00,
            paymentMethod: 'card',
            splitPaymentEnabled: false,
            correlationId: Str::uuid()->toString(),
        );

        $booking = $this->orchestrator->createBooking($dto);

        $this->assertEquals(0.10, $booking->commission_rate); // B2B gets 10% commission
        $this->assertEquals(2000.00, $booking->commission_amount); // 10% of 20000
    }

    protected function tearDown(): void
    {
        Redis::flushdb();
        parent::tearDown();
    }
}
