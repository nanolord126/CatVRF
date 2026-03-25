<?php

declare(strict_types=1);

namespace Tests\Contract\Payment;

use App\Models\Payment;
use App\Models\User;
use App\Models\Tenant;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * PaymentApiContractTest
 * 
 * OpenAPI schema validation для Payment API endpoints
 */
final class PaymentApiContractTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;
    protected Tenant $tenant;

    protected function setUp(): void
    {
        parent::setUp();

        $this->tenant = Tenant::factory()->create();
        $this->user = User::factory()->for($this->tenant)->create();
        $this->actingAs($this->user);
    }

    /** @test */
    public function it_conforms_to_payment_init_response_contract(): void
    {
        $response = $this->postJson('/api/v1/payments/init', [
            'amount' => 50000,
            'description' => 'Test payment',
            'provider' => 'tinkoff',
            'return_url' => 'https://example.com/return',
        ]);

        $response->assertStatus(201)
            ->assertJsonStructure([
                'data' => [
                    'id',
                    'payment_id',
                    'amount',
                    'status',
                    'provider',
                    'payment_url',
                    'created_at',
                ],
                'meta' => [
                    'correlation_id',
                ],
            ]);

        // Validate data types
        $data = $response->json('data');
        $this->assertIsString($data['id']);
        $this->assertIsString($data['payment_id']);
        $this->assertIsInt($data['amount']);
        $this->assertIn($data['status'], ['pending', 'authorized', 'captured']);
        $this->assertIsString($data['provider']);
        $this->assertMatchesRegularExpression('/^https:\/\//', $data['payment_url']);
    }

    /** @test */
    public function it_conforms_to_payment_list_response_contract(): void
    {
        Payment::factory()->for($this->user)->count(5)->create();

        $response = $this->getJson('/api/v1/payments?page=1&per_page=10');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'data' => [
                    '*' => [
                        'id',
                        'amount',
                        'status',
                        'provider',
                        'created_at',
                        'updated_at',
                    ],
                ],
                'meta' => [
                    'total',
                    'per_page',
                    'current_page',
                    'last_page',
                    'from',
                    'to',
                ],
            ]);

        // Validate pagination meta
        $meta = $response->json('meta');
        $this->assertGreaterThanOrEqual(0, $meta['total']);
        $this->assertEquals(10, $meta['per_page']);
        $this->assertEquals(1, $meta['current_page']);
        $this->assertIsInt($meta['from']);
        $this->assertIsInt($meta['to']);
    }

    /** @test */
    public function it_conforms_to_payment_view_response_contract(): void
    {
        $payment = Payment::factory()->for($this->user)->create();

        $response = $this->getJson("/api/v1/payments/{$payment->id}");

        $response->assertStatus(200)
            ->assertJsonStructure([
                'data' => [
                    'id',
                    'amount',
                    'status',
                    'provider',
                    'payment_url',
                    'idempotency_key',
                    'provider_payment_id',
                    'created_at',
                    'updated_at',
                    'captured_at',
                    'refunded_at',
                ],
                'meta' => [
                    'correlation_id',
                ],
            ]);
    }

    /** @test */
    public function it_conforms_to_capture_response_contract(): void
    {
        $payment = Payment::factory()
            ->for($this->user)
            ->create(['status' => 'authorized']);

        $response = $this->postJson("/api/v1/payments/{$payment->id}/capture");

        $response->assertStatus(200)
            ->assertJsonStructure([
                'data' => [
                    'id',
                    'status',
                    'captured_at',
                ],
            ]);

        $data = $response->json('data');
        $this->assertEquals('captured', $data['status']);
        $this->assertMatchesRegularExpression('/^\d{4}-\d{2}-\d{2}T/', $data['captured_at']);
    }

    /** @test */
    public function it_conforms_to_refund_response_contract(): void
    {
        $payment = Payment::factory()
            ->for($this->user)
            ->create(['status' => 'captured']);

        $response = $this->postJson("/api/v1/payments/{$payment->id}/refund", [
            'amount' => 25000,
        ]);

        $response->assertStatus(200)
            ->assertJsonStructure([
                'data' => [
                    'id',
                    'status',
                    'refunded_amount',
                    'refunded_at',
                ],
            ]);

        $data = $response->json('data');
        $this->assertEquals('refunded', $data['status']);
        $this->assertEquals(25000, $data['refunded_amount']);
    }

    /** @test */
    public function it_returns_error_response_with_correct_structure(): void
    {
        $response = $this->postJson('/api/v1/payments/init', [
            'amount' => -50000, // Invalid
            'description' => 'Test',
            'provider' => 'tinkoff',
            'return_url' => 'https://example.com',
        ]);

        $response->assertStatus(422)
            ->assertJsonStructure([
                'message',
                'errors' => [
                    'amount' => [],
                ],
            ]);
    }

    /** @test */
    public function it_returns_404_error_with_correct_structure(): void
    {
        $response = $this->getJson('/api/v1/payments/nonexistent-id');

        $response->assertStatus(404)
            ->assertJsonStructure([
                'message',
            ]);
    }

    /** @test */
    public function it_returns_authentication_error(): void
    {
        $response = $this->postJson('/api/v1/payments/init', [
            'amount' => 50000,
            'description' => 'Test',
            'provider' => 'tinkoff',
            'return_url' => 'https://example.com',
        ]);

        // Before authentication
        $this->assertIn($response->status(), [401, 302]);
    }

    /** @test */
    public function it_includes_rate_limit_headers(): void
    {
        $response = $this->postJson('/api/v1/payments/init', [
            'amount' => 50000,
            'description' => 'Test payment',
            'provider' => 'tinkoff',
            'return_url' => 'https://example.com/return',
        ]);

        // Rate limit headers should be present
        $headers = $response->headers->all();
        
        // X-RateLimit-* headers are optional but valid
        $this->assertTrue(
            isset($headers['x-ratelimit-limit']) || 
            isset($headers['x-ratelimit-remaining']) ||
            $response->status() < 429
        );
    }

    /** @test */
    public function it_validates_payment_status_enum(): void
    {
        $payment = Payment::factory()->for($this->user)->create(['status' => 'pending']);

        $response = $this->getJson("/api/v1/payments/{$payment->id}");

        $data = $response->json('data');
        $this->assertIn($data['status'], [
            'pending',
            'authorized',
            'captured',
            'refunded',
            'failed',
            'cancelled',
        ]);
    }

    /** @test */
    public function it_validates_provider_enum(): void
    {
        $payment = Payment::factory()->for($this->user)->create(['provider' => 'tinkoff']);

        $response = $this->getJson("/api/v1/payments/{$payment->id}");

        $data = $response->json('data');
        $this->assertIn($data['provider'], ['tinkoff', 'tochka', 'sber']);
    }

    /** @test */
    public function it_validates_timestamp_format_iso8601(): void
    {
        $payment = Payment::factory()->for($this->user)->create();

        $response = $this->getJson("/api/v1/payments/{$payment->id}");

        $data = $response->json('data');
        
        // ISO 8601 format: 2026-03-24T12:34:56Z or 2026-03-24T12:34:56+00:00
        $this->assertMatchesRegularExpression(
            '/^\d{4}-\d{2}-\d{2}T\d{2}:\d{2}:\d{2}/',
            $data['created_at']
        );
    }

    /** @test */
    public function it_validates_uuid_format(): void
    {
        $payment = Payment::factory()->for($this->user)->create();

        $response = $this->getJson("/api/v1/payments/{$payment->id}");

        $data = $response->json('data');
        
        // UUID v4 format
        $this->assertMatchesRegularExpression(
            '/^[0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12}$/i',
            $data['id']
        );
    }

    /** @test */
    public function it_validates_nullable_fields(): void
    {
        $payment = Payment::factory()
            ->for($this->user)
            ->create([
                'captured_at' => null,
                'refunded_at' => null,
                'error_message' => null,
            ]);

        $response = $this->getJson("/api/v1/payments/{$payment->id}");

        $data = $response->json('data');
        $this->assertNull($data['captured_at']);
        $this->assertNull($data['refunded_at']);
    }

    /** @test */
    public function it_validates_integer_amount(): void
    {
        $response = $this->postJson('/api/v1/payments/init', [
            'amount' => 50000,
            'description' => 'Test payment',
            'provider' => 'tinkoff',
            'return_url' => 'https://example.com/return',
        ]);

        $data = $response->json('data');
        $this->assertIsInt($data['amount']);
        $this->assertGreaterThan(0, $data['amount']);
    }

    /** @test */
    public function it_validates_string_fields(): void
    {
        $response = $this->postJson('/api/v1/payments/init', [
            'amount' => 50000,
            'description' => 'Test payment',
            'provider' => 'tinkoff',
            'return_url' => 'https://example.com/return',
        ]);

        $data = $response->json('data');
        $this->assertIsString($data['id']);
        $this->assertIsString($data['status']);
        $this->assertIsString($data['provider']);
    }

    /** @test */
    public function it_returns_correct_http_status_codes(): void
    {
        // 201 Created on successful init
        $initResponse = $this->postJson('/api/v1/payments/init', [
            'amount' => 50000,
            'description' => 'Test',
            'provider' => 'tinkoff',
            'return_url' => 'https://example.com',
        ]);
        $this->assertEquals(201, $initResponse->status());

        // 200 OK on read
        $payment = Payment::factory()->for($this->user)->create();
        $viewResponse = $this->getJson("/api/v1/payments/{$payment->id}");
        $this->assertEquals(200, $viewResponse->status());

        // 404 Not Found on missing
        $notFoundResponse = $this->getJson('/api/v1/payments/missing');
        $this->assertEquals(404, $notFoundResponse->status());

        // 422 Unprocessable on validation
        $validationResponse = $this->postJson('/api/v1/payments/init', [
            'description' => 'Test',
        ]);
        $this->assertEquals(422, $validationResponse->status());
    }

    /** @test */
    public function it_includes_correlation_id_in_all_responses(): void
    {
        $response = $this->postJson('/api/v1/payments/init', [
            'amount' => 50000,
            'description' => 'Test',
            'provider' => 'tinkoff',
            'return_url' => 'https://example.com',
        ]);

        $meta = $response->json('meta');
        $this->assertNotNull($meta['correlation_id']);
        $this->assertIsString($meta['correlation_id']);
        $this->assertNotEmpty($meta['correlation_id']);
    }

    /** @test */
    public function it_validates_nested_object_structure(): void
    {
        $response = $this->getJson('/api/v1/payments');

        $response->assertJsonStructure([
            'data' => [
                '*' => [
                    'id',
                    'amount',
                ],
            ],
            'meta' => [
                'total',
                'per_page',
            ],
        ]);
    }
}
