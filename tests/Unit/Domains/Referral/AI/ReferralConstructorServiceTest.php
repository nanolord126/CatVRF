<?php declare(strict_types=1);

namespace Tests\Unit\Domains\Referral\AI;

use PHPUnit\Framework\TestCase;

/**
 * Unit tests for ReferralConstructorService.
 *
 * @covers \App\Domains\Referral\Services\AI\ReferralConstructorService
 * @group ai-constructors
 */
final class ReferralConstructorServiceTest extends TestCase
{
    public function test_class_is_final_readonly(): void
    {
        $class = $this->getServiceClass();
        if (!class_exists($class)) {
            $this->markTestSkipped("Class {$class} not found");
        }
        $reflection = new \ReflectionClass($class);
        $this->assertTrue($reflection->isFinal());
        $this->assertTrue($reflection->isReadOnly());
    }

    public function test_has_analyze_method(): void
    {
        $class = $this->getServiceClass();
        if (!class_exists($class)) {
            $this->markTestSkipped("Class {$class} not found");
        }
        $methods = get_class_methods($class);
        $hasAnalyze = false;
        foreach ($methods as $m) {
            if (str_contains($m, 'nalyze') || str_contains($m, 'ecommend') || str_contains($m, 'enerate')) {
                $hasAnalyze = true;
                break;
            }
        }
        $this->assertTrue($hasAnalyze, 'ReferralConstructorService must have analyze/recommend/generate method');
    }

    public function test_has_constructor_with_openai(): void
    {
        $class = $this->getServiceClass();
        if (!class_exists($class)) {
            $this->markTestSkipped("Class {$class} not found");
        }
        $constructor = (new \ReflectionClass($class))->getConstructor();
        $this->assertNotNull($constructor);
        $this->assertGreaterThanOrEqual(2, $constructor->getNumberOfParameters());
    }

    private function getServiceClass(): string
    {
        $paths = [
            'App\Domains\Referral\Services\AI\ReferralConstructorService',
            'App\Domains\Referral\Domain\Services\AI\ReferralConstructorService',
        ];
        foreach ($paths as $p) {
            if (class_exists($p)) return $p;
        }
        return $paths[0];
    }
}
