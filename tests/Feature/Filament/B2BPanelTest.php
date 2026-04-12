<?php declare(strict_types=1);

namespace Tests\Feature\Filament;

use PHPUnit\Framework\TestCase;

/**
 * Tests for B2B Filament Panel structure.
 *
 * @group filament
 */
final class B2BPanelTest extends TestCase
{
    public function test_panel_resources_directory_exists(): void
    {
        $resourceDir = __DIR__ . '/../../../app/Filament/B2B/Resources';
        $this->assertDirectoryExists($resourceDir, 'B2B panel resources dir must exist');
    }

    public function test_panel_has_resources(): void
    {
        $resourceDir = __DIR__ . '/../../../app/Filament/B2B/Resources';
        if (!is_dir($resourceDir)) {
            $this->markTestSkipped('B2B resources dir not found');
        }
        $files = glob($resourceDir . '/*Resource.php');
        $this->assertNotEmpty($files, 'B2B panel must have at least one resource');
    }

    public function test_panel_resources_follow_naming(): void
    {
        $resourceDir = __DIR__ . '/../../../app/Filament/B2B/Resources';
        if (!is_dir($resourceDir)) {
            $this->markTestSkipped('B2B resources dir not found');
        }
        $files = glob($resourceDir . '/*.php');
        foreach ($files as $file) {
            $name = basename($file);
            $this->assertMatchesRegularExpression('/^[A-Z][a-zA-Z]+Resource\.php$/', $name);
        }
    }
}
