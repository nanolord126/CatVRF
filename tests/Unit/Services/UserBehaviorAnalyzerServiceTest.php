<?php

declare(strict_types=1);

namespace Tests\Unit\Services;

use PHPUnit\Framework\TestCase;
use App\Services\ML\UserBehaviorAnalyzerService;

/**
 * Unit tests for UserBehaviorAnalyzerService.
 *
 * @covers \App\Services\ML\UserBehaviorAnalyzerService
 */
final class UserBehaviorAnalyzerServiceTest extends TestCase
{
    public function test_class_is_final_readonly(): void
    {
        $reflection = new \ReflectionClass(UserBehaviorAnalyzerService::class);
        $this->assertTrue($reflection->isFinal(), 'UserBehaviorAnalyzerService must be final');
        $this->assertTrue($reflection->isReadOnly(), 'UserBehaviorAnalyzerService must be readonly');
    }

    public function test_has_constructor_injection(): void
    {
        $reflection = new \ReflectionClass(UserBehaviorAnalyzerService::class);
        $constructor = $reflection->getConstructor();
        $this->assertNotNull($constructor);
        $this->assertGreaterThan(0, $constructor->getNumberOfParameters());
    }

    public function test_classify_user_method_exists(): void
    {
        $this->assertTrue(
            method_exists(UserBehaviorAnalyzerService::class, 'classifyUser'),
            'UserBehaviorAnalyzerService must implement classifyUser()'
        );
    }

    public function test_is_new_user_method_exists(): void
    {
        $this->assertTrue(
            method_exists(UserBehaviorAnalyzerService::class, 'isNewUser'),
            'UserBehaviorAnalyzerService must implement isNewUser()'
        );
    }

    public function test_process_event_method_exists(): void
    {
        $this->assertTrue(
            method_exists(UserBehaviorAnalyzerService::class, 'processEvent'),
            'UserBehaviorAnalyzerService must implement processEvent()'
        );
    }

    public function test_get_new_users_count_method_exists(): void
    {
        $this->assertTrue(
            method_exists(UserBehaviorAnalyzerService::class, 'getNewUsersCount'),
            'UserBehaviorAnalyzerService must implement getNewUsersCount()'
        );
    }

    public function test_get_returning_users_count_method_exists(): void
    {
        $this->assertTrue(
            method_exists(UserBehaviorAnalyzerService::class, 'getReturningUsersCount'),
            'UserBehaviorAnalyzerService must implement getReturningUsersCount()'
        );
    }

    public function test_get_pattern_method_exists(): void
    {
        $this->assertTrue(
            method_exists(UserBehaviorAnalyzerService::class, 'getPattern'),
            'UserBehaviorAnalyzerService must implement getPattern()'
        );
    }
}
