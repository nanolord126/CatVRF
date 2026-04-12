<?php declare(strict_types=1);

namespace Tests\Unit\Domains\Tickets\DTOs;

use PHPUnit\Framework\TestCase;

/**
 * Unit tests for BuyTicketDto.
 *
 * @covers \App\Domains\Tickets\DTOs\BuyTicketDto
 */
final class BuyTicketDtoTest extends TestCase
{
    public function test_class_is_final_readonly(): void
    {
        $reflection = new \ReflectionClass(
            \App\Domains\Tickets\DTOs\BuyTicketDto::class
        );
        $this->assertTrue($reflection->isFinal(), 'BuyTicketDto must be final');
        $this->assertTrue($reflection->isReadOnly(), 'BuyTicketDto must be readonly');
    }

    public function test_constructor_properties(): void
    {
        $reflection = new \ReflectionClass(
            \App\Domains\Tickets\DTOs\BuyTicketDto::class
        );
        $constructor = $reflection->getConstructor();
        $this->assertNotNull($constructor);
        $params = array_map(fn($p) => $p->getName(), $constructor->getParameters());
        $this->assertContains('eventId', $params, 'Constructor must have eventId');
        $this->assertContains('ticketTypeId', $params, 'Constructor must have ticketTypeId');
        $this->assertContains('userId', $params, 'Constructor must have userId');
        $this->assertContains('quantity', $params, 'Constructor must have quantity');
        $this->assertContains('sector', $params, 'Constructor must have sector');
        $this->assertContains('row', $params, 'Constructor must have row');
        $this->assertContains('number', $params, 'Constructor must have number');
        $this->assertContains('correlation_id', $params, 'Constructor must have correlation_id');
        $this->assertContains('metadata', $params, 'Constructor must have metadata');
    }

    public function test_has_toArray_method(): void
    {
        $this->assertTrue(
            method_exists($this->getDtoClass(), 'toArray'),
            'DTO must implement toArray()'
        );
    }

    private function getDtoClass(): string
    {
        return \App\Domains\Tickets\DTOs\BuyTicketDto::class;
    }
}
