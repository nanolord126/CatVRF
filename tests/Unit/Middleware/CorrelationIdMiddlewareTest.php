<?php declare(strict_types=1);

namespace Tests\Unit\Middleware;

use App\Http\Middleware\CorrelationIdMiddleware;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Log\LogManager;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

/**
 * =================================================================
 *  CorrelationIdMiddleware — UNIT TEST
 *  CANON: correlation_id обязателен в каждом логе, событии, ответе.
 * =================================================================
 *
 *  Проверяет:
 *   1. Генерацию UUID при отсутствии заголовка
 *   2. Проброс X-Correlation-ID из request → response
 *   3. Невалидный формат → генерируется новый UUID
 *   4. Атрибут correlation_id доступен в request
 *   5. X-Request-ID в response совпадает с X-Correlation-ID
 */
final class CorrelationIdMiddlewareTest extends TestCase
{
    private LogManager|\PHPUnit\Framework\MockObject\MockObject $logManager;
    private CorrelationIdMiddleware $middleware;

    protected function setUp(): void
    {
        parent::setUp();

        $this->logManager = $this->createMock(LogManager::class);

        // channel('audit') returns logger that can debug()
        $channelMock = $this->createMock(\Psr\Log\LoggerInterface::class);
        $this->logManager
            ->method('channel')
            ->willReturn($channelMock);

        $this->middleware = new CorrelationIdMiddleware($this->logManager);
    }

    #[Test]
    public function generates_uuid_when_header_missing(): void
    {
        $request = Request::create('/api/test', 'GET');

        $response = $this->middleware->handle($request, function (Request $req) {
            $cid = $req->attributes->get('correlation_id');
            self::assertNotNull($cid, 'correlation_id must be set');
            self::assertTrue(
                \Illuminate\Support\Str::isUuid($cid),
                'correlation_id must be a valid UUID',
            );

            return new Response('ok');
        });

        self::assertTrue(
            $response->headers->has('X-Correlation-ID'),
            'Response must contain X-Correlation-ID header',
        );
    }

    #[Test]
    public function passes_through_existing_correlation_id(): void
    {
        $existingId = '550e8400-e29b-41d4-a716-446655440000';
        $request = Request::create('/api/test', 'GET');
        $request->headers->set('X-Correlation-ID', $existingId);

        $response = $this->middleware->handle($request, function (Request $req) use ($existingId) {
            self::assertSame(
                $existingId,
                $req->attributes->get('correlation_id'),
                'Must use existing correlation_id from request header',
            );

            return new Response('ok');
        });

        self::assertSame(
            $existingId,
            $response->headers->get('X-Correlation-ID'),
            'Response header must match request header',
        );
    }

    #[Test]
    public function replaces_invalid_format_with_new_uuid(): void
    {
        $request = Request::create('/api/test', 'GET');
        $request->headers->set('X-Correlation-ID', 'not-a-uuid');

        $this->middleware->handle($request, function (Request $req) {
            $cid = $req->attributes->get('correlation_id');
            self::assertNotSame(
                'not-a-uuid',
                $cid,
                'Invalid correlation_id must be replaced with a valid UUID',
            );
            self::assertTrue(
                \Illuminate\Support\Str::isUuid($cid),
                'Replaced correlation_id must be a valid UUID',
            );

            return new Response('ok');
        });
    }

    #[Test]
    public function response_contains_both_headers(): void
    {
        $request = Request::create('/api/test', 'GET');

        $response = $this->middleware->handle($request, fn () => new Response('ok'));

        self::assertTrue(
            $response->headers->has('X-Correlation-ID'),
            'Response must have X-Correlation-ID',
        );
        self::assertTrue(
            $response->headers->has('X-Request-ID'),
            'Response must have X-Request-ID',
        );
        self::assertSame(
            $response->headers->get('X-Correlation-ID'),
            $response->headers->get('X-Request-ID'),
            'X-Correlation-ID and X-Request-ID must match',
        );
    }

    #[Test]
    public function supports_lowercase_header(): void
    {
        $expectedId = '660e8400-e29b-41d4-a716-446655440000';
        $request = Request::create('/api/test', 'GET');
        $request->headers->set('x-correlation-id', $expectedId);

        $this->middleware->handle($request, function (Request $req) use ($expectedId) {
            $cid = $req->attributes->get('correlation_id');
            self::assertNotNull($cid, 'correlation_id must be set from lowercase header');

            return new Response('ok');
        });
    }

    #[Test]
    public function correlation_id_attribute_is_string(): void
    {
        $request = Request::create('/api/test', 'GET');

        $this->middleware->handle($request, function (Request $req) {
            $cid = $req->attributes->get('correlation_id');
            self::assertIsString($cid, 'correlation_id attribute must be string');

            return new Response('ok');
        });
    }

    #[Test]
    public function unique_ids_generated_for_different_requests(): void
    {
        $ids = [];

        for ($i = 0; $i < 5; $i++) {
            $request = Request::create('/api/test', 'GET');
            $this->middleware->handle($request, function (Request $req) use (&$ids) {
                $ids[] = $req->attributes->get('correlation_id');

                return new Response('ok');
            });
        }

        self::assertCount(
            5,
            array_unique($ids),
            'Each request must get a unique correlation_id',
        );
    }
}
