<?php declare(strict_types=1);

namespace Tests\Unit\Domains\Travel\DTOs;

use PHPUnit\Framework\TestCase;

/**
 * Unit tests for BookingDto.
 *
 * @covers \App\Domains\Travel\DTOs\BookingDto
 */
final class BookingDtoTest extends TestCase
{
    public function test_class_is_final_readonly(): void
    {
        $reflection = new \ReflectionClass(
            \App\Domains\Travel\DTOs\BookingDto::class
        );
        $this->assertTrue($reflection->isFinal(), 'BookingDto must be final');
        $this->assertTrue($reflection->isReadOnly(), 'BookingDto must be readonly');
    }

    public function test_constructor_properties(): void
    {
        $reflection = new \ReflectionClass(
            \App\Domains\Travel\DTOs\BookingDto::class
        );
        $constructor = $reflection->getConstructor();
        $this->assertNotNull($constructor);
        $params = array_map(fn($p) => $p->getName(), $constructor->getParameters());
        $this->assertContains('userId', $params, 'Constructor must have userId');
        $this->assertContains('bookableType', $params, 'Constructor must have bookableType');
        $this->assertContains('bookableId', $params, 'Constructor must have bookableId');
        $this->assertContains('slotsCount', $params, 'Constructor must have slotsCount');
        $this->assertContains('correlationId', $params, 'Constructor must have correlationId');
        $this->assertContains('idempotencyKey', $params, 'Constructor must have idempotencyKey');
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
        return \App\Domains\Travel\DTOs\BookingDto::class;
    }
}
