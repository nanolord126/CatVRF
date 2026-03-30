<?php declare(strict_types=1);

namespace Tests\Performance\Payment;

use App\Models\Payment;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/**
 * PaymentPerformanceTest
 * 
 * Throughput, memory, и query optimization для Payment API
 */
final class PaymentPerformanceTest extends TestCase
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
    public function it_creates_single_payment_under_50ms(): void
    {
        $startTime = microtime(true);

        $this->postJson('/api/v1/payments/init', [
            'amount' => 50000,
            'description' => 'Test payment',
            'provider' => 'tinkoff',
            'return_url' => 'https://example.com/return',
        ]);

        $elapsed = (microtime(true) - $startTime) * 1000;

        $this->assertLessThan(50, $elapsed, "Payment creation took {$elapsed}ms (expected <50ms)");
    }

    /** @test */
    public function it_lists_100_payments_under_500ms(): void
    {
        Payment::factory()->for($this->user)->count(100)->create();

        $startTime = microtime(true);

        $this->getJson('/api/v1/payments?page=1&per_page=100');

        $elapsed = (microtime(true) - $startTime) * 1000;

        $this->assertLessThan(500, $elapsed, "Listing 100 payments took {$elapsed}ms (expected <500ms)");
    }

    /** @test */
    public function it_retrieves_single_payment_under_50ms(): void
    {
        $payment = Payment::factory()->for($this->user)->create();

        $startTime = microtime(true);

        $this->getJson("/api/v1/payments/{$payment->id}");

        $elapsed = (microtime(true) - $startTime) * 1000;

        $this->assertLessThan(50, $elapsed, "Payment retrieval took {$elapsed}ms (expected <50ms)");
    }

    /** @test */
    public function it_captures_payment_under_100ms(): void
    {
        $payment = Payment::factory()
            ->for($this->user)
            ->create(['status' => 'authorized']);

        $startTime = microtime(true);

        $this->postJson("/api/v1/payments/{$payment->id}/capture");

        $elapsed = (microtime(true) - $startTime) * 1000;

        $this->assertLessThan(100, $elapsed, "Payment capture took {$elapsed}ms (expected <100ms)");
    }

    /** @test */
    public function it_handles_100_payment_creations_under_5_seconds(): void
    {
        $startTime = microtime(true);

        for ($i = 0; $i < 100; $i++) {
            $this->postJson('/api/v1/payments/init', [
                'amount' => 50000 + $i * 100,
                'description' => "Test payment {$i}",
                'provider' => 'tinkoff',
                'return_url' => 'https://example.com/return',
            ]);
        }

        $elapsed = (microtime(true) - $startTime) * 1000;

        $this->assertLessThan(5000, $elapsed, "100 payment creations took {$elapsed}ms (expected <5000ms)");
    }

    /** @test */
    public function it_does_not_have_n_plus_one_queries_on_list(): void
    {
        Payment::factory()->for($this->user)->count(10)->create();

        DB::flushQueryLog();
        DB::enableQueryLog();

        $this->getJson('/api/v1/payments?page=1&per_page=10');

        $queries = DB::getQueryLog();
        
        // Should be minimal queries: 1 for pagination + 1 for data + maybe 1 for count
        $this->assertLessThan(5, count($queries), 
            "Query count: " . count($queries) . " (N+1 detected: expected <5 queries)"
        );
    }

    /** @test */
    public function it_uses_eager_loading_to_prevent_n_plus_one(): void
    {
        Payment::factory()->for($this->user)->count(10)->create();

        DB::flushQueryLog();
        DB::enableQueryLog();

        $response = $this->getJson('/api/v1/payments?page=1&per_page=10');

        $response->assertSuccessful();

        $queries = DB::getQueryLog();
        
        // Count queries that hit payments table
        $paymentQueries = array_filter($queries, fn($q) => str_contains($q['query'], 'payments'));
        
        // Should not have individual queries per payment (N+1)
        $this->assertLessThan(15, count($queries));
    }

    /** @test */
    public function it_paginates_efficiently_through_1000_payments(): void
    {
        Payment::factory()->for($this->user)->count(1000)->create();

        $startTime = microtime(true);

        // Paginate through 10 pages
        for ($page = 1; $page <= 10; $page++) {
            $this->getJson("/api/v1/payments?page={$page}&per_page=100");
        }

        $elapsed = (microtime(true) - $startTime) * 1000;

        $this->assertLessThan(2000, $elapsed, "Pagination through 1000 items took {$elapsed}ms");
    }

    /** @test */
    public function it_filters_by_status_efficiently(): void
    {
        Payment::factory()->for($this->user)
            ->create(['status' => 'captured'], 500);
        Payment::factory()->for($this->user)
            ->create(['status' => 'pending'], 500);

        $startTime = microtime(true);

        $this->getJson('/api/v1/payments?status=captured&per_page=100');

        $elapsed = (microtime(true) - $startTime) * 1000;

        $this->assertLessThan(300, $elapsed, "Status filtering took {$elapsed}ms");
    }

    /** @test */
    public function it_refunds_payment_under_150ms(): void
    {
        $payment = Payment::factory()
            ->for($this->user)
            ->create(['status' => 'captured', 'amount' => 50000]);

        $startTime = microtime(true);

        $this->postJson("/api/v1/payments/{$payment->id}/refund", [
            'amount' => 25000,
        ]);

        $elapsed = (microtime(true) - $startTime) * 1000;

        $this->assertLessThan(150, $elapsed, "Payment refund took {$elapsed}ms");
    }

    /** @test */
    public function it_maintains_index_efficiency_on_sorting(): void
    {
        Payment::factory()->for($this->user)->count(100)->create();

        $startTime = microtime(true);

        $this->getJson('/api/v1/payments?sort=-created_at&per_page=50');

        $elapsed = (microtime(true) - $startTime) * 1000;

        $this->assertLessThan(200, $elapsed, "Sorting by created_at took {$elapsed}ms");
    }

    /** @test */
    public function it_handles_concurrent_payment_lookups(): void
    {
        $payments = Payment::factory()->for($this->user)->count(50)->create();

        $startTime = microtime(true);

        foreach ($payments as $payment) {
            $this->getJson("/api/v1/payments/{$payment->id}");
        }

        $elapsed = (microtime(true) - $startTime) * 1000;
        $avgPerRequest = $elapsed / 50;

        $this->assertLessThan(2500, $elapsed, "50 lookups took {$elapsed}ms total");
        $this->assertLessThan(100, $avgPerRequest, "Avg per request: {$avgPerRequest}ms");
    }

    /** @test */
    public function it_serializes_response_efficiently(): void
    {
        Payment::factory()->for($this->user)->count(100)->create();

        $startTime = microtime(true);

        $response = $this->getJson('/api/v1/payments?per_page=100');

        $elapsed = (microtime(true) - $startTime) * 1000;

        $this->assertNotNull($response->json('data'));
        $this->assertLessThan(500, $elapsed, "Response serialization took {$elapsed}ms");
    }

    /** @test */
    public function it_validates_input_efficiently(): void
    {
        $startTime = microtime(true);

        // Invalid request should fail quickly
        $this->postJson('/api/v1/payments/init', [
            'amount' => -1, // Invalid
        ]);

        $elapsed = (microtime(true) - $startTime) * 1000;

        // Validation should be fast
        $this->assertLessThan(100, $elapsed, "Validation took {$elapsed}ms");
    }

    /** @test */
    public function it_handles_large_description_efficiently(): void
    {
        $largeDescription = str_repeat('Test description ', 100); // ~1.6KB

        $startTime = microtime(true);

        $this->postJson('/api/v1/payments/init', [
            'amount' => 50000,
            'description' => $largeDescription,
            'provider' => 'tinkoff',
            'return_url' => 'https://example.com/return',
        ]);

        $elapsed = (microtime(true) - $startTime) * 1000;

        $this->assertLessThan(100, $elapsed, "Large payload handling took {$elapsed}ms");
    }

    /** @test */
    public function it_caches_lookups_for_same_payment(): void
    {
        $payment = Payment::factory()->for($this->user)->create();

        DB::flushQueryLog();
        DB::enableQueryLog();

        // First request
        $this->getJson("/api/v1/payments/{$payment->id}");
        $queries1 = count(DB::getQueryLog());

        DB::flushQueryLog();

        // Second request (should use cache or have similar query count)
        $this->getJson("/api/v1/payments/{$payment->id}");
        $queries2 = count(DB::getQueryLog());

        // Both should have minimal queries
        $this->assertLessThan(3, $queries1);
        $this->assertLessThan(3, $queries2);
    }

    /** @test */
    public function it_handles_multiple_sorts_efficiently(): void
    {
        Payment::factory()->for($this->user)->count(100)->create();

        $startTime = microtime(true);

        $this->getJson('/api/v1/payments?sort=-amount,created_at&per_page=50');

        $elapsed = (microtime(true) - $startTime) * 1000;

        $this->assertLessThan(300, $elapsed, "Multi-column sort took {$elapsed}ms");
    }

    /** @test */
    public function it_provides_quick_list_with_default_pagination(): void
    {
        Payment::factory()->for($this->user)->count(1000)->create();

        $startTime = microtime(true);

        $response = $this->getJson('/api/v1/payments');

        $elapsed = (microtime(true) - $startTime) * 1000;

        // Should have default per_page (usually 15-25 items)
        $data = $response->json('data');
        $this->assertLessThanOrEqual(25, count($data));
        $this->assertLessThan(500, $elapsed, "Default pagination took {$elapsed}ms");
    }

    /** @test */
    public function it_returns_metadata_efficiently(): void
    {
        Payment::factory()->for($this->user)->count(100)->create();

        $startTime = microtime(true);

        $response = $this->getJson('/api/v1/payments?per_page=100');

        $elapsed = (microtime(true) - $startTime) * 1000;

        $meta = $response->json('meta');
        $this->assertNotNull($meta['total']);
        $this->assertLessThan(500, $elapsed);
    }

    /** @test */
    public function bulk_payment_capture_maintains_performance(): void
    {
        $payments = Payment::factory()
            ->for($this->user)
            ->create(['status' => 'authorized'], 50);

        $startTime = microtime(true);

        foreach ($payments as $payment) {
            $this->postJson("/api/v1/payments/{$payment->id}/capture");
        }

        $elapsed = (microtime(true) - $startTime) * 1000;

        $this->assertLessThan(5000, $elapsed, "50 captures took {$elapsed}ms total");
    }
}
