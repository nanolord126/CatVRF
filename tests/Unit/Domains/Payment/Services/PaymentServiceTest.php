<?php

declare(strict_types=1);

namespace Tests\Unit\Domains\Payment\Services;

use App\Domains\Payment\Contracts\PaymentGatewayInterface;
use App\Domains\Payment\DTOs\CreatePaymentRecordDto;
use App\Domains\Payment\DTOs\UpdatePaymentRecordDto;
use App\Domains\Payment\Services\PaymentCoordinatorService;
use App\Domains\Payment\Services\PaymentService;
use App\Services\AuditService;
use App\Services\FraudControlService;
use Illuminate\Contracts\Auth\Guard;
use Illuminate\Database\DatabaseManager;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;

/**
 * Unit-тесты для PaymentService + PaymentCoordinatorService.
 */
final class PaymentServiceTest extends TestCase
{
    // ─── PaymentService: structural ──────────────────────────────

    public function test_payment_service_is_final_readonly(): void
    {
        $ref = new \ReflectionClass(PaymentService::class);
        $this->assertTrue($ref->isFinal());
        $this->assertTrue($ref->isReadOnly());
    }

    public function test_payment_service_constructor_requires_five_deps(): void
    {
        $ctor = (new \ReflectionClass(PaymentService::class))->getConstructor();
        $this->assertNotNull($ctor);

        $params = $ctor->getParameters();
        $this->assertGreaterThanOrEqual(5, count($params));

        $names = array_map(fn(\ReflectionParameter $p) => $p->getName(), $params);
        $this->assertContains('db', $names);
        $this->assertContains('logger', $names);
        $this->assertContains('fraud', $names);
        $this->assertContains('audit', $names);
    }

    public function test_payment_service_has_create_method(): void
    {
        $ref = new \ReflectionClass(PaymentService::class);
        $this->assertTrue($ref->hasMethod('create'));

        $method = $ref->getMethod('create');
        $params = $method->getParameters();
        $this->assertCount(1, $params);
        $this->assertSame(CreatePaymentRecordDto::class, $params[0]->getType()->getName());
    }

    public function test_payment_service_has_update_status_method(): void
    {
        $ref = new \ReflectionClass(PaymentService::class);
        $this->assertTrue($ref->hasMethod('updateStatus'));

        $method = $ref->getMethod('updateStatus');
        $params = $method->getParameters();
        $this->assertCount(1, $params);
        $this->assertSame(UpdatePaymentRecordDto::class, $params[0]->getType()->getName());
    }

    public function test_payment_service_has_find_methods(): void
    {
        $ref = new \ReflectionClass(PaymentService::class);
        $this->assertTrue($ref->hasMethod('findById'));
        $this->assertTrue($ref->hasMethod('findByIdempotencyKey'));
    }

    public function test_payment_service_no_facade_imports(): void
    {
        $src = file_get_contents(__DIR__ . '/../../../../../app/Domains/Payment/Services/PaymentService.php');
        $this->assertIsString($src);
        $this->assertStringNotContainsString('use Illuminate\\Support\\Facades\\', $src);
        $this->assertStringNotContainsString('DB::', $src);
        $this->assertStringNotContainsString('Auth::', $src);
        $this->assertStringNotContainsString('Log::', $src);
        $this->assertStringNotContainsString('Cache::', $src);
    }

    public function test_payment_service_has_strict_types(): void
    {
        $src = file_get_contents(__DIR__ . '/../../../../../app/Domains/Payment/Services/PaymentService.php');
        $this->assertIsString($src);
        $this->assertStringContainsString('declare(strict_types=1);', $src);
    }

    // ─── PaymentCoordinatorService: structural ───────────────────

    public function test_coordinator_is_final_readonly(): void
    {
        $ref = new \ReflectionClass(PaymentCoordinatorService::class);
        $this->assertTrue($ref->isFinal());
        $this->assertTrue($ref->isReadOnly());
    }

    public function test_coordinator_constructor_deps(): void
    {
        $ctor = (new \ReflectionClass(PaymentCoordinatorService::class))->getConstructor();
        $this->assertNotNull($ctor);

        $params = $ctor->getParameters();
        $this->assertGreaterThanOrEqual(4, count($params));

        $names = array_map(fn(\ReflectionParameter $p) => $p->getName(), $params);
        $this->assertContains('db', $names);
        $this->assertContains('logger', $names);
        $this->assertContains('fraud', $names);
        $this->assertContains('audit', $names);
    }

    public function test_coordinator_has_init_payment_method(): void
    {
        $ref = new \ReflectionClass(PaymentCoordinatorService::class);
        $this->assertTrue($ref->hasMethod('initPayment'));

        $method = $ref->getMethod('initPayment');
        $params = $method->getParameters();
        $this->assertGreaterThanOrEqual(2, count($params));

        // Должен принимать DTO + gateway
        $typeNames = array_map(
            fn(\ReflectionParameter $p) => $p->getType()?->getName(),
            $params,
        );
        $this->assertContains(CreatePaymentRecordDto::class, $typeNames);
        $this->assertContains(PaymentGatewayInterface::class, $typeNames);
    }

    public function test_coordinator_has_webhook_method(): void
    {
        $ref = new \ReflectionClass(PaymentCoordinatorService::class);
        $this->assertTrue($ref->hasMethod('handleWebhook'));
    }

    public function test_coordinator_has_capture_method(): void
    {
        $ref = new \ReflectionClass(PaymentCoordinatorService::class);
        $this->assertTrue($ref->hasMethod('capture'));
    }

    public function test_coordinator_has_refund_method(): void
    {
        $ref = new \ReflectionClass(PaymentCoordinatorService::class);
        $this->assertTrue($ref->hasMethod('refund'));
    }

    public function test_coordinator_no_facade_imports(): void
    {
        $src = file_get_contents(__DIR__ . '/../../../../../app/Domains/Payment/Services/PaymentCoordinatorService.php');
        $this->assertIsString($src);
        $this->assertStringNotContainsString('use Illuminate\\Support\\Facades\\', $src);
        $this->assertStringNotContainsString('DB::', $src);
    }

    // ─── PaymentGatewayInterface: contract ───────────────────────

    public function test_gateway_interface_exists(): void
    {
        $ref = new \ReflectionClass(PaymentGatewayInterface::class);
        $this->assertTrue($ref->isInterface());
    }

    public function test_gateway_interface_has_required_methods(): void
    {
        $ref = new \ReflectionClass(PaymentGatewayInterface::class);

        foreach (['initPayment', 'capture', 'refund', 'handleWebhook', 'getProvider'] as $method) {
            $this->assertTrue($ref->hasMethod($method), "Interface must have {$method}()");
        }
    }

    public function test_gateway_all_methods_require_correlation_id(): void
    {
        $ref = new \ReflectionClass(PaymentGatewayInterface::class);

        foreach (['initPayment', 'capture', 'refund'] as $methodName) {
            $method = $ref->getMethod($methodName);
            $paramNames = array_map(
                fn(\ReflectionParameter $p) => $p->getName(),
                $method->getParameters(),
            );
            $this->assertContains(
                'correlationId',
                $paramNames,
                "Method {$methodName}() must accept correlationId",
            );
        }
    }
}
