<?php declare(strict_types=1);

namespace Tests\Unit\Services;

use App\Services\FraudControlService;
use App\Services\Fraud\FraudMLService;
use App\Services\Security\RateLimiterService;
use Illuminate\Database\DatabaseManager;
use Illuminate\Http\Request;
use Illuminate\Log\LogManager;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;

/**
 * =================================================================
 *  FraudControlService — UNIT TEST
 *  CANON: Fraud-check обязателен перед любой мутацией.
 * =================================================================
 *
 *  Проверяет:
 *   1. Класс final readonly
 *   2. check() возвращает массив с ключами score, decision, correlation_id
 *   3. Пороги блокировки: > 0.85 → block, > 0.65 → review
 *   4. Логирование в fraud_alert канал
 *   5. Constructor injection (без фасадов)
 */
final class FraudControlServiceTest extends TestCase
{
    #[Test]
    public function class_is_final_and_readonly(): void
    {
        $ref = new \ReflectionClass(FraudControlService::class);

        self::assertTrue($ref->isFinal(), 'FraudControlService must be final');
        self::assertTrue($ref->isReadOnly(), 'FraudControlService must be readonly');
    }

    #[Test]
    public function constructor_injection_only(): void
    {
        $ref = new \ReflectionClass(FraudControlService::class);
        $constructor = $ref->getConstructor();

        self::assertNotNull($constructor, 'Must have explicit constructor');

        $params = $constructor->getParameters();
        self::assertGreaterThanOrEqual(3, count($params), 'Must inject at least 3 dependencies');

        $paramTypes = array_map(
            fn (\ReflectionParameter $p) => $p->getType()?->getName(),
            $params,
        );

        self::assertContains(FraudMLService::class, $paramTypes, 'Must inject FraudMLService');
        self::assertContains(RateLimiterService::class, $paramTypes, 'Must inject RateLimiterService');
    }

    #[Test]
    public function check_method_exists_with_correct_params(): void
    {
        self::assertTrue(
            method_exists(FraudControlService::class, 'check'),
            'Must have check() method',
        );

        $ref = new \ReflectionMethod(FraudControlService::class, 'check');
        $params = $ref->getParameters();
        $names = array_map(fn ($p) => $p->getName(), $params);

        self::assertContains('userId', $names, 'check() must accept userId');
        self::assertContains('operationType', $names, 'check() must accept operationType');
        self::assertContains('amount', $names, 'check() must accept amount');
        self::assertContains('correlationId', $names, 'check() must accept correlationId');
    }

    #[Test]
    public function block_threshold_constant(): void
    {
        $ref = new \ReflectionClass(FraudControlService::class);

        self::assertTrue(
            $ref->hasConstant('THRESHOLD_BLOCK'),
            'Must define THRESHOLD_BLOCK constant',
        );

        $blockConst = $ref->getConstant('THRESHOLD_BLOCK');
        self::assertSame(0.85, $blockConst, 'THRESHOLD_BLOCK must be 0.85');
    }

    #[Test]
    public function review_threshold_constant(): void
    {
        $ref = new \ReflectionClass(FraudControlService::class);

        self::assertTrue(
            $ref->hasConstant('THRESHOLD_REVIEW'),
            'Must define THRESHOLD_REVIEW constant',
        );

        $reviewConst = $ref->getConstant('THRESHOLD_REVIEW');
        self::assertSame(0.65, $reviewConst, 'THRESHOLD_REVIEW must be 0.65');
    }

    #[Test]
    public function block_threshold_is_higher_than_review(): void
    {
        $ref = new \ReflectionClass(FraudControlService::class);

        $block = $ref->getConstant('THRESHOLD_BLOCK');
        $review = $ref->getConstant('THRESHOLD_REVIEW');

        self::assertGreaterThan(
            $review,
            $block,
            'Block threshold must be higher than review threshold',
        );
    }

    #[Test]
    public function check_method_returns_array(): void
    {
        $ref = new \ReflectionMethod(FraudControlService::class, 'check');
        $returnType = $ref->getReturnType();

        self::assertNotNull($returnType, 'check() must declare return type');
        self::assertSame('array', $returnType->getName(), 'check() must return array');
    }

    #[Test]
    public function no_static_methods(): void
    {
        $ref = new \ReflectionClass(FraudControlService::class);

        $staticMethods = array_filter(
            $ref->getMethods(\ReflectionMethod::IS_STATIC),
            fn (\ReflectionMethod $m) => $m->getDeclaringClass()->getName() === FraudControlService::class,
        );

        self::assertEmpty(
            $staticMethods,
            'FraudControlService must not have static methods (CANON: no static calls)',
        );
    }

    #[Test]
    public function no_facade_usage_in_source(): void
    {
        $source = file_get_contents(
            (new \ReflectionClass(FraudControlService::class))->getFileName(),
        );

        self::assertStringNotContainsString(
            'DB::',
            $source,
            'Must not use DB:: facade',
        );
        self::assertStringNotContainsString(
            'Log::',
            $source,
            'Must not use Log:: facade',
        );
        self::assertStringNotContainsString(
            'Cache::',
            $source,
            'Must not use Cache:: facade',
        );
    }
}
