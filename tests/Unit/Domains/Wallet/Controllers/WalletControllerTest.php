<?php

declare(strict_types=1);

namespace Tests\Unit\Domains\Wallet\Controllers;

use App\Domains\Wallet\Controllers\WalletController;
use App\Domains\Wallet\Services\WalletService;
use App\Services\AuditService;
use App\Services\FraudControlService;
use Illuminate\Contracts\Routing\ResponseFactory;
use Illuminate\Database\DatabaseManager;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;

/**
 * Unit-тесты WalletController.
 *
 * WalletService, FraudControlService, AuditService — final classes.
 * Создаём через Reflection (newInstanceWithoutConstructor).
 */
final class WalletControllerTest extends TestCase
{
    public function test_can_instantiate_controller(): void
    {
        $controller = $this->createController();
        $this->assertInstanceOf(WalletController::class, $controller);
    }

    public function test_controller_is_final(): void
    {
        $ref = new \ReflectionClass(WalletController::class);
        $this->assertTrue($ref->isFinal());
    }

    public function test_controller_has_required_methods(): void
    {
        $ref = new \ReflectionClass(WalletController::class);

        $this->assertTrue($ref->hasMethod('show'));
        $this->assertTrue($ref->hasMethod('store'));
        $this->assertTrue($ref->hasMethod('destroy'));
    }

    public function test_controller_constructor_has_no_facades(): void
    {
        $ref = new \ReflectionClass(WalletController::class);
        $constructor = $ref->getConstructor();

        $this->assertNotNull($constructor);
        $params = $constructor->getParameters();
        $this->assertCount(6, $params);

        $paramNames = array_map(
            static fn (\ReflectionParameter $p): string => $p->getName(),
            $params,
        );

        $this->assertContains('walletService', $paramNames);
        $this->assertContains('fraud', $paramNames);
        $this->assertContains('audit', $paramNames);
        $this->assertContains('db', $paramNames);
        $this->assertContains('logger', $paramNames);
        $this->assertContains('response', $paramNames);
    }

    public function test_extract_correlation_id_returns_uuid_when_no_header(): void
    {
        $controller = $this->createController();

        $method = new \ReflectionMethod(WalletController::class, 'extractCorrelationId');
        $method->setAccessible(true);

        $request = \Illuminate\Http\Request::create('/test');
        $result = $method->invoke($controller, $request);

        $this->assertNotEmpty($result);
        $this->assertMatchesRegularExpression('/^[0-9a-f\-]{36}$/', $result);
    }

    public function test_extract_correlation_id_returns_header_when_present(): void
    {
        $controller = $this->createController();

        $method = new \ReflectionMethod(WalletController::class, 'extractCorrelationId');
        $method->setAccessible(true);

        $request = \Illuminate\Http\Request::create('/test', 'GET', [], [], [], [
            'HTTP_X-Correlation-ID' => 'my-corr-id',
        ]);
        $result = $method->invoke($controller, $request);

        $this->assertSame('my-corr-id', $result);
    }

    // ─── Helpers ─────────────────────────────────────────────────────

    private function createController(): WalletController
    {
        return new WalletController(
            (new \ReflectionClass(WalletService::class))->newInstanceWithoutConstructor(),
            (new \ReflectionClass(FraudControlService::class))->newInstanceWithoutConstructor(),
            (new \ReflectionClass(AuditService::class))->newInstanceWithoutConstructor(),
            $this->createMock(DatabaseManager::class),
            $this->createMock(LoggerInterface::class),
            $this->createMock(ResponseFactory::class),
        );
    }
}
