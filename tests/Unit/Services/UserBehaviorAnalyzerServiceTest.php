<?php declare(strict_types=1);

namespace Tests\Unit\Services;

use PHPUnit\Framework\TestCase;

/**
 * Unit tests for UserBehaviorAnalyzerService.
 *
 * @covers \App\Services\ML\UserBehaviorAnalyzerService
 */
final class UserBehaviorAnalyzerServiceTest extends TestCase
{
    public function test_class_is_final_readonly(): void
    {
        $reflection = new \ReflectionClass(\App\Services\ML\UserBehaviorAnalyzerService::class);
        $this->assertTrue($reflection->isFinal(), 'UserBehaviorAnalyzerService must be final');
        $this->assertTrue($reflection->isReadOnly(), 'UserBehaviorAnalyzerService must be readonly');
    }

    public function test_has_constructor_injection(): void
    {
        $reflection = new \ReflectionClass(\App\Services\ML\UserBehaviorAnalyzerService::class);
        $constructor = $reflection->getConstructor();
        $this->assertNotNull($constructor);
        $this->assertGreaterThan(0, $constructor->getNumberOfParameters());
    }

    public function test_classifyUser_method_exists(): void
    {
        $this->assertTrue(
            method_exists(\App\Services\ML\UserBehaviorAnalyzerService::class, 'classifyUser'),
            'UserBehaviorAnalyzerService must implement classifyUser()'
        );
    }

    public function test_isNewUser_method_exists(): void
    {
        $this->assertTrue(
            method_exists(\App\Services\ML\UserBehaviorAnalyzerService::class, 'isNewUser'),
            'UserBehaviorAnalyzerService must implement isNewUser()'
        );
    }

    public function test_processEvent_method_exists(): void
    {
        $this->assertTrue(
            method_exists(\App\Services\ML\UserBehaviorAnalyzerService::class, 'processEvent'),
            'UserBehaviorAnalyzerService must implement processEvent()'
        );
    }

    public function test_getNewUsersCount_method_exists(): void
    {
        $this->assertTrue(
            method_exists(\App\Services\ML\UserBehaviorAnalyzerService::class, 'getNewUsersCount'),
            'UserBehaviorAnalyzerService must implement getNewUsersCount()'
        );
    }

    public function test_getReturningUsersCount_method_exists(): void
    {
        $this->assertTrue(
            method_exists(\App\Services\ML\UserBehaviorAnalyzerService::class, 'getReturningUsersCount'),
            'UserBehaviorAnalyzerService must implement getReturningUsersCount()'
        );
    }

    public function test_getPattern_method_exists(): void
    {
        $this->assertTrue(
            method_exists(\App\Services\ML\UserBehaviorAnalyzerService::class, 'getPattern'),
            'UserBehaviorAnalyzerService must implement getPattern()'
        );
    }

}
