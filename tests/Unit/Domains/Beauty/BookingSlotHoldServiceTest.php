<?php declare(strict_types=1);

namespace Tests\Unit\Domains\Beauty;

use App\Domains\Beauty\DTOs\HoldBookingSlotDto;
use App\Domains\Beauty\Events\SlotHeldEvent;
use App\Domains\Beauty\Events\SlotReleasedEvent;
use App\Domains\Beauty\Models\BookingSlot;
use App\Domains\Beauty\Services\BookingSlotHoldService;
use App\Services\AuditService;
use App\Services\FraudControlService;
use App\Services\IdempotencyService;
use App\Services\CRMService;
use Illuminate\Database\ConnectionInterface;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Log\Logger;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Tests\TestCase;

final class BookingSlotHoldServiceTest extends TestCase
{
    use RefreshDatabase;

    private BookingSlotHoldService $service;

    private FraudControlService $fraudControl;

    private AuditService $auditService;

    private IdempotencyService $idempotencyService;

    private CRMService $crmService;

    private ConnectionInterface $db;

    private Logger $logger;

    protected function setUp(): void
    {
        parent::setUp();

        $this->fraudControl = $this->createMock(FraudControlService::class);
        $this->auditService = $this->createMock(AuditService::class);
        $this->idempotencyService = $this->createMock(IdempotencyService::class);
        $this->crmService = $this->createMock(CRMService::class);
        $this->db = $this->app->make(ConnectionInterface::class);
        $this->logger = $this->app->make(Logger::class);

        $this->service = new BookingSlotHoldService(
            $this->fraudControl,
            $this->auditService,
            $this->idempotencyService,
            $this->crmService,
            $this->db,
            $this->logger,
        );
    }

    public function test_hold_slot_successfully(): void
    {
        Event::fake();

        $this->fraudControl->expects($this->once())
            ->method('check');

        $this->idempotencyService->expects($this->once())
            ->method('checkOrSkip');

        $slot = BookingSlot::factory()->create([
            'status' => 'available',
            'tenant_id' => 1,
        ]);

        $dto = new HoldBookingSlotDto(
            bookingSlotId: $slot->id,
            customerId: 100,
            tenantId: 1,
            businessGroupId: null,
            isB2b: false,
            correlationId: Str::uuid()->toString(),
            idempotencyKey: null,
        );

        $result = $this->service->holdSlot($dto);

        $this->assertInstanceOf(BookingSlot::class, $result);
        $this->assertEquals('held', $result->status);
        $this->assertEquals(100, $result->customer_id);
        $this->assertNotNull($result->held_at);
        $this->assertNotNull($result->expires_at);

        Event::assertDispatched(SlotHeldEvent::class);
    }

    public function test_hold_slot_b2b_has_longer_duration(): void
    {
        Event::fake();

        $this->fraudControl->expects($this->once())
            ->method('check');

        $this->idempotencyService->expects($this->once())
            ->method('checkOrSkip');

        $slot = BookingSlot::factory()->create([
            'status' => 'available',
            'tenant_id' => 1,
        ]);

        $dto = new HoldBookingSlotDto(
            bookingSlotId: $slot->id,
            customerId: 100,
            tenantId: 1,
            businessGroupId: 10,
            isB2b: true,
            correlationId: Str::uuid()->toString(),
            idempotencyKey: null,
        );

        $result = $this->service->holdSlot($dto);

        $this->assertEquals(60, $result->getHoldDurationMinutes());
    }

    public function test_hold_slot_fails_if_not_available(): void
    {
        $this->fraudControl->expects($this->once())
            ->method('check');

        $this->idempotencyService->expects($this->once())
            ->method('checkOrSkip');

        $slot = BookingSlot::factory()->create([
            'status' => 'booked',
            'tenant_id' => 1,
        ]);

        $dto = new HoldBookingSlotDto(
            bookingSlotId: $slot->id,
            customerId: 100,
            tenantId: 1,
            businessGroupId: null,
            isB2b: false,
            correlationId: Str::uuid()->toString(),
            idempotencyKey: null,
        );

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Booking slot not available or does not exist');

        $this->service->holdSlot($dto);
    }

    public function test_release_slot_successfully(): void
    {
        Event::fake();

        $slot = BookingSlot::factory()->create([
            'status' => 'held',
            'customer_id' => 100,
            'tenant_id' => 1,
            'held_at' => now(),
            'expires_at' => now()->addMinutes(15),
        ]);

        $result = $this->service->releaseSlot(
            bookingSlotId: $slot->id,
            tenantId: 1,
            reason: 'payment_failed',
            correlationId: Str::uuid()->toString(),
        );

        $this->assertEquals('available', $result->status);
        $this->assertNull($result->customer_id);
        $this->assertNull($result->held_at);
        $this->assertNull($result->expires_at);

        Event::assertDispatched(SlotReleasedEvent::class);
    }

    public function test_confirm_slot_as_booked_successfully(): void
    {
        Event::fake();

        $this->crmService->expects($this->once())
            ->method('createBooking');

        $slot = BookingSlot::factory()->create([
            'status' => 'held',
            'customer_id' => 100,
            'tenant_id' => 1,
            'held_at' => now(),
            'expires_at' => now()->addMinutes(15),
        ]);

        $result = $this->service->confirmSlotAsBooked(
            bookingSlotId: $slot->id,
            tenantId: 1,
            orderId: 500,
            correlationId: Str::uuid()->toString(),
        );

        $this->assertEquals('booked', $result->status);
        $this->assertEquals(500, $result->order_id);
        $this->assertNotNull($result->booked_at);
    }

    public function test_confirm_slot_fails_if_expired(): void
    {
        $slot = BookingSlot::factory()->create([
            'status' => 'held',
            'customer_id' => 100,
            'tenant_id' => 1,
            'held_at' => now()->subMinutes(20),
            'expires_at' => now()->subMinutes(5),
        ]);

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Booking slot has expired');

        $this->service->confirmSlotAsBooked(
            bookingSlotId: $slot->id,
            tenantId: 1,
            orderId: 500,
            correlationId: Str::uuid()->toString(),
        );
    }

    public function test_expire_held_slots(): void
    {
        Event::fake();

        $expiredSlot = BookingSlot::factory()->create([
            'status' => 'held',
            'tenant_id' => 1,
            'held_at' => now()->subMinutes(20),
            'expires_at' => now()->subMinutes(5),
        ]);

        $validSlot = BookingSlot::factory()->create([
            'status' => 'held',
            'tenant_id' => 1,
            'held_at' => now(),
            'expires_at' => now()->addMinutes(10),
        ]);

        $count = $this->service->expireHeldSlots(1);

        $this->assertEquals(1, $count);
        $expiredSlot->refresh();
        $this->assertEquals('available', $expiredSlot->status);

        $validSlot->refresh();
        $this->assertEquals('held', $validSlot->status);
    }
}
