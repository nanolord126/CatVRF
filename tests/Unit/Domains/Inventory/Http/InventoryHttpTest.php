<?php

declare(strict_types=1);

namespace Tests\Unit\Domains\Inventory\Http;

use App\Domains\Inventory\Http\Controllers\InventoryController;
use App\Domains\Inventory\Http\Requests\AdjustStockRequest;
use App\Domains\Inventory\Http\Requests\ReserveStockRequest;
use App\Domains\Inventory\Http\Resources\InventoryItemResource;
use App\Domains\Inventory\Http\Resources\ReservationResource;
use App\Domains\Inventory\Http\Resources\StockMovementResource;
use App\Domains\Inventory\Services\InventoryService;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use ReflectionClass;

/**
 * Тесты Layer 4 (Requests) + Layer 5 (Resources) + Controller.
 *
 * Проверяем: final, strict_types, no facades, правила валидации,
 * correlation_id в ресурсах, DI в контроллере.
 */
#[CoversClass(ReserveStockRequest::class)]
#[CoversClass(AdjustStockRequest::class)]
#[CoversClass(InventoryItemResource::class)]
#[CoversClass(StockMovementResource::class)]
#[CoversClass(ReservationResource::class)]
#[CoversClass(InventoryController::class)]
final class InventoryHttpTest extends TestCase
{
    /* ================================================================== */
    /*  Providers                                                          */
    /* ================================================================== */

    /** @return list<array{class-string}> */
    public static function requestClassesProvider(): array
    {
        return [
            [ReserveStockRequest::class],
            [AdjustStockRequest::class],
        ];
    }

    /** @return list<array{class-string}> */
    public static function resourceClassesProvider(): array
    {
        return [
            [InventoryItemResource::class],
            [StockMovementResource::class],
            [ReservationResource::class],
        ];
    }

    /* ================================================================== */
    /*  Requests — structural                                              */
    /* ================================================================== */

    #[Test]
    #[DataProvider('requestClassesProvider')]
    public function request_is_final(string $class): void
    {
        self::assertTrue((new ReflectionClass($class))->isFinal());
    }

    #[Test]
    #[DataProvider('requestClassesProvider')]
    public function request_extends_form_request(string $class): void
    {
        $ref = new ReflectionClass($class);
        self::assertTrue($ref->isSubclassOf(\Illuminate\Foundation\Http\FormRequest::class));
    }

    #[Test]
    #[DataProvider('requestClassesProvider')]
    public function request_has_strict_types(string $class): void
    {
        $ref  = new ReflectionClass($class);
        $code = (string) file_get_contents((string) $ref->getFileName());
        self::assertStringContainsString('declare(strict_types=1);', $code);
    }

    #[Test]
    #[DataProvider('requestClassesProvider')]
    public function request_has_no_facades(string $class): void
    {
        $ref  = new ReflectionClass($class);
        $code = (string) file_get_contents((string) $ref->getFileName());
        self::assertStringNotContainsString('use Illuminate\Support\Facades\\', $code);
    }

    #[Test]
    #[DataProvider('requestClassesProvider')]
    public function request_has_authorize_method(string $class): void
    {
        self::assertTrue(method_exists($class, 'authorize'));
    }

    #[Test]
    #[DataProvider('requestClassesProvider')]
    public function request_has_rules_method(string $class): void
    {
        self::assertTrue(method_exists($class, 'rules'));
    }

    #[Test]
    #[DataProvider('requestClassesProvider')]
    public function request_has_messages_method(string $class): void
    {
        self::assertTrue(method_exists($class, 'messages'));
    }

    /* ================================================================== */
    /*  ReserveStockRequest — rules                                        */
    /* ================================================================== */

    #[Test]
    public function reserve_stock_request_rules_contain_required_fields(): void
    {
        $ref     = new ReflectionClass(ReserveStockRequest::class);
        $request = $ref->newInstanceWithoutConstructor();
        $rules   = $request->rules();

        $required = ['product_id', 'warehouse_id', 'quantity', 'source_type', 'source_id', 'correlation_id'];

        foreach ($required as $field) {
            self::assertArrayHasKey($field, $rules, "ReserveStockRequest must validate '{$field}'");
            self::assertContains('required', $rules[$field], "Field '{$field}' must be required");
        }
    }

    #[Test]
    public function reserve_stock_request_has_optional_fields(): void
    {
        $ref     = new ReflectionClass(ReserveStockRequest::class);
        $request = $ref->newInstanceWithoutConstructor();
        $rules   = $request->rules();

        $optional = ['cart_id', 'order_id', 'expires_at'];

        foreach ($optional as $field) {
            self::assertArrayHasKey($field, $rules, "ReserveStockRequest should validate '{$field}'");
            self::assertContains('nullable', $rules[$field], "Field '{$field}' should be nullable");
        }
    }

    #[Test]
    public function reserve_stock_request_correlation_id_is_uuid(): void
    {
        $ref     = new ReflectionClass(ReserveStockRequest::class);
        $request = $ref->newInstanceWithoutConstructor();
        $rules   = $request->rules();

        self::assertContains('uuid', $rules['correlation_id']);
    }

