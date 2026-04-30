<?php

declare(strict_types=1);

namespace Tests\Smoke;

use Tests\BaseTestCase;

final class SmokeTest extends BaseTestCase
{
    public function test_api_health_endpoint(): void
    {
        $this->get('/api/health')
            ->assertStatus(200)
            ->assertJson(['status' => 'ok']);
    }

    public function test_api_version_endpoint(): void
    {
        $this->get('/api/version')
            ->assertStatus(200)
            ->assertJsonStructure(['version', 'build']);
    }

    public function test_database_connection(): void
    {
        $this->assertDatabaseConnection('mysql');
    }

    public function test_redis_connection(): void
    {
        $this->assertDatabaseConnection('redis');
    }

    public function test_cache_is_working(): void
    {
        $key = 'smoke_test_key';
        $value = 'test_value';

        \Cache::put($key, $value, 60);
        $this->assertEquals($value, \Cache::get($key));
        \Cache::forget($key);
    }

    public function test_queue_is_working(): void
    {
        $job = new \App\Jobs\TestJob();
        \Queue::push($job);
        $this->assertTrue(\Queue::size() > 0);
    }
}
