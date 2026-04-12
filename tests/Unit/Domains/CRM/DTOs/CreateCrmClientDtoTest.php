<?php declare(strict_types=1);

namespace Tests\Unit\Domains\CRM\DTOs;

use PHPUnit\Framework\TestCase;

/**
 * Unit tests for CreateCrmClientDto.
 *
 * @covers \App\Domains\CRM\DTOs\CreateCrmClientDto
 */
final class CreateCrmClientDtoTest extends TestCase
{
    public function test_class_is_final_readonly(): void
    {
        $reflection = new \ReflectionClass(
            \App\Domains\CRM\DTOs\CreateCrmClientDto::class
        );
        $this->assertTrue($reflection->isFinal(), 'CreateCrmClientDto must be final');
        $this->assertTrue($reflection->isReadOnly(), 'CreateCrmClientDto must be readonly');
    }

    public function test_constructor_properties(): void
    {
        $reflection = new \ReflectionClass(
            \App\Domains\CRM\DTOs\CreateCrmClientDto::class
        );
        $constructor = $reflection->getConstructor();
        $this->assertNotNull($constructor);
        $params = array_map(fn($p) => $p->getName(), $constructor->getParameters());
        $this->assertContains('tenantId', $params, 'Constructor must have tenantId');
        $this->assertContains('businessGroupId', $params, 'Constructor must have businessGroupId');
        $this->assertContains('userId', $params, 'Constructor must have userId');
        $this->assertContains('firstName', $params, 'Constructor must have firstName');
        $this->assertContains('lastName', $params, 'Constructor must have lastName');
        $this->assertContains('companyName', $params, 'Constructor must have companyName');
        $this->assertContains('email', $params, 'Constructor must have email');
        $this->assertContains('phone', $params, 'Constructor must have phone');
        $this->assertContains('phoneSecondary', $params, 'Constructor must have phoneSecondary');
        $this->assertContains('clientType', $params, 'Constructor must have clientType');
        $this->assertContains('status', $params, 'Constructor must have status');
        $this->assertContains('source', $params, 'Constructor must have source');
        $this->assertContains('vertical', $params, 'Constructor must have vertical');
        $this->assertContains('addresses', $params, 'Constructor must have addresses');
        $this->assertContains('segment', $params, 'Constructor must have segment');
        $this->assertContains('preferences', $params, 'Constructor must have preferences');
        $this->assertContains('specialNotes', $params, 'Constructor must have specialNotes');
        $this->assertContains('internalNotes', $params, 'Constructor must have internalNotes');
        $this->assertContains('verticalData', $params, 'Constructor must have verticalData');
        $this->assertContains('avatarUrl', $params, 'Constructor must have avatarUrl');
        $this->assertContains('preferredLanguage', $params, 'Constructor must have preferredLanguage');
        $this->assertContains('correlationId', $params, 'Constructor must have correlationId');
        $this->assertContains('idempotencyKey', $params, 'Constructor must have idempotencyKey');
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
        return \App\Domains\CRM\DTOs\CreateCrmClientDto::class;
    }
}
