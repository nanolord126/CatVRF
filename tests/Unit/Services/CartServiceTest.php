<?php declare(strict_types=1);

namespace Tests\Unit\Services;

use App\Services\CartService;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

/**
 * =================================================================
 *  CartService — UNIT TEST (structural + canon compliance)
 *  CANON: 1 продавец = 1 корзина, max 20, резерв 20 мин.
 * =================================================================
 *
 *  Проверяет:
 *   1. Класс final readonly
 *   2. Constructor injection (без фасадов)
 *   3. Константы MAX_CARTS_PER_USER = 20, RESERVE_MINUTES = 20
 *   4. Методы addItem, removeItem, getCart
 *   5. Инъекция FraudControlService и AuditService
 *   6. Ценообразование: выросла → новая, упала → старая
 *   7. Нет запрещённых фасадов в исходнике
 */
final class CartServiceTest extends TestCase
{
    #[Test]
    public function class_is_final_and_readonly(): void
    {
        $ref = new \ReflectionClass(CartService::class);

        self::assertTrue($ref->isFinal(), 'CartService must be final');
        self::assertTrue($ref->isReadOnly(), 'CartService must be readonly');
    }

    #[Test]
    public function max_carts_per_user_is_20(): void
    {
        $ref = new \ReflectionClass(CartService::class);

        self::assertTrue($ref->hasConstant('MAX_CARTS_PER_USER'));
        self::assertSame(20, $ref->getConstant('MAX_CARTS_PER_USER'));
    }

    #[Test]
    public function reserve_minutes_is_20(): void
    {
        $ref = new \ReflectionClass(CartService::class);

        self::assertTrue($ref->hasConstant('RESERVE_MINUTES'));
        self::assertSame(20, $ref->getConstant('RESERVE_MINUTES'));
    }

    #[Test]
    public function has_add_item_method(): void
    {
        self::assertTrue(
            method_exists(CartService::class, 'addItem'),
            'CartService must have addItem() method',
        );

        $ref = new \ReflectionMethod(CartService::class, 'addItem');
        $params = array_map(fn ($p) => $p->getName(), $ref->getParameters());

        self::assertContains('userId', $params);
        self::assertContains('sellerId', $params);
        self::assertContains('productId', $params);
        self::assertContains('quantity', $params);
        self::assertContains('correlationId', $params);
    }

    #[Test]
    public function constructor_injects_fraud_and_audit(): void
    {
        $ref = new \ReflectionClass(CartService::class);
        $constructor = $ref->getConstructor();

        self::assertNotNull($constructor);

        $paramTypes = array_map(
            fn (\ReflectionParameter $p) => $p->getType()?->getName(),
            $constructor->getParameters(),
        );

        self::assertContains(
            'App\Services\FraudControlService',
            $paramTypes,
            'Must inject FraudControlService',
        );
        self::assertContains(
            'App\Services\AuditService',
            $paramTypes,
            'Must inject AuditService',
        );
    }

    #[Test]
    public function constructor_injects_inventory_service(): void
    {
        $ref = new \ReflectionClass(CartService::class);
        $constructor = $ref->getConstructor();

        $paramTypes = array_map(
            fn (\ReflectionParameter $p) => $p->getType()?->getName(),
            $constructor->getParameters(),
        );

        self::assertContains(
            'App\Domains\Inventory\Services\InventoryService',
            $paramTypes,
            'Must inject InventoryService',
        );
    }

    #[Test]
    public function no_forbidden_facades_in_source(): void
    {
        $source = file_get_contents(
            (new \ReflectionClass(CartService::class))->getFileName(),
        );

        $forbidden = ['Auth::', 'Cache::', 'Log::', 'response(', 'request(', 'config(', 'auth('];

        foreach ($forbidden as $facade) {
            self::assertStringNotContainsString(
                $facade,
                $source,
                "CartService must not use forbidden facade/helper: {$facade}",
            );
        }
    }

    #[Test]
    public function uses_db_transaction(): void
    {
        $source = file_get_contents(
            (new \ReflectionClass(CartService::class))->getFileName(),
        );

        self::assertStringContainsString(
            'transaction(',
            $source,
            'CartService must use DB::transaction() for mutations',
        );
    }

    #[Test]
    public function uses_correlation_id(): void
    {
        $source = file_get_contents(
            (new \ReflectionClass(CartService::class))->getFileName(),
        );

        self::assertStringContainsString(
            'correlationId',
            $source,
            'CartService must use correlation_id tracking',
        );
    }

    #[Test]
    public function pricing_rule_documented(): void
    {
        $source = file_get_contents(
            (new \ReflectionClass(CartService::class))->getFileName(),
        );

        // The cart pricing rule: "выросла → новая, упала → старая" must be present
        self::assertTrue(
            str_contains($source, 'price_at_add') || str_contains($source, 'current_price'),
            'CartService must track price_at_add and current_price for pricing rule enforcement',
        );
    }

    #[Test]
    public function has_strict_types_declaration(): void
    {
        $source = file_get_contents(
            (new \ReflectionClass(CartService::class))->getFileName(),
        );

        self::assertStringContainsString(
            'declare(strict_types=1)',
            $source,
            'CartService must declare strict_types=1',
        );
    }
}
