<?php declare(strict_types=1);

namespace Tests\Feature\Domains\Tickets;

use App\Domains\Tickets\Models\Event;
use App\Domains\Tickets\Models\Ticket;
use App\Domains\Tickets\Services\TicketService;
use App\Models\Wallet;
use Illuminate\Foundation\Testing\RefreshDatabase;
use App\Services\FraudControlService;
use Illuminate\Support\Str;
use Tests\BaseTestCase;

/**
 * TicketServiceTest — Feature-тесты вертикали Билеты/Мероприятия.
 */
final class TicketServiceTest extends BaseTestCase
{
    use RefreshDatabase;

    private TicketService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = app(TicketService::class);
        $this->app->instance(
            FraudControlService::class,
            \Mockery::mock(FraudControlService::class)->shouldReceive('check')->andReturn(true)->getMock()
        );
    }

    public function test_ticket_purchased_with_qr_code(): void
    {
        $event = Event::factory()->create([
            'tenant_id'    => $this->tenant->id,
            'price'        => 2_500_00,
            'max_capacity' => 100,
            'status'       => 'active',
        ]);

        Wallet::factory()->create([
            'tenant_id'       => $this->tenant->id,
            'current_balance' => 50_000_00,
        ]);

        $ticket = $this->service->purchaseTicket([
            'event_id'       => $event->id,
            'user_id'        => $this->user->id,
            'tenant_id'      => $this->tenant->id,
            'quantity'       => 1,
            'correlation_id' => Str::uuid()->toString(),
        ]);

        $this->assertInstanceOf(Ticket::class, $ticket);
        $this->assertNotNull($ticket->qr_code);
        $this->assertNotNull($ticket->uuid);
        $this->assertSame('active', $ticket->status);
    }

    public function test_commission_between_8_and_17_percent(): void
    {
        $event = Event::factory()->create([
            'tenant_id' => $this->tenant->id,
            'price'     => 10_000_00, // 10 000 руб
        ]);

        Wallet::factory()->create([
            'tenant_id'       => $this->tenant->id,
            'current_balance' => 50_000_00,
        ]);

        $ticket = $this->service->purchaseTicket([
            'event_id'       => $event->id,
            'user_id'        => $this->user->id,
            'tenant_id'      => $this->tenant->id,
            'quantity'       => 1,
            'correlation_id' => Str::uuid()->toString(),
        ]);

        $minCommission = (int)(10_000_00 * 0.08);
        $maxCommission = (int)(10_000_00 * 0.17);

        $this->assertGreaterThanOrEqual($minCommission, $ticket->commission_amount);
        $this->assertLessThanOrEqual($maxCommission, $ticket->commission_amount);
    }

    public function test_refund_with_2_percent_fee_before_event(): void
    {
        $event = Event::factory()->create([
            'tenant_id'  => $this->tenant->id,
            'price'      => 5_000_00,
            'event_date' => now()->addDays(10),
        ]);

        $wallet = Wallet::factory()->create([
            'tenant_id'       => $this->tenant->id,
            'current_balance' => 50_000_00,
        ]);

        $ticket = $this->service->purchaseTicket([
            'event_id'       => $event->id,
            'user_id'        => $this->user->id,
            'tenant_id'      => $this->tenant->id,
            'quantity'       => 1,
            'correlation_id' => Str::uuid()->toString(),
        ]);

        $before = $wallet->fresh()->current_balance;

        $this->service->refundTicket($ticket->id, Str::uuid()->toString());

        $after = $wallet->fresh()->current_balance;
        $refundAmount = $after - $before;

        // Must be refunded minus 2% fee
        $expectedMax = 5_000_00;
        $expectedMin = (int)(5_000_00 * 0.98);

        $this->assertGreaterThanOrEqual($expectedMin, $refundAmount);
        $this->assertLessThanOrEqual($expectedMax, $refundAmount);
    }

    public function test_sold_out_event_rejects_purchase(): void
    {
        $event = Event::factory()->create([
            'tenant_id'    => $this->tenant->id,
            'price'        => 2_000_00,
            'max_capacity' => 1,
        ]);

        Wallet::factory()->create([
            'tenant_id'       => $this->tenant->id,
            'current_balance' => 50_000_00,
        ]);

        // Buy the last ticket
        $this->service->purchaseTicket([
            'event_id'       => $event->id,
            'user_id'        => $this->user->id,
            'tenant_id'      => $this->tenant->id,
            'quantity'       => 1,
            'correlation_id' => Str::uuid()->toString(),
        ]);

        // Now it's sold out
        $this->expectException(\RuntimeException::class);
        $this->service->purchaseTicket([
            'event_id'       => $event->id,
            'user_id'        => $this->user->id,
            'tenant_id'      => $this->tenant->id,
            'quantity'       => 1,
            'correlation_id' => Str::uuid()->toString(),
        ]);
    }

    public function test_checkin_marks_ticket_as_used(): void
    {
        $ticket = Ticket::factory()->create([
            'tenant_id' => $this->tenant->id,
            'user_id'   => $this->user->id,
            'status'    => 'active',
        ]);

        $this->service->checkIn($ticket->qr_code ?? $ticket->uuid);

        $ticket->refresh();
        $this->assertSame('used', $ticket->status);
    }
}
