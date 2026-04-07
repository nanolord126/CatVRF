<?php

declare(strict_types=1);

namespace Tests\Unit\Domains\Payment\Controllers;

use App\Domains\Payment\Controllers\PaymentRecordController;
use App\Domains\Payment\Services\PaymentService;
use App\Services\AuditService;
use App\Services\FraudControlService;
use Illuminate\Database\DatabaseManager;
use Illuminate\Routing\ResponseFactory;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;

/**
 * Unit-тесты для PaymentRecordController.
 */
final class PaymentControllerTest extends TestCase
{
    public function test_controller_is_final(): void
    {
        $ref = new \ReflectionClass(PaymentRecordController::class);
        $this->assertTrue($ref->isFinal());
    }

    public function test_controller_constructor_deps(): void
    {
        $ctor = (new \ReflectionClass(PaymentRecordController::class))->getConstructor();
        $this->assertNotNull($ctor);

        $params = $ctor->getParameters();
        $this->assertGreaterThanOrEqual(5, count($params));

        $names = array_map(fn(\ReflectionParameter $p) => $p->getName(), $params);
        $this->assertContains('paymentService', $names);
        $this->assertContains('fraud', $names);
        $this->assertContains('audit', $names);
        $this->assertContains('logger', $names);
    }

    public function test_controller_constructor_dep_types(): void
    {
        $ctor = (new \ReflectionClass(PaymentRecordController::class))->getConstructor();
        $types = [];
        foreach ($ctor->getParameters() as $p) {
            $types[$p->getName()] = $p->getType()?->getName();
        }

        $this->assertSame(PaymentService::class, $types['paymentService']);
        $this->assertSame(FraudControlService::class, $types['fraud']);
        $this->assertSame(AuditService::class, $types['audit']);
        $this->assertSame(LoggerInterface::class, $types['logger']);
    }

    public function test_controller_has_show_method(): void
    {
        $ref = new \ReflectionClass(PaymentRecordController::class);
        $this->assertTrue($ref->hasMethod('show'));
    }

    public function test_controller_has_store_method(): void
    {
        $ref = new \ReflectionClass(PaymentRecordController::class);
        $this->assertTrue($ref->hasMethod('store'));
    }

    public function test_controller_has_update_status_method(): void
    {
        $ref = new \ReflectionClass(PaymentRecordController::class);
        $this->assertTrue($ref->hasMethod('updateStatus'));
    }

    public function test_controller_has_extract_correlation_id(): void
    {
        $ref = new \ReflectionClass(PaymentRecordController::class);
        $this->assertTrue($ref->hasMethod('extractCorrelationId'));

        $method = $ref->getMethod('extractCorrelationId');
        $this->assertTrue($method->isPrivate());
    }

    public function test_controller_no_facade_imports(): void
    {
        $src = file_get_contents(__DIR__ . '/../../../../../app/Domains/Payment/Controllers/PaymentRecordController.php');
        $this->assertIsString($src);
        $this->assertStringNotContainsString('use Illuminate\\Support\\Facades\\', $src);
        $this->assertStringNotContainsString('response()', $src);
        $this->assertStringNotContainsString('request()', $src);
        $this->assertStringNotContainsString('auth()', $src);
    }

    public function test_controller_has_strict_types(): void
    {
        $src = file_get_contents(__DIR__ . '/../../../../../app/Domains/Payment/Controllers/PaymentRecordController.php');
        $this->assertStringContainsString('declare(strict_types=1);', $src);
    }
}
