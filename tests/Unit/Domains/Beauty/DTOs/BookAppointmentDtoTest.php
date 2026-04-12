<?php declare(strict_types=1);

namespace Tests\Unit\Domains\Beauty\DTOs;

use PHPUnit\Framework\TestCase;

/**
 * Unit tests for BookAppointmentDto.
 *
 * @covers \App\Domains\Beauty\DTOs\BookAppointmentDto
 */
final class BookAppointmentDtoTest extends TestCase
{
    public function test_class_is_final_readonly(): void
    {
        $reflection = new \ReflectionClass(
            \App\Domains\Beauty\DTOs\BookAppointmentDto::class
        );
        $this->assertTrue($reflection->isFinal(), 'BookAppointmentDto must be final');
        $this->assertTrue($reflection->isReadOnly(), 'BookAppointmentDto must be readonly');
    }

    public function test_constructor_properties(): void
    {
        $reflection = new \ReflectionClass(
            \App\Domains\Beauty\DTOs\BookAppointmentDto::class
        );
        $constructor = $reflection->getConstructor();
        $this->assertNotNull($constructor);
        $params = array_map(fn($p) => $p->getName(), $constructor->getParameters());
        $this->assertContains('tenantId', $params, 'Constructor must have tenantId');
        $this->assertContains('businessGroupId', $params, 'Constructor must have businessGroupId');
        $this->assertContains('salonId', $params, 'Constructor must have salonId');
        $this->assertContains('masterId', $params, 'Constructor must have masterId');
        $this->assertContains('serviceId', $params, 'Constructor must have serviceId');
        $this->assertContains('userId', $params, 'Constructor must have userId');
        $this->assertContains('correlationId', $params, 'Constructor must have correlationId');
        $this->assertContains('startsAt', $params, 'Constructor must have startsAt');
        $this->assertContains('isB2b', $params, 'Constructor must have isB2b');
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
        return \App\Domains\Beauty\DTOs\BookAppointmentDto::class;
    }
}
