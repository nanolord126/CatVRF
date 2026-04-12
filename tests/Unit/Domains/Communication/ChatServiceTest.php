<?php declare(strict_types=1);

namespace Tests\Unit\Domains\Communication;

use PHPUnit\Framework\TestCase;

/**
 * Unit tests for ChatService.
 *
 * @covers \App\Domains\Communication\Domain\Services\ChatService
 */
final class ChatServiceTest extends TestCase
{
    public function test_class_is_final(): void
    {
        $reflection = new \ReflectionClass(
            \App\Domains\Communication\Domain\Services\ChatService::class
        );
        $this->assertTrue($reflection->isFinal(), 'ChatService must be final');
    }

    public function test_class_is_readonly(): void
    {
        $reflection = new \ReflectionClass(
            \App\Domains\Communication\Domain\Services\ChatService::class
        );
        $this->assertTrue($reflection->isReadOnly(), 'ChatService must be readonly');
    }

    public function test_has_constructor_injection(): void
    {
        $reflection = new \ReflectionClass(
            \App\Domains\Communication\Domain\Services\ChatService::class
        );
        $constructor = $reflection->getConstructor();
        $this->assertNotNull($constructor, 'ChatService must have __construct');
        $this->assertGreaterThan(0, $constructor->getNumberOfParameters());
    }

    public function test_createRoom_method_exists(): void
    {
        $this->assertTrue(
            method_exists(\App\Domains\Communication\Domain\Services\ChatService::class, 'createRoom'),
            'ChatService must implement createRoom()'
        );
    }

    public function test_sendMessage_method_exists(): void
    {
        $this->assertTrue(
            method_exists(\App\Domains\Communication\Domain\Services\ChatService::class, 'sendMessage'),
            'ChatService must implement sendMessage()'
        );
    }

    public function test_markRoomAsRead_method_exists(): void
    {
        $this->assertTrue(
            method_exists(\App\Domains\Communication\Domain\Services\ChatService::class, 'markRoomAsRead'),
            'ChatService must implement markRoomAsRead()'
        );
    }

    public function test_closeRoom_method_exists(): void
    {
        $this->assertTrue(
            method_exists(\App\Domains\Communication\Domain\Services\ChatService::class, 'closeRoom'),
            'ChatService must implement closeRoom()'
        );
    }

    public function test_getRoomHistory_method_exists(): void
    {
        $this->assertTrue(
            method_exists(\App\Domains\Communication\Domain\Services\ChatService::class, 'getRoomHistory'),
            'ChatService must implement getRoomHistory()'
        );
    }

}
