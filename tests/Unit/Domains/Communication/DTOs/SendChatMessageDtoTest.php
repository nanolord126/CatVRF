<?php

declare(strict_types=1);

namespace Tests\Unit\Domains\Communication\DTOs;

use PHPUnit\Framework\TestCase;
use App\Domains\Communication\DTOs\SendChatMessageDto;

/**
 * Unit tests for SendChatMessageDto.
 *
 * @covers \App\Domains\Communication\DTOs\SendChatMessageDto
 */
final class SendChatMessageDtoTest extends TestCase
{
    public function test_class_is_final_readonly(): void
    {
        $reflection = new \ReflectionClass(
            SendChatMessageDto::class
        );
        $this->assertTrue($reflection->isFinal(), 'SendChatMessageDto must be final');
        $this->assertTrue($reflection->isReadOnly(), 'SendChatMessageDto must be readonly');
    }

    public function test_constructor_properties(): void
    {
        $reflection = new \ReflectionClass(
            SendChatMessageDto::class
        );
        $constructor = $reflection->getConstructor();
        $this->assertNotNull($constructor);
        $params = array_map(fn ($p) => $p->getName(), $constructor->getParameters());
        $this->assertContains('tenantId', $params, 'Constructor must have tenantId');
        $this->assertContains('roomId', $params, 'Constructor must have roomId');
        $this->assertContains('senderId', $params, 'Constructor must have senderId');
        $this->assertContains('body', $params, 'Constructor must have body');
        $this->assertContains('type', $params, 'Constructor must have type');
        $this->assertContains('correlationId', $params, 'Constructor must have correlationId');
        $this->assertContains('metadata', $params, 'Constructor must have metadata');
    }

    public function test_has_to_array_method(): void
    {
        $this->assertTrue(
            method_exists($this->getDtoClass(), 'toArray'),
            'DTO must implement toArray()'
        );
    }

    private function getDtoClass(): string
    {
        return SendChatMessageDto::class;
    }
}