    /* ================================================================== */
    /*  AdjustStockRequest — rules                                         */
    /* ================================================================== */

    #[Test]
    public function adjust_stock_request_rules_contain_required_fields(): void
    {
        $ref     = new ReflectionClass(AdjustStockRequest::class);
        $request = $ref->newInstanceWithoutConstructor();
        $rules   = $request->rules();

        $required = ['product_id', 'warehouse_id', 'new_quantity', 'reason', 'correlation_id'];

        foreach ($required as $field) {
            self::assertArrayHasKey($field, $rules, "AdjustStockRequest must validate '{$field}'");
            self::assertContains('required', $rules[$field], "Field '{$field}' must be required");
        }
    }

    #[Test]
    public function adjust_stock_request_correlation_id_is_uuid(): void
    {
        $ref     = new ReflectionClass(AdjustStockRequest::class);
        $request = $ref->newInstanceWithoutConstructor();
        $rules   = $request->rules();

        self::assertContains('uuid', $rules['correlation_id']);
    }

    /* ================================================================== */
    /*  Resources — structural                                             */
    /* ================================================================== */

    #[Test]
    #[DataProvider('resourceClassesProvider')]
    public function resource_is_final(string $class): void
    {
        self::assertTrue((new ReflectionClass($class))->isFinal());
    }

    #[Test]
    #[DataProvider('resourceClassesProvider')]
    public function resource_extends_json_resource(string $class): void
    {
        $ref = new ReflectionClass($class);
        self::assertTrue($ref->isSubclassOf(\Illuminate\Http\Resources\Json\JsonResource::class));
    }

    #[Test]
    #[DataProvider('resourceClassesProvider')]
    public function resource_has_strict_types(string $class): void
    {
        $ref  = new ReflectionClass($class);
        $code = (string) file_get_contents((string) $ref->getFileName());
        self::assertStringContainsString('declare(strict_types=1);', $code);
    }

    #[Test]
    #[DataProvider('resourceClassesProvider')]
    public function resource_has_no_facades(string $class): void
    {
        $ref  = new ReflectionClass($class);
        $code = (string) file_get_contents((string) $ref->getFileName());
        self::assertStringNotContainsString('use Illuminate\Support\Facades\\', $code);
    }

    #[Test]
    #[DataProvider('resourceClassesProvider')]
    public function resource_has_to_array_method(string $class): void
    {
        self::assertTrue(method_exists($class, 'toArray'));
    }

    #[Test]
    #[DataProvider('resourceClassesProvider')]
    public function resource_to_array_contains_correlation_id(string $class): void
    {
        $ref  = new ReflectionClass($class);
        $code = (string) file_get_contents((string) $ref->getFileName());
        self::assertStringContainsString('correlation_id', $code, "{$class} toArray must include correlation_id");
    }

    /* ================================================================== */
    /*  Controller — structural                                            */
    /* ================================================================== */

    #[Test]
    public function controller_is_final(): void
    {
        self::assertTrue((new ReflectionClass(InventoryController::class))->isFinal());
    }

    #[Test]
    public function controller_has_strict_types(): void
    {
        $ref  = new ReflectionClass(InventoryController::class);
        $code = (string) file_get_contents((string) $ref->getFileName());
        self::assertStringContainsString('declare(strict_types=1);', $code);
    }

    #[Test]
    public function controller_has_no_facades(): void
    {
        $ref  = new ReflectionClass(InventoryController::class);
        $code = (string) file_get_contents((string) $ref->getFileName());
        self::assertStringNotContainsString('use Illuminate\Support\Facades\\', $code);
    }

    #[Test]
    public function controller_uses_constructor_injection(): void
    {
        $ref  = new ReflectionClass(InventoryController::class);
        $ctor = $ref->getConstructor();
        self::assertNotNull($ctor);

        $types = array_map(
            fn (\ReflectionParameter $p) => (string) $p->getType(),
            $ctor->getParameters(),
        );

        self::assertContains(InventoryService::class, $types);
    }

    #[Test]
    public function controller_has_required_methods(): void
    {
        $expected = ['index', 'show', 'reserve', 'adjust', 'available'];

        foreach ($expected as $method) {
            self::assertTrue(
                method_exists(InventoryController::class, $method),
                "InventoryController must have method '{$method}'",
            );
        }
    }

    #[Test]
    public function controller_methods_have_correct_type_hints(): void
    {
        $ref = new ReflectionClass(InventoryController::class);

        // reserve() accepts ReserveStockRequest
        $reserve = $ref->getMethod('reserve');
        $paramTypes = array_map(
            fn (\ReflectionParameter $p) => (string) $p->getType(),
            $reserve->getParameters(),
        );
        self::assertContains(ReserveStockRequest::class, $paramTypes);

        // adjust() accepts AdjustStockRequest
        $adjust = $ref->getMethod('adjust');
        $paramTypes = array_map(
            fn (\ReflectionParameter $p) => (string) $p->getType(),
            $adjust->getParameters(),
        );
        self::assertContains(AdjustStockRequest::class, $paramTypes);
    }
}
