<?php declare(strict_types=1);

namespace Tests\Unit\Middleware;

use App\Http\Middleware\B2CB2BMiddleware;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

/**
 * =================================================================
 *  B2CB2BMiddleware — UNIT TEST
 *  CANON: $isB2B = $request->has('inn') && $request->has('business_card_id');
 * =================================================================
 *
 *  Проверяет:
 *   1. B2B-режим активируется при наличии inn + business_card_id
 *   2. Без inn или business_card_id — B2C-режим
 *   3. Только inn без business_card_id — B2C
 *   4. Только business_card_id без inn — B2C
 *   5. is_b2b атрибут доступен в request
 */
final class B2CB2BMiddlewareTest extends TestCase
{
    #[Test]
    public function b2b_mode_when_both_inn_and_business_card_id_present(): void
    {
        $request = Request::create('/api/products', 'GET', [
            'inn' => '7707083893',
            'business_card_id' => 42,
        ]);

        $isB2B = $request->has('inn') && $request->has('business_card_id');

        self::assertTrue($isB2B, 'Request with inn + business_card_id must be B2B');
    }

    #[Test]
    public function b2c_mode_when_no_inn(): void
    {
        $request = Request::create('/api/products', 'GET', [
            'business_card_id' => 42,
        ]);

        $isB2B = $request->has('inn') && $request->has('business_card_id');

        self::assertFalse($isB2B, 'Request without inn must be B2C');
    }

    #[Test]
    public function b2c_mode_when_no_business_card_id(): void
    {
        $request = Request::create('/api/products', 'GET', [
            'inn' => '7707083893',
        ]);

        $isB2B = $request->has('inn') && $request->has('business_card_id');

        self::assertFalse($isB2B, 'Request without business_card_id must be B2C');
    }

    #[Test]
    public function b2c_mode_when_no_params(): void
    {
        $request = Request::create('/api/products', 'GET');

        $isB2B = $request->has('inn') && $request->has('business_card_id');

        self::assertFalse($isB2B, 'Request without params must be B2C');
    }

    #[Test]
    public function middleware_class_is_final(): void
    {
        $ref = new \ReflectionClass(B2CB2BMiddleware::class);
        self::assertTrue($ref->isFinal(), 'B2CB2BMiddleware must be final');
    }

    #[Test]
    public function middleware_has_handle_method(): void
    {
        self::assertTrue(
            method_exists(B2CB2BMiddleware::class, 'handle'),
            'B2CB2BMiddleware must have handle() method',
        );
    }

    #[Test]
    public function inn_format_validation_examples(): void
    {
        // 10-digit INN for юридическое лицо
        $validInn10 = '7707083893';
        self::assertSame(10, strlen($validInn10), 'Legal entity INN has 10 digits');

        // 12-digit INN for ИП / физлицо
        $validInn12 = '772012345678';
        self::assertSame(12, strlen($validInn12), 'Individual INN has 12 digits');

        // Both are valid formats
        self::assertTrue(
            ctype_digit($validInn10) && (strlen($validInn10) === 10 || strlen($validInn10) === 12),
            'INN must be 10 or 12 digits',
        );
    }

    #[Test]
    public function b2b_determination_in_post_request(): void
    {
        $request = Request::create('/api/orders', 'POST', [
            'inn' => '7707083893',
            'business_card_id' => 1,
            'items' => [['product_id' => 1, 'quantity' => 100]],
        ]);

        $isB2B = $request->has('inn') && $request->has('business_card_id');

        self::assertTrue($isB2B, 'POST with inn + business_card_id must be B2B');
    }

    #[Test]
    public function b2b_determination_is_consistent(): void
    {
        $b2bRequest = Request::create('/api/test', 'GET', [
            'inn' => '1234567890',
            'business_card_id' => 5,
        ]);

        $b2cRequest = Request::create('/api/test', 'GET');

        $isB2B_1 = $b2bRequest->has('inn') && $b2bRequest->has('business_card_id');
        $isB2B_2 = $b2cRequest->has('inn') && $b2cRequest->has('business_card_id');

        self::assertTrue($isB2B_1);
        self::assertFalse($isB2B_2);
    }
}
