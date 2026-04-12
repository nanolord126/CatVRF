<?php declare(strict_types=1);

namespace Tests\Feature\Filament;

use PHPUnit\Framework\TestCase;

/**
 * Tests for Admin Filament Panel structure.
 *
 * @group filament
 */
final class AdminPanelTest extends TestCase
{
    public function test_panel_resources_directory_exists(): void
    {
        $resourceDir = __DIR__ . '/../../../app/Filament/Admin/Resources';
        $this->assertDirectoryExists($resourceDir, 'Admin panel resources dir must exist');
    }

    public function test_panel_has_resources(): void
    {
        $resourceDir = __DIR__ . '/../../../app/Filament/Admin/Resources';
        if (!is_dir($resourceDir)) {
            $this->markTestSkipped('Admin resources dir not found');
        }
        $files = glob($resourceDir . '/*Resource.php');
        $this->assertNotEmpty($files, 'Admin panel must have at least one resource');
    }

    public function test_panel_resources_follow_naming(): void
    {
        $resourceDir = __DIR__ . '/../../../app/Filament/Admin/Resources';
        if (!is_dir($resourceDir)) {
            $this->markTestSkipped('Admin resources dir not found');
        }
        $files = glob($resourceDir . '/*.php');
        foreach ($files as $file) {
            $name = basename($file);
            $this->assertMatchesRegularExpression('/^[A-Z][a-zA-Z]+Resource\.php$/', $name);
        }
    }
}
