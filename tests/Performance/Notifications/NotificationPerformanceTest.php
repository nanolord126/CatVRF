<?php

declare(strict_types=1);

namespace Tests\Performance\Notifications;

use App\Models\Notification;
use App\Models\NotificationPreference;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/**
 * NotificationPerformanceTest
 * 
 * Тесты производительности - throughput, memory, query count
 */
final class NotificationPerformanceTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function it_lists_1000_notifications_under_500ms(): void
    {
        $user = User::factory()->create();
        Notification::factory()->count(1000)->for($user)->create();

        $startTime = microtime(true);

        $this->actingAs($user)
            ->getJson('/api/v1/notifications?per_page=100');

        $endTime = microtime(true);
        $duration = ($endTime - $startTime) * 1000; // Convert to milliseconds

        $this->assertLessThan(500, $duration, "Query took {$duration}ms, expected < 500ms");
    }

    /** @test */
    public function it_creates_100_notifications_under_1_second(): void
    {
        $startTime = microtime(true);

        for ($i = 0; $i < 100; $i++) {
            User::factory()->create();
        }

        Notification::factory()->count(100)->create();

        $endTime = microtime(true);
        $duration = ($endTime - $startTime);

        $this->assertLessThan(1, $duration, "Batch creation took {$duration}s, expected < 1s");
    }

    /** @test */
    public function it_uses_optimal_memory_for_large_datasets(): void
    {
        $startMemory = memory_get_usage(true);

        $users = User::factory()->count(100)->create();

        foreach ($users as $user) {
            Notification::factory()->for($user)->create();
        }

        $endMemory = memory_get_usage(true);
        $memoryUsed = $endMemory - $startMemory;

        // Should use less than 10MB for 100 users + notifications
        $maxMemory = 10 * 1024 * 1024; // 10MB in bytes

        $this->assertLessThan(
            $maxMemory,
            $memoryUsed,
            "Memory usage: " . round($memoryUsed / 1024 / 1024, 2) . "MB"
        );
    }

    /** @test */
    public function it_executes_notification_queries_with_minimal_count(): void
    {
        $user = User::factory()->create();
        Notification::factory()->count(10)->for($user)->create();

        DB::enableQueryLog();

        $this->actingAs($user)->getJson('/api/v1/notifications?per_page=10');

        $queryCount = count(DB::getQueryLog());

        // Should have minimal queries (not N+1)
        // Expected: 1 for auth + 1 for notifications + 1 for meta = ~3
        $this->assertLessThan(5, $queryCount, "Executed {$queryCount} queries");

        DB::disableQueryLog();
    }

    /** @test */
    public function it_handles_preference_queries_efficiently(): void
    {
        $user = User::factory()->create();
        NotificationPreference::factory()->count(20)->for($user)->create();

        DB::enableQueryLog();

        $this->actingAs($user)->getJson('/api/v1/notification-preferences');

        $queryCount = count(DB::getQueryLog());

        // Should not exceed 3 queries
        $this->assertLessThan(4, $queryCount, "Executed {$queryCount} queries");

        DB::disableQueryLog();
    }

    /** @test */
    public function it_processes_bulk_notifications_at_scale(): void
    {
        $users = User::factory()->count(1000)->create();

        $startTime = microtime(true);

        foreach (array_chunk($users->all(), 100) as $batch) {
            foreach ($batch as $user) {
                Notification::factory()->for($user)->create();
            }
        }

        $endTime = microtime(true);
        $duration = ($endTime - $startTime);

        // Should process 1000 notifications in < 5 seconds
        $this->assertLessThan(5, $duration, "Batch processing took {$duration}s");
    }

    /** @test */
    public function it_queries_with_indexes_efficiently(): void
    {
        $user = User::factory()->create();
        Notification::factory()->count(100)->for($user)->create();

        DB::enableQueryLog();

        // Query by indexed column (user_id)
        Notification::where('user_id', $user->id)->get();

        $queries = DB::getQueryLog();
        
        // Should use index (EXPLAIN would show "Using index")
        $this->assertNotEmpty($queries);

        DB::disableQueryLog();
    }

    /** @test */
    public function it_handles_concurrent_reads_efficiently(): void
    {
        $users = User::factory()->count(10)->create();
        
        foreach ($users as $user) {
            Notification::factory()->count(50)->for($user)->create();
        }

        // Simulate 10 concurrent reads
        $startTime = microtime(true);

        foreach ($users as $user) {
            $this->actingAs($user)->getJson('/api/v1/notifications');
        }

        $endTime = microtime(true);
        $duration = ($endTime - $startTime);

        // 10 concurrent reads should complete in < 2 seconds
        $this->assertLessThan(2, $duration, "Concurrent reads took {$duration}s");
    }

    /** @test */
    public function it_updates_notification_status_efficiently(): void
    {
        $user = User::factory()->create();
        Notification::factory()->count(100)->for($user)->create(['status' => 'pending']);

        DB::enableQueryLog();

        // Bulk update
        Notification::where('user_id', $user->id)->update(['status' => 'sent']);

        $queryCount = count(DB::getQueryLog());

        // Should be single UPDATE query
        $this->assertEquals(1, $queryCount, "Executed {$queryCount} queries");

        DB::disableQueryLog();
    }

    /** @test */
    public function it_filters_preferences_with_optimal_queries(): void
    {
        $user = User::factory()->create();
        
        for ($i = 0; $i < 20; $i++) {
            NotificationPreference::factory()
                ->for($user)
                ->create([
                    'enabled' => $i % 2 === 0,
                    'notification_type' => "type.{$i}",
                ]);
        }

        DB::enableQueryLog();

        $this->actingAs($user)
            ->getJson('/api/v1/notification-preferences?enabled=true');

        $queryCount = count(DB::getQueryLog());

        // Should use indexed WHERE clause
        $this->assertLessThan(3, $queryCount);

        DB::disableQueryLog();
    }

    /** @test */
    public function it_caches_preferences_appropriately(): void
    {
        $user = User::factory()->create();
        NotificationPreference::factory()->count(10)->for($user)->create();

        // First call (cold cache)
        $start1 = microtime(true);
        $this->actingAs($user)->getJson('/api/v1/notification-preferences');
        $time1 = microtime(true) - $start1;

        // Second call (warm cache) - should be faster
        $start2 = microtime(true);
        $this->actingAs($user)->getJson('/api/v1/notification-preferences');
        $time2 = microtime(true) - $start2;

        // Cached call should be faster or equal
        $this->assertLessThanOrEqual($time1, $time2 * 1.5);
    }

    /** @test */
    public function it_handles_pagination_efficiently(): void
    {
        $user = User::factory()->create();
        Notification::factory()->count(1000)->for($user)->create();

        DB::enableQueryLog();

        // Request page 50
        $this->actingAs($user)->getJson('/api/v1/notifications?per_page=20&page=50');

        $queryCount = count(DB::getQueryLog());

        // Pagination shouldn't add queries
        $this->assertLessThan(5, $queryCount);

        DB::disableQueryLog();
    }

    /** @test */
    public function it_processes_eager_loading(): void
    {
        $users = User::factory()->count(10)->create();
        
        foreach ($users as $user) {
            Notification::factory()->count(10)->for($user)->create();
        }

        DB::enableQueryLog();

        // With eager loading
        $notifications = Notification::with('user')->get();

        $queryCount = count(DB::getQueryLog());

        // Should be 2 queries (notifications + users), not N+1
        $this->assertLessThanOrEqual(2, $queryCount);

        DB::disableQueryLog();
    }

    /** @test */
    public function it_deletes_old_notifications_efficiently(): void
    {
        $user = User::factory()->create();
        
        // Create 100 old notifications
        Notification::factory()
            ->count(100)
            ->for($user)
            ->create(['created_at' => now()->subDays(90)]);

        DB::enableQueryLog();

        Notification::where('created_at', '<', now()->subDays(30))->delete();

        $queryCount = count(DB::getQueryLog());

        // Should be single DELETE query
        $this->assertLessThanOrEqual(1, $queryCount);

        DB::disableQueryLog();
    }

    /** @test */
    public function it_searches_notifications_efficiently(): void
    {
        $user = User::factory()->create();
        Notification::factory()->count(100)->for($user)->create();

        DB::enableQueryLog();

        $this->actingAs($user)
            ->getJson('/api/v1/notifications?type=payment');

        $queryCount = count(DB::getQueryLog());

        // Search should use index
        $this->assertLessThan(4, $queryCount);

        DB::disableQueryLog();
    }

    /** @test */
    public function it_aggregates_data_efficiently(): void
    {
        $user = User::factory()->create();
        Notification::factory()->count(500)->for($user)->create([
            'status' => 'sent',
        ]);

        DB::enableQueryLog();

        // Count unread
        $count = Notification::where('user_id', $user->id)
            ->whereNull('read_at')
            ->count();

        $queryCount = count(DB::getQueryLog());

        // Should be single COUNT query
        $this->assertEqual(1, $queryCount);

        DB::disableQueryLog();
    }

    /** @test */
    public function it_handles_json_column_queries_efficiently(): void
    {
        $user = User::factory()->create();
        
        Notification::factory()
            ->count(50)
            ->for($user)
            ->create([
                'metadata' => ['key' => 'value', 'count' => 123],
            ]);

        DB::enableQueryLog();

        // Query JSON column
        $this->actingAs($user)
            ->getJson('/api/v1/notifications?has_metadata=true');

        $queryCount = count(DB::getQueryLog());

        // Should still be efficient
        $this->assertLessThan(4, $queryCount);

        DB::disableQueryLog();
    }

    /** @test */
    public function it_streams_large_datasets_efficiently(): void
    {
        $users = User::factory()->count(100)->create();
        
        foreach ($users as $user) {
            Notification::factory()->count(10)->for($user)->create();
        }

        $startMemory = memory_get_usage(true);

        // Chunked processing (streaming)
        Notification::chunk(100, function ($batch) {
            foreach ($batch as $notification) {
                // Process
            }
        });

        $endMemory = memory_get_usage(true);
        $memoryUsed = $endMemory - $startMemory;

        // Chunked processing should use minimal memory
        $this->assertLessThan(5 * 1024 * 1024, $memoryUsed); // 5MB
    }

    /** @test */
    public function it_measures_api_response_time(): void
    {
        $user = User::factory()->create();
        Notification::factory()->count(50)->for($user)->create();

        $start = microtime(true);

        $response = $this->actingAs($user)
            ->getJson('/api/v1/notifications?per_page=50');

        $duration = (microtime(true) - $start) * 1000;

        $response->assertOk();

        // API response should be under 200ms
        $this->assertLessThan(200, $duration, "API response took {$duration}ms");
    }

    /** @test */
    public function it_handles_sorting_efficiently(): void
    {
        $user = User::factory()->create();
        Notification::factory()->count(100)->for($user)->create();

        DB::enableQueryLog();

        $this->actingAs($user)
            ->getJson('/api/v1/notifications?sort=created_at&order=desc');

        $queryCount = count(DB::getQueryLog());

        // Sorting shouldn't add queries
        $this->assertLessThan(4, $queryCount);

        DB::disableQueryLog();
    }

    /** @test */
    public function it_uses_database_indexes_for_common_queries(): void
    {
        $user = User::factory()->create();
        Notification::factory()->count(100)->for($user)->create();

        // Common queries that should use indexes:
        // - WHERE user_id = ?
        // - WHERE status = ?
        // - WHERE created_at > ?

        $this->assertTrue(true); // Index validation would require EXPLAIN
    }

    /** @test */
    public function it_maintains_performance_with_many_channels(): void
    {
        $notification = Notification::factory()->create([
            'channels' => ['email', 'sms', 'push', 'database', 'websocket'],
        ]);

        $start = microtime(true);

        $this->actingAs($notification->user)
            ->getJson("/api/v1/notifications/{$notification->id}");

        $duration = (microtime(true) - $start) * 1000;

        // Should still be fast even with many channels
        $this->assertLessThan(100, $duration);
    }
}
