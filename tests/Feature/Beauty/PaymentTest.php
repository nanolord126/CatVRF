<?php

namespace Tests\Feature\Beauty;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\Beauty\Enums\BookingStatus;
use Modules\Beauty\Enums\PaymentStatus;
use Modules\Beauty\Models\Booking;
use Modules\Beauty\Models\BeautySalon;
use Modules\Beauty\Models\Payment;
use Modules\Beauty\Models\Service;
use Modules\Beauty\Services\BookingService;
use Modules\Beauty\Services\PaymentService;
use Modules\Payments\Gateways\TinkoffGateway;
use Tests\TestCase;

class PaymentTest extends TestCase
{
    use RefreshDatabase;

    private BeautySalon $salon;
    private Service $service;
    private BookingService $bookingService;
    private PaymentService $paymentService;
    private TinkoffGateway $tinkoffGateway;

    protected function setUp(): void
    {
        parent::setUp();

        $this->salon = BeautySalon::factory()->create([
            'tenant_id' => 'test-tenant',
        ]);

        $this->service = Service::factory()->create([
            'salon_id' => $this->salon->id,
            'tenant_id' => 'test-tenant',
            'price' => 1500.00,
            'is_active' => true,
        ]);

        $this->bookingService = app(BookingService::class);
        $this->paymentService = app(PaymentService::class);
        
        // Mock Tinkoff gateway
        $this->tinkoffGateway = $this->mock(TinkoffGateway::class);
    }

    public function test_payment_can_be_initiated_for_booking(): void
    {
        $customerId = 1;
        $scheduledAt = now()->addDays(2);

        $booking = $this->bookingService->createBooking(
            $this->service,
            $customerId,
            $scheduledAt->toDateTimeString()
        );

        // Mock Tinkoff response
        $this->tinkoffGateway
            ->shouldReceive('createPayment')
            ->once()
            ->andReturn('https://test.tinkoff.ru/payment/url');

        $paymentData = $this->paymentService->initiatePayment($booking);

        $this->assertArrayHasKey('payment_id', $paymentData);
        $this->assertArrayHasKey('payment_url', $paymentData);
        $this->assertArrayHasKey('correlation_id', $paymentData);
        $this->assertEquals($this->service->price, $paymentData['amount']);

        $this->assertDatabaseHas('beauty_payments', [
            'booking_id' => $booking->id,
            'status' => PaymentStatus::PENDING->value,
            'amount' => $this->service->price,
        ]);
    }

    public function test_payment_cannot_be_initiated_for_completed_booking(): void
    {
        $customerId = 1;
        $scheduledAt = now()->addDays(2);

        $booking = $this->bookingService->createBooking(
            $this->service,
            $customerId,
            $scheduledAt->toDateTimeString()
        );

        $booking = $this->bookingService->confirmBooking($booking);
        $booking = $this->bookingService->completeBooking($booking);

        $this->expectException(\Exception::class);

        $this->paymentService->initiatePayment($booking);
    }

    public function test_payment_is_confirmed_and_wallet_is_credited(): void
    {
        $customerId = 1;
        $scheduledAt = now()->addDays(2);

        $booking = $this->bookingService->createBooking(
            $this->service,
            $customerId,
            $scheduledAt->toDateTimeString()
        );

        // Mock wallet
        $this->salon->wallet()->createAccount(['name' => 'Main Account']);

        $payment = Payment::factory()->create([
            'booking_id' => $booking->id,
            'salon_id' => $this->salon->id,
            'tenant_id' => 'test-tenant',
            'amount' => $this->service->price,
            'status' => PaymentStatus::PENDING,
        ]);

        // Confirm payment
        $confirmedPayment = $this->paymentService->confirmPayment($payment, 'TINKOFF123456');

        $this->assertEquals(PaymentStatus::CONFIRMED, $confirmedPayment->status);
        $this->assertNotNull($confirmedPayment->completed_at);
        $this->assertEquals('TINKOFF123456', $confirmedPayment->tinkoff_payment_id);

        // Check salon payout calculation (80%)
        $expectedSalonPayout = $this->service->price * 0.8;
        $this->assertEquals($expectedSalonPayout, $confirmedPayment->salon_payout_amount);

        // Check platform commission (20%)
        $expectedCommission = $this->service->price * 0.2;
        $this->assertEquals($expectedCommission, $confirmedPayment->platform_commission_amount);
    }

    public function test_payment_can_be_failed(): void
    {
        $payment = Payment::factory()->create([
            'status' => PaymentStatus::PENDING,
        ]);

        $failedPayment = $this->paymentService->failPayment($payment, 'Card declined');

        $this->assertEquals(PaymentStatus::FAILED, $failedPayment->status);

        $this->assertDatabaseHas('beauty_payments', [
            'id' => $payment->id,
            'status' => PaymentStatus::FAILED->value,
        ]);
    }

    public function test_confirmed_payment_can_be_refunded(): void
    {
        $this->salon->wallet()->createAccount(['name' => 'Main Account']);
        $this->salon->wallet()->deposit(5000);

        $payment = Payment::factory()->create([
            'salon_id' => $this->salon->id,
            'tenant_id' => 'test-tenant',
            'amount' => 1500.00,
            'status' => PaymentStatus::CONFIRMED,
            'salon_payout_amount' => 1200.00,
            'tinkoff_payment_id' => 'TINKOFF123456',
        ]);

        // Mock Tinkoff refund
        $this->tinkoffGateway
            ->shouldReceive('refund')
            ->once()
            ->andReturn(true);

        $refundedPayment = $this->paymentService->refundPayment($payment, 'Customer request');

        $this->assertEquals(PaymentStatus::REFUNDED, $refundedPayment->status);

        $this->assertDatabaseHas('beauty_payments', [
            'id' => $payment->id,
            'status' => PaymentStatus::REFUNDED->value,
        ]);
    }
}
