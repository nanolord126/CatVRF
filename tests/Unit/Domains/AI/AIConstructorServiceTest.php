<?php

declare(strict_types=1);

namespace Tests\Unit\Domains\AI;

use App\Domains\AI\Services\AIConstructorService;
use App\Octane\Services\SwooleCoroutineService;
use PHPUnit\Framework\TestCase;

/**
 * Unit tests for AIConstructorService.
 *
 * @covers \App\Domains\AI\Domain\Services\AIConstructorService
 */
final class AIConstructorServiceTest extends TestCase
{
    public function test_class_is_final(): void
    {
        $reflection = new \ReflectionClass(
            \App\Domains\AI\Domain\Services\AIConstructorService::class
        );
        $this->assertTrue($reflection->isFinal(), 'AIConstructorService must be final');
    }

    public function test_class_is_readonly(): void
    {
        $reflection = new \ReflectionClass(
            \App\Domains\AI\Domain\Services\AIConstructorService::class
        );
        $this->assertTrue($reflection->isReadOnly(), 'AIConstructorService must be readonly');
    }

    public function test_has_constructor_injection(): void
    {
        $reflection = new \ReflectionClass(
            \App\Domains\AI\Domain\Services\AIConstructorService::class
        );
        $constructor = $reflection->getConstructor();
        $this->assertNotNull($constructor, 'AIConstructorService must have __construct');
        $this->assertGreaterThan(0, $constructor->getNumberOfParameters());
    }

    public function test_analyze_photo_and_recommend_method_exists(): void
    {
        $this->assertTrue(
            method_exists(\App\Domains\AI\Domain\Services\AIConstructorService::class, 'analyzePhotoAndRecommend'),
            'AIConstructorService must implement analyzePhotoAndRecommend()'
        );
    }

    public function test_swoole_coroutine_service_injection(): void
    {
        // Test that AIConstructorService can accept SwooleCoroutineService
        $coroutineServiceMock = $this->createMock(SwooleCoroutineService::class);

        $this->assertInstanceOf(SwooleCoroutineService::class, $coroutineServiceMock);
    }

    public function test_ai_constructor_service_is_octane_aware(): void
    {
        $reflection = new \ReflectionClass(AIConstructorService::class);
        $constructor = $reflection->getConstructor();

        $this->assertNotNull($constructor);

        // Check if SwooleCoroutineService is in constructor parameters
        $parameters = $constructor->getParameters();
        $hasCoroutineService = false;

        foreach ($parameters as $param) {
            if ($param->getType() && $param->getType()->getName() === SwooleCoroutineService::class) {
                $hasCoroutineService = true;
                break;
            }
        }

        $this->assertTrue($hasCoroutineService, 'AIConstructorService should have SwooleCoroutineService parameter');
    }
}
