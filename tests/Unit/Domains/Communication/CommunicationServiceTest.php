<?php declare(strict_types=1);

namespace Tests\Unit\Domains\Communication;

use PHPUnit\Framework\TestCase;

/**
 * Unit tests for CommunicationService.
 *
 * @covers \App\Domains\Communication\Domain\Services\CommunicationService
 */
final class CommunicationServiceTest extends TestCase
{
    public function test_class_is_final(): void
    {
        $reflection = new \ReflectionClass(
            \App\Domains\Communication\Domain\Services\CommunicationService::class
        );
        $this->assertTrue($reflection->isFinal(), 'CommunicationService must be final');
    }

    public function test_class_is_readonly(): void
    {
        $reflection = new \ReflectionClass(
            \App\Domains\Communication\Domain\Services\CommunicationService::class
        );
        $this->assertTrue($reflection->isReadOnly(), 'CommunicationService must be readonly');
    }

    public function test_has_constructor_injection(): void
    {
        $reflection = new \ReflectionClass(
            \App\Domains\Communication\Domain\Services\CommunicationService::class
        );
        $constructor = $reflection->getConstructor();
        $this->assertNotNull($constructor, 'CommunicationService must have __construct');
        $this->assertGreaterThan(0, $constructor->getNumberOfParameters());
    }

    public function test_send_method_exists(): void
    {
        $this->assertTrue(
            method_exists(\App\Domains\Communication\Domain\Services\CommunicationService::class, 'send'),
            'CommunicationService must implement send()'
        );
    }

    public function test_createChannel_method_exists(): void
    {
        $this->assertTrue(
            method_exists(\App\Domains\Communication\Domain\Services\CommunicationService::class, 'createChannel'),
            'CommunicationService must implement createChannel()'
        );
    }

    public function test_disableChannel_method_exists(): void
    {
        $this->assertTrue(
            method_exists(\App\Domains\Communication\Domain\Services\CommunicationService::class, 'disableChannel'),
            'CommunicationService must implement disableChannel()'
        );
    }

    public function test_markDelivered_method_exists(): void
    {
        $this->assertTrue(
            method_exists(\App\Domains\Communication\Domain\Services\CommunicationService::class, 'markDelivered'),
            'CommunicationService must implement markDelivered()'
        );
    }

    public function test_markRead_method_exists(): void
    {
        $this->assertTrue(
            method_exists(\App\Domains\Communication\Domain\Services\CommunicationService::class, 'markRead'),
            'CommunicationService must implement markRead()'
        );
    }

}
