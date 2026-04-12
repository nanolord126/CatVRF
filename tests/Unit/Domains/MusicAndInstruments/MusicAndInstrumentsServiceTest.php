<?php declare(strict_types=1);

namespace Tests\Unit\Domains\MusicAndInstruments;

use PHPUnit\Framework\TestCase;

/**
 * Unit tests for MusicAndInstrumentsService.
 *
 * @covers \App\Domains\MusicAndInstruments\Domain\Services\MusicAndInstrumentsService
 */
final class MusicAndInstrumentsServiceTest extends TestCase
{
    public function test_class_is_final(): void
    {
        $reflection = new \ReflectionClass(
            \App\Domains\MusicAndInstruments\Domain\Services\MusicAndInstrumentsService::class
        );
        $this->assertTrue($reflection->isFinal(), 'MusicAndInstrumentsService must be final');
    }

    public function test_class_is_readonly(): void
    {
        $reflection = new \ReflectionClass(
            \App\Domains\MusicAndInstruments\Domain\Services\MusicAndInstrumentsService::class
        );
        $this->assertTrue($reflection->isReadOnly(), 'MusicAndInstrumentsService must be readonly');
    }

    public function test_has_constructor_injection(): void
    {
        $reflection = new \ReflectionClass(
            \App\Domains\MusicAndInstruments\Domain\Services\MusicAndInstrumentsService::class
        );
        $constructor = $reflection->getConstructor();
        $this->assertNotNull($constructor, 'MusicAndInstrumentsService must have __construct');
        $this->assertGreaterThan(0, $constructor->getNumberOfParameters());
    }

    public function test_create_method_exists(): void
    {
        $this->assertTrue(
            method_exists(\App\Domains\MusicAndInstruments\Domain\Services\MusicAndInstrumentsService::class, 'create'),
            'MusicAndInstrumentsService must implement create()'
        );
    }

    public function test_update_method_exists(): void
    {
        $this->assertTrue(
            method_exists(\App\Domains\MusicAndInstruments\Domain\Services\MusicAndInstrumentsService::class, 'update'),
            'MusicAndInstrumentsService must implement update()'
        );
    }

    public function test_delete_method_exists(): void
    {
        $this->assertTrue(
            method_exists(\App\Domains\MusicAndInstruments\Domain\Services\MusicAndInstrumentsService::class, 'delete'),
            'MusicAndInstrumentsService must implement delete()'
        );
    }

    public function test_list_method_exists(): void
    {
        $this->assertTrue(
            method_exists(\App\Domains\MusicAndInstruments\Domain\Services\MusicAndInstrumentsService::class, 'list'),
            'MusicAndInstrumentsService must implement list()'
        );
    }

    public function test_getById_method_exists(): void
    {
        $this->assertTrue(
            method_exists(\App\Domains\MusicAndInstruments\Domain\Services\MusicAndInstrumentsService::class, 'getById'),
            'MusicAndInstrumentsService must implement getById()'
        );
    }

}
