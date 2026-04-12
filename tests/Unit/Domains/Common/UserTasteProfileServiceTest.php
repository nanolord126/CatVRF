<?php declare(strict_types=1);

namespace Tests\Unit\Domains\Common;

use PHPUnit\Framework\TestCase;

/**
 * Unit tests for UserTasteProfileService.
 *
 * @covers \App\Domains\Common\Domain\Services\UserTasteProfileService
 */
final class UserTasteProfileServiceTest extends TestCase
{
    public function test_class_is_final(): void
    {
        $reflection = new \ReflectionClass(
            \App\Domains\Common\Domain\Services\UserTasteProfileService::class
        );
        $this->assertTrue($reflection->isFinal(), 'UserTasteProfileService must be final');
    }

    public function test_class_is_readonly(): void
    {
        $reflection = new \ReflectionClass(
            \App\Domains\Common\Domain\Services\UserTasteProfileService::class
        );
        $this->assertTrue($reflection->isReadOnly(), 'UserTasteProfileService must be readonly');
    }

    public function test_has_constructor_injection(): void
    {
        $reflection = new \ReflectionClass(
            \App\Domains\Common\Domain\Services\UserTasteProfileService::class
        );
        $constructor = $reflection->getConstructor();
        $this->assertNotNull($constructor, 'UserTasteProfileService must have __construct');
        $this->assertGreaterThan(0, $constructor->getNumberOfParameters());
    }

    public function test_getOrCreateProfile_method_exists(): void
    {
        $this->assertTrue(
            method_exists(\App\Domains\Common\Domain\Services\UserTasteProfileService::class, 'getOrCreateProfile'),
            'UserTasteProfileService must implement getOrCreateProfile()'
        );
    }

    public function test_updateProfileFromInteraction_method_exists(): void
    {
        $this->assertTrue(
            method_exists(\App\Domains\Common\Domain\Services\UserTasteProfileService::class, 'updateProfileFromInteraction'),
            'UserTasteProfileService must implement updateProfileFromInteraction()'
        );
    }

    public function test_getExplicitPreferences_method_exists(): void
    {
        $this->assertTrue(
            method_exists(\App\Domains\Common\Domain\Services\UserTasteProfileService::class, 'getExplicitPreferences'),
            'UserTasteProfileService must implement getExplicitPreferences()'
        );
    }

    public function test_getImplicitScores_method_exists(): void
    {
        $this->assertTrue(
            method_exists(\App\Domains\Common\Domain\Services\UserTasteProfileService::class, 'getImplicitScores'),
            'UserTasteProfileService must implement getImplicitScores()'
        );
    }

    public function test_setSizeProfile_method_exists(): void
    {
        $this->assertTrue(
            method_exists(\App\Domains\Common\Domain\Services\UserTasteProfileService::class, 'setSizeProfile'),
            'UserTasteProfileService must implement setSizeProfile()'
        );
    }

}
