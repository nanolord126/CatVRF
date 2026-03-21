<?php declare(strict_types=1);

namespace Tests\Unit\Services;

use Tests\SimpleTestCase;

/**
 * Smoke Test - проверяет что фреймворк инициализируется правильно
 */
class SmokeTest extends SimpleTestCase
{
    /**
     * @test
     */
    public function test_framework_can_initialize(): void
    {
        $this->assertTrue(true);
    }

    /**
     * @test
     */
    public function test_app_is_available(): void
    {
        $this->assertNotNull($this->app);
    }

    /**
     * @test
     */
    public function test_config_is_loaded(): void
    {
        $appName = config('app.name');
        $this->assertNotNull($appName);
    }

    /**
     * @test
     */
    public function test_database_connection_exists(): void
    {
        $connection = config('database.default');
        $this->assertNotNull($connection);
    }

    /**
     * @test
     */
    public function test_correlation_id_generated(): void
    {
        $correlationId = $this->getCorrelationId();
        $this->assertNotEmpty($correlationId);
        $this->assertIsString($correlationId);
    }

    /**
     * @test
     */
    public function test_faker_is_available(): void
    {
        $email = $this->faker->email();
        $this->assertNotEmpty($email);
        $this->assertStringContainsString('@', $email);
    }
}
