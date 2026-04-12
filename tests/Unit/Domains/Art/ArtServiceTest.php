<?php declare(strict_types=1);

namespace Tests\Unit\Domains\Art;

use PHPUnit\Framework\TestCase;

/**
 * Unit tests for ArtService.
 *
 * @covers \App\Domains\Art\Domain\Services\ArtService
 */
final class ArtServiceTest extends TestCase
{
    public function test_class_is_final(): void
    {
        $reflection = new \ReflectionClass(
            \App\Domains\Art\Domain\Services\ArtService::class
        );
        $this->assertTrue($reflection->isFinal(), 'ArtService must be final');
    }

    public function test_class_is_readonly(): void
    {
        $reflection = new \ReflectionClass(
            \App\Domains\Art\Domain\Services\ArtService::class
        );
        $this->assertTrue($reflection->isReadOnly(), 'ArtService must be readonly');
    }

    public function test_has_constructor_injection(): void
    {
        $reflection = new \ReflectionClass(
            \App\Domains\Art\Domain\Services\ArtService::class
        );
        $constructor = $reflection->getConstructor();
        $this->assertNotNull($constructor, 'ArtService must have __construct');
        $this->assertGreaterThan(0, $constructor->getNumberOfParameters());
    }

    public function test_createProject_method_exists(): void
    {
        $this->assertTrue(
            method_exists(\App\Domains\Art\Domain\Services\ArtService::class, 'createProject'),
            'ArtService must implement createProject()'
        );
    }

    public function test_addArtwork_method_exists(): void
    {
        $this->assertTrue(
            method_exists(\App\Domains\Art\Domain\Services\ArtService::class, 'addArtwork'),
            'ArtService must implement addArtwork()'
        );
    }

    public function test_recordReview_method_exists(): void
    {
        $this->assertTrue(
            method_exists(\App\Domains\Art\Domain\Services\ArtService::class, 'recordReview'),
            'ArtService must implement recordReview()'
        );
    }

}
