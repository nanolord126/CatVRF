<?php

declare(strict_types=1);

namespace Tests\Unit\Domains\Common;

use PHPUnit\Framework\TestCase;
use App\Domains\Common\Domain\Services\UserTasteProfileService;

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
            UserTasteProfileService::class
        );
        $this->assertTrue($reflection->isFinal(), 'UserTasteProfileService must be final');
    }

    public function test_class_is_readonly(): void
    {
        $reflection = new \ReflectionClass(
            UserTasteProfileService::class
        );
        $this->assertTrue($reflection->isReadOnly(), 'UserTasteProfileService must be readonly');
    }

    public function test_has_constructor_injection(): void
    {
        $reflection = new \ReflectionClass(
            UserTasteProfileService::class
        );
        $constructor = $reflection->getConstructor();
        $this->assertNotNull($constructor, 'UserTasteProfileService must have __construct');
        $this->assertGreaterThan(0, $constructor->getNumberOfParameters());
    }

    public function test_get_or_create_profile_method_exists(): void
    {
        $this->assertTrue(
            method_exists(UserTasteProfileService::class, 'getOrCreateProfile'),
            'UserTasteProfileService must implement getOrCreateProfile()'
        );
    }

    public function test_update_profile_from_interaction_method_exists(): void
    {
        $this->assertTrue(
            method_exists(UserTasteProfileService::class, 'updateProfileFromInteraction'),
            'UserTasteProfileService must implement updateProfileFromInteraction()'
        );
    }

    public function test_get_explicit_preferences_method_exists(): void
    {
        $this->assertTrue(
            method_exists(UserTasteProfileService::class, 'getExplicitPreferences'),
            'UserTasteProfileService must implement getExplicitPreferences()'
        );
    }

    public function test_get_implicit_scores_method_exists(): void
    {
        $this->assertTrue(
            method_exists(UserTasteProfileService::class, 'getImplicitScores'),
            'UserTasteProfileService must implement getImplicitScores()'
        );
    }

    public function test_set_size_profile_method_exists(): void
    {
        $this->assertTrue(
            method_exists(UserTasteProfileService::class, 'setSizeProfile'),
            'UserTasteProfileService must implement setSizeProfile()'
        );
    }
}
