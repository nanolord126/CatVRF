<?php declare(strict_types=1);

namespace Tests\Unit\Domains\Hotels\DTOs;

use PHPUnit\Framework\TestCase;

/**
 * Unit tests for BookRoomDto.
 *
 * @covers \App\Domains\Hotels\DTOs\BookRoomDto
 */
final class BookRoomDtoTest extends TestCase
{
    public function test_class_is_final_readonly(): void
    {
        $reflection = new \ReflectionClass(
            \App\Domains\Hotels\DTOs\BookRoomDto::class
        );
        $this->assertTrue($reflection->isFinal(), 'BookRoomDto must be final');
        $this->assertTrue($reflection->isReadOnly(), 'BookRoomDto must be readonly');
    }

    public function test_constructor_properties(): void
    {
        $reflection = new \ReflectionClass(
            \App\Domains\Hotels\DTOs\BookRoomDto::class
        );
        $constructor = $reflection->getConstructor();
        $this->assertNotNull($constructor);
        $params = array_map(fn($p) => $p->getName(), $constructor->getParameters());
        $this->assertContains('hotelId', $params, 'Constructor must have hotelId');
        $this->assertContains('roomId', $params, 'Constructor must have roomId');
        $this->assertContains('customerId', $params, 'Constructor must have customerId');
        $this->assertContains('checkIn', $params, 'Constructor must have checkIn');
        $this->assertContains('checkOut', $params, 'Constructor must have checkOut');
        $this->assertContains('guestsCount', $params, 'Constructor must have guestsCount');
        $this->assertContains('specialRequests', $params, 'Constructor must have specialRequests');
        $this->assertContains('correlationId', $params, 'Constructor must have correlationId');
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
        return \App\Domains\Hotels\DTOs\BookRoomDto::class;
    }
}
