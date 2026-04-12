<?php declare(strict_types=1);

namespace Tests\Unit\Domains\CRM\DTOs;

use PHPUnit\Framework\TestCase;

/**
 * Unit tests for CreateCrmAutomationDto.
 *
 * @covers \App\Domains\CRM\DTOs\CreateCrmAutomationDto
 */
final class CreateCrmAutomationDtoTest extends TestCase
{
    public function test_class_is_final_readonly(): void
    {
        $reflection = new \ReflectionClass(
            \App\Domains\CRM\DTOs\CreateCrmAutomationDto::class
        );
        $this->assertTrue($reflection->isFinal(), 'CreateCrmAutomationDto must be final');
        $this->assertTrue($reflection->isReadOnly(), 'CreateCrmAutomationDto must be readonly');
    }

    public function test_constructor_properties(): void
    {
        $reflection = new \ReflectionClass(
            \App\Domains\CRM\DTOs\CreateCrmAutomationDto::class
        );
        $constructor = $reflection->getConstructor();
        $this->assertNotNull($constructor);
        $params = array_map(fn($p) => $p->getName(), $constructor->getParameters());
        $this->assertContains('tenantId', $params, 'Constructor must have tenantId');
        $this->assertContains('name', $params, 'Constructor must have name');
        $this->assertContains('description', $params, 'Constructor must have description');
        $this->assertContains('vertical', $params, 'Constructor must have vertical');
        $this->assertContains('isActive', $params, 'Constructor must have isActive');
        $this->assertContains('triggerType', $params, 'Constructor must have triggerType');
        $this->assertContains('triggerConfig', $params, 'Constructor must have triggerConfig');
        $this->assertContains('actionType', $params, 'Constructor must have actionType');
        $this->assertContains('actionConfig', $params, 'Constructor must have actionConfig');
        $this->assertContains('delayType', $params, 'Constructor must have delayType');
        $this->assertContains('delayMinutes', $params, 'Constructor must have delayMinutes');
        $this->assertContains('correlationId', $params, 'Constructor must have correlationId');
        $this->assertContains('tags', $params, 'Constructor must have tags');
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
        return \App\Domains\CRM\DTOs\CreateCrmAutomationDto::class;
    }
}
