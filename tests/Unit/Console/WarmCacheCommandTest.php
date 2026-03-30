<?php declare(strict_types=1);

namespace Tests\Unit\Console;

use Tests\TestCase;

final class WarmCacheCommandTest extends TestCase
{
    public function test_command_executes_successfully(): void
    {
        $this->artisan('cache:warm --user-id=1')
            ->assertExitCode(0);
    }

    public function test_command_warms_specific_vertical(): void
    {
        $this->artisan('cache:warm --vertical=beauty')
            ->assertExitCode(0);
    }

    public function test_command_without_options(): void
    {
        $this->artisan('cache:warm')
            ->assertExitCode(0);
    }
}
