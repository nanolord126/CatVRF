<?php

declare(strict_types=1);

namespace Tests\Contract;

use Illuminate\Support\Facades\Http;
use Tests\Helpers\MedicalTestHelper;

// Pest test using modern declarative syntax
beforeEach(function () {
    Http::fake();
});

test('openai chat completion contract', function () {
    // Arrange: Mock OpenAI API response
    Http::fake([
        'api.openai.com/v1/chat/completions' => Http::response([
            'id' => 'chatcmpl-123',
            'object' => 'chat.completion',
            'created' => 1677652288,
            'model' => 'gpt-4',
            'choices' => [
                [
                    'index' => 0,
                    'message' => [
                        'role' => 'assistant',
                        'content' => 'Anonymized response',
                    ],
                    'finish_reason' => 'stop',
                ],
            ],
            'usage' => [
                'prompt_tokens' => 10,
                'completion_tokens' => 5,
                'total_tokens' => 15,
            ],
        ], 200),
    ]);

    // Act: Make request to OpenAI
    $response = Http::withHeaders([
        'Authorization' => 'Bearer '.config('services.openai.api_key'),
        'Content-Type' => 'application/json',
    ])->post('https://api.openai.com/v1/chat/completions', [
        'model' => 'gpt-4',
        'messages' => [
            ['role' => 'user', 'content' => 'Anonymized medical query'],
        ],
        'temperature' => 0.7,
    ]);

    // Assert: Response matches contract
    expect($response->status())->toBe(200);
    expect($response->json('id'))->toBeString();
    expect($response->json('object'))->toBe('chat.completion');
    expect($response->json('choices'))->toBeArray();
    expect($response->json('choices.0.message.role'))->toBe('assistant');
    expect($response->json('choices.0.message.content'))->toBeString();
    expect($response->json('usage'))->toHaveKeys(['prompt_tokens', 'completion_tokens', 'total_tokens']);
});

test('openai embedding contract', function () {
    // Arrange: Mock OpenAI embedding response
    Http::fake([
        'api.openai.com/v1/embeddings' => Http::response([
            'object' => 'list',
            'data' => [
                [
                    'object' => 'embedding',
                    'embedding' => array_fill(0, 3072, 0.1),
                    'index' => 0,
                ],
            ],
            'model' => 'text-embedding-3-large',
            'usage' => [
                'prompt_tokens' => 5,
                'total_tokens' => 5,
            ],
        ], 200),
    ]);

    // Act: Create embedding
    $response = Http::withHeaders([
        'Authorization' => 'Bearer '.config('services.openai.api_key'),
        'Content-Type' => 'application/json',
    ])->post('https://api.openai.com/v1/embeddings', [
        'model' => 'text-embedding-3-large',
        'input' => 'Anonymized text',
    ]);

    // Assert: Embedding response matches contract
    expect($response->status())->toBe(200);
    expect($response->json('object'))->toBe('list');
    expect($response->json('data'))->toBeArray();
    expect($response->json('data.0.object'))->toBe('embedding');
    expect($response->json('data.0.embedding'))->toBeArray();
    expect(count($response->json('data.0.embedding')))->toBe(3072);
});

test('openai error handling contract', function () {
    // Arrange: Mock error response
    Http::fake([
        'api.openai.com/v1/chat/completions' => Http::response([
            'error' => [
                'message' => 'Invalid API key',
                'type' => 'invalid_request_error',
                'param' => null,
                'code' => 'invalid_api_key',
            ],
        ], 401),
    ]);

    // Act: Make request with invalid key
    $response = Http::withHeaders([
        'Authorization' => 'Bearer invalid_key',
        'Content-Type' => 'application/json',
    ])->post('https://api.openai.com/v1/chat/completions', [
        'model' => 'gpt-4',
        'messages' => [['role' => 'user', 'content' => 'test']],
    ]);

    // Assert: Error response matches contract
    expect($response->status())->toBe(401);
    expect($response->json('error'))->toHaveKeys(['message', 'type', 'code']);
    expect($response->json('error.type'))->toBe('invalid_request_error');
});

test('openai rate limit handling', function () {
    // Arrange: Mock rate limit response
    Http::fake([
        'api.openai.com/v1/chat/completions' => Http::response([
            'error' => [
                'message' => 'Rate limit exceeded',
                'type' => 'rate_limit_error',
                'code' => 'rate_limit_exceeded',
            ],
        ], 429, [
            'Retry-After' => '60',
        ]),
    ]);

    // Act: Make request that hits rate limit
    $response = Http::withHeaders([
        'Authorization' => 'Bearer '.config('services.openai.api_key'),
        'Content-Type' => 'application/json',
    ])->post('https://api.openai.com/v1/chat/completions', [
        'model' => 'gpt-4',
        'messages' => [['role' => 'user', 'content' => 'test']],
    ]);

    // Assert: Rate limit response matches contract
    expect($response->status())->toBe(429);
    expect($response->header('Retry-After'))->not->toBeNull();
    expect($response->json('error.type'))->toBe('rate_limit_error');
});

test('openai request contains no PII', function () {
    // Arrange: Track outgoing requests
    Http::fake(function ($request) {
        // Assert request body contains no PII
        $body = json_decode($request->body(), true);
        MedicalTestHelper::assertNoMedicalDataInExternalCalls($body);

        return Http::response([
            'id' => 'chatcmpl-123',
            'choices' => [
                ['message' => ['content' => 'Response']],
            ],
        ], 200);
    });

    // Act: Make request with anonymized data
    Http::withHeaders([
        'Authorization' => 'Bearer '.config('services.openai.api_key'),
    ])->post('https://api.openai.com/v1/chat/completions', [
        'model' => 'gpt-4',
        'messages' => [
            ['role' => 'user', 'content' => 'Anonymized symptom: headache'],
        ],
    ]);
});
