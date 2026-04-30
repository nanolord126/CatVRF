<?php

declare(strict_types=1);

namespace Tests\Unit\Services;

use PHPUnit\Framework\TestCase;
use App\Services\ML\UserTasteAnalyzerService;

/**
 * Unit tests for UserTasteAnalyzerService.
 *
 * @covers \App\Services\ML\UserTasteAnalyzerService
 */
final class UserTasteAnalyzerServiceTest extends TestCase
{
    public function test_class_is_final_readonly(): void
    {
        $reflection = new \ReflectionClass(UserTasteAnalyzerService::class);
        $this->assertTrue($reflection->isFinal(), 'UserTasteAnalyzerService must be final');
        $this->assertTrue($reflection->isReadOnly(), 'UserTasteAnalyzerService must be readonly');
    }

    public function test_has_constructor_injection(): void
    {
        $reflection = new \ReflectionClass(UserTasteAnalyzerService::class);
        $constructor = $reflection->getConstructor();
        $this->assertNotNull($constructor);
        $this->assertGreaterThan(0, $constructor->getNumberOfParameters());
    }

    public function test_analyze_and_save_user_profile_method_exists(): void
    {
        $this->assertTrue(
            method_exists(UserTasteAnalyzerService::class, 'analyzeAndSaveUserProfile'),
            'UserTasteAnalyzerService must implement analyzeAndSaveUserProfile()'
        );
    }

    public function test_get_profile_method_exists(): void
    {
        $this->assertTrue(
            method_exists(UserTasteAnalyzerService::class, 'getProfile'),
            'UserTasteAnalyzerService must implement getProfile()'
        );
    }
}
