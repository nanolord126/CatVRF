<?php

declare(strict_types=1);

namespace Tests\Feature\Tickets;

use App\Domains\Tickets\Models\Event;
use App\Domains\Tickets\Models\Ticket;
use Database\Factories\Tickets\EventFactory;
use Database\Factories\Tickets\TicketFactory;
use Tests\TestCase;

final class TicketTest extends TestCase
{
    use \Illuminate\Foundation\Testing\DatabaseTransactions;

    /**
     * Тест: Можно создать событие
     */
    public function test_can_create_event(): void
    {
        $event = EventFactory::new()->create([
            'tenant_id' => 1,
            'name' => 'Test Concert',
            'status' => 'published',
        ]);

        $this->assertDatabaseHas('events', [
            'id' => $event->id,
            'name' => 'Test Concert',
            'status' => 'published',
        ]);

        $this->assertTrue($event->hasAvailableTickets());
    }

    /**
     * Тест: Можно создать билет
     */
    public function test_can_create_ticket(): void
    {
        $event = EventFactory::new()->create(['tenant_id' => 1]);
        $ticket = TicketFactory::new()->create([
            'tenant_id' => 1,
            'event_id' => $event->id,
            'user_id' => 1,
        ]);

        $this->assertDatabaseHas('tickets', [
            'id' => $ticket->id,
            'event_id' => $event->id,
            'status' => 'active',
        ]);
    }

    /**
     * Тест: Событие показывает доступные билеты
     */
    public function test_event_has_available_tickets(): void
    {
        $event = EventFactory::new()->create([
            'tenant_id' => 1,
            'total_capacity' => 100,
            'sold_count' => 50,
        ]);

        $this->assertTrue($event->hasAvailableTickets());
        $this->assertEquals(50, $event->getRemainingTickets());
    }

    /**
     * Тест: Событие без доступных билетов
     */
    public function test_event_no_available_tickets(): void
    {
        $event = EventFactory::new()->create([
            'tenant_id' => 1,
            'total_capacity' => 100,
            'sold_count' => 100,
        ]);

        $this->assertFalse($event->hasAvailableTickets());
        $this->assertEquals(0, $event->getRemainingTickets());
    }

    /**
     * Тест: Билет активен с правильным статусом
     */
    public function test_ticket_is_active_when_paid(): void
    {
        $ticket = TicketFactory::new()->create([
            'tenant_id' => 1,
            'status' => 'active',
            'payment_status' => 'paid',
        ]);

        $this->assertTrue($ticket->isActive());
        $this->assertFalse($ticket->isCheckedIn());
        $this->assertFalse($ticket->isCancelled());
    }

    /**
     * Тест: Билет используется при check-in
     */
    public function test_ticket_checked_in(): void
    {
        $ticket = TicketFactory::new()->checkedIn()->create([
            'tenant_id' => 1,
            'checked_in_by' => 5,
        ]);

        $this->assertTrue($ticket->isCheckedIn());
        $this->assertNotNull($ticket->checked_in_at);
        $this->assertEquals(5, $ticket->checked_in_by);
    }

    /**
     * Тест: Билет может быть отменён
     */
    public function test_ticket_can_be_cancelled(): void
    {
        $event = EventFactory::new()->create([
            'tenant_id' => 1,
            'start_datetime' => now()->addHours(5),
        ]);

        $ticket = TicketFactory::new()->create([
            'tenant_id' => 1,
            'event_id' => $event->id,
            'status' => 'active',
            'payment_status' => 'paid',
        ]);

        $this->assertTrue($ticket->canBeCancelled());
    }

    /**
     * Тест: Билет не может быть отменён за 2 часа до события
     */
    public function test_ticket_cannot_be_cancelled_near_event(): void
    {
        $event = EventFactory::new()->create([
            'tenant_id' => 1,
            'start_datetime' => now()->addHour(),
        ]);

        $ticket = TicketFactory::new()->create([
            'tenant_id' => 1,
            'event_id' => $event->id,
            'status' => 'active',
        ]);

        $this->assertFalse($ticket->canBeCancelled());
    }
}
