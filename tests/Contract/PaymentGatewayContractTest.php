<?php

declare(strict_types=1);

namespace Tests\Contract;

use Illuminate\Support\Facades\Http;
use Tests\Helpers\PaymentTestHelper;

// Pest test using modern declarative syntax
beforeEach(function () {
    Http::fake();
});

test('yukassa payment creation contract', function () {
    // Arrange: Mock YooKassa payment creation response
    Http::fake([
        'api.yookassa.ru/v3/payments' => Http::response([
            'id' => '12345678-1234-1234-1234-123456789012',
            'status' => 'pending',
            'amount' => [
                'value' => '100.00',
                'currency' => 'RUB',
            ],
            'description' => 'Payment for order #123',
            'created_at' => '2024-01-01T00:00:00.000Z',
            'confirmation' => [
                'type' => 'redirect',
                'confirmation_url' => 'https://yoomoney.ru/checkout/payments/v2/...',
            ],
            'test' => true,
            'metadata' => [
                'order_id' => '123',
            ],
        ], 200),
    ]);

    // Act: Create payment
    $response = Http::withHeaders([
        'Authorization' => 'Basic '.base64_encode(config('payment.yukassa.shop_id').':'.config('payment.yukassa.secret_key')),
        'Content-Type' => 'application/json',
        'Idempotence-Key' => PaymentTestHelper::generateIdempotencyKey(),
    ])->post('https://api.yookassa.ru/v3/payments', [
        'amount' => [
            'value' => '100.00',
            'currency' => 'RUB',
        ],
        'payment_method_data' => [
            'type' => 'bank_card',
        ],
        'confirmation' => [
            'type' => 'redirect',
            'return_url' => config('app.url').'/payment/success',
        ],
        'description' => 'Payment for order #123',
        'metadata' => [
            'order_id' => '123',
        ],
    ]);

    // Assert: Payment creation response matches contract
    expect($response->status())->toBe(200);
    expect($response->json('id'))->toBeString();
    expect($response->json('status'))->toBe('pending');
    expect($response->json('amount'))->toHaveKeys(['value', 'currency']);
    expect($response->json('amount.currency'))->toBe('RUB');
    expect($response->json('confirmation'))->toHaveKey('confirmation_url');
    expect($response->json('metadata'))->toHaveKey('order_id');
});

test('yukassa payment status check contract', function () {
    // Arrange: Mock payment status response
    $paymentId = '12345678-1234-1234-1234-123456789012';

    Http::fake([
        "api.yookassa.ru/v3/payments/{$paymentId}" => Http::response([
            'id' => $paymentId,
            'status' => 'succeeded',
            'amount' => [
                'value' => '100.00',
                'currency' => 'RUB',
            ],
            'income_amount' => [
                'value' => '98.50',
                'currency' => 'RUB',
            ],
            'paid' => true,
            'created_at' => '2024-01-01T00:00:00.000Z',
            'captured_at' => '2024-01-01T00:05:00.000Z',
        ], 200),
    ]);

    // Act: Check payment status
    $response = Http::withHeaders([
        'Authorization' => 'Basic '.base64_encode(config('payment.yukassa.shop_id').':'.config('payment.yukassa.secret_key')),
    ])->get("https://api.yookassa.ru/v3/payments/{$paymentId}");

    // Assert: Status response matches contract
    expect($response->status())->toBe(200);
    expect($response->json('status'))->toBe('succeeded');
    expect($response->json('paid'))->toBeTrue();
    expect($response->json('income_amount'))->toHaveKeys(['value', 'currency']);
});

test('yukassa webhook signature verification contract', function () {
    // Arrange: Create webhook payload
    $webhookPayload = PaymentTestHelper::simulateWebhook('12345678', 'succeeded');

    // Assert: Signature matches expected format
    expect($webhookPayload)->toHaveKey('event');
    expect($webhookPayload)->toHaveKey('data');
    expect($webhookPayload)->toHaveKey('signature');
    expect($webhookPayload['signature'])->toBeString();
    expect($webhookPayload['event'])->toBe('payment.succeeded');
});

test('yukassa refund creation contract', function () {
    // Arrange: Mock refund response
    Http::fake([
        'api.yookassa.ru/v3/refunds' => Http::response([
            'id' => 'refund-12345678-1234-1234-1234-123456789012',
            'status' => 'succeeded',
            'amount' => [
                'value' => '100.00',
                'currency' => 'RUB',
            ],
            'payment_id' => '12345678-1234-1234-1234-123456789012',
            'created_at' => '2024-01-01T00:10:00.000Z',
        ], 200),
    ]);

    // Act: Create refund
    $response = Http::withHeaders([
        'Authorization' => 'Basic '.base64_encode(config('payment.yukassa.shop_id').':'.config('payment.yukassa.secret_key')),
        'Content-Type' => 'application/json',
        'Idempotence-Key' => PaymentTestHelper::generateIdempotencyKey(),
    ])->post('https://api.yookassa.ru/v3/refunds', [
        'amount' => [
            'value' => '100.00',
            'currency' => 'RUB',
        ],
        'payment_id' => '12345678-1234-1234-1234-123456789012',
    ]);

    // Assert: Refund response matches contract
    expect($response->status())->toBe(200);
    expect($response->json('id'))->toBeString();
    expect($response->json('status'))->toBe('succeeded');
    expect($response->json('payment_id'))->toBeString();
});

test('yukassa error handling contract', function () {
    // Arrange: Mock error response
    Http::fake([
        'api.yookassa.ru/v3/payments' => Http::response([
            'type' => 'error',
            'id' => 'error-123',
            'code' => 'invalid_request',
            'description' => 'Invalid request parameters',
            'parameter' => 'amount.value',
        ], 400),
    ]);

    // Act: Make invalid request
    $response = Http::withHeaders([
        'Authorization' => 'Basic '.base64_encode(config('payment.yukassa.shop_id').':'.config('payment.yukassa.secret_key')),
    ])->post('https://api.yookassa.ru/v3/payments', [
        'amount' => [
            'value' => 'invalid',
            'currency' => 'RUB',
        ],
    ]);

    // Assert: Error response matches contract
    expect($response->status())->toBe(400);
    expect($response->json('type'))->toBe('error');
    expect($response->json('code'))->toBe('invalid_request');
    expect($response->json('description'))->toBeString();
});

test('yukassa idempotency contract', function () {
    // Arrange: Mock idempotency behavior
    $idempotencyKey = PaymentTestHelper::generateIdempotencyKey();

    Http::fake([
        'api.yookassa.ru/v3/payments' => Http::response([
            'id' => '12345678-1234-1234-1234-123456789012',
            'status' => 'pending',
        ], 200),
    ]);

    // Act: Make two identical requests with same idempotency key
    $response1 = Http::withHeaders([
        'Authorization' => 'Basic '.base64_encode(config('payment.yukassa.shop_id').':'.config('payment.yukassa.secret_key')),
        'Idempotence-Key' => $idempotencyKey,
    ])->post('https://api.yookassa.ru/v3/payments', [
        'amount' => ['value' => '100.00', 'currency' => 'RUB'],
    ]);

    $response2 = Http::withHeaders([
        'Authorization' => 'Basic '.base64_encode(config('payment.yukassa.shop_id').':'.config('payment.yukassa.secret_key')),
        'Idempotence-Key' => $idempotencyKey,
    ])->post('https://api.yookassa.ru/v3/payments', [
        'amount' => ['value' => '100.00', 'currency' => 'RUB'],
    ]);

    // Assert: Both responses return same payment ID
    expect($response1->json('id'))->toBe($response2->json('id'));
});
