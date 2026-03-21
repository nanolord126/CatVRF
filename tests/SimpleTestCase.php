<?php declare(strict_types=1);

namespace Tests;

use Illuminate\Foundation\Testing\TestCase as LaravelTestCase;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Support\Facades\Schema;

/**
 * Simple base test case that doesn't require tenancy.
 * Used for unit tests that test services/models directly.
 */
abstract class SimpleTestCase extends LaravelTestCase
{
    use WithFaker;

    protected string $correlationId;

    protected function setUp(): void
    {
        parent::setUp();

        $this->correlationId = \Illuminate\Support\Str::uuid()->toString();
        
        // Ensure migrations are run
        $this->ensureMigrationsRun();
    }

    protected function ensureMigrationsRun(): void
    {
        // Check if migration table exists using the default connection
        if (!Schema::hasTable('migrations')) {
            $this->artisan('migrate', ['--force' => true]);
        }
    }

    protected function getCorrelationId(): string
    {
        return $this->correlationId;
    }
}
