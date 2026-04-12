<?php declare(strict_types=1);

namespace Tests\Unit\Domains\Legal;

use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

/**
 * =================================================================
 *  Legal Domain — STRUCTURAL COMPLIANCE TEST
 *  CANON CatVRF 2026: 9-слойная архитектура.
 * =================================================================
 */
final class LegalStructureTest extends TestCase
{
    private const BASE = __DIR__ . '/../../../../app/Domains/Legal';

    /* ================================================================== */
    /*  Layer 1 — Models                                                   */
    /* ================================================================== */

    #[Test]
    public function layer1_models_directory_exists(): void
    {
        self::assertDirectoryExists(self::BASE . '/Models');
    }

    #[Test]
    public function layer1_model_files_present(): void
    {
        $models = glob(self::BASE . '/Models/*.php');
        self::assertNotEmpty($models, 'Legal: Models directory must contain files');
    }

    #[Test]
    public function layer1_models_are_final(): void
    {
        $files = glob(self::BASE . '/Models/*.php') ?: [];

        foreach ($files as $file) {
            $content = file_get_contents($file);
            $name = pathinfo($file, PATHINFO_FILENAME);

            if (str_contains($content, 'class ') && !str_contains($content, 'abstract ')) {
                self::assertTrue(
                    str_contains($content, 'final'),
                    "Model {$name} must be final class",
                );
            }
        }
    }

    /* ================================================================== */
    /*  Layer 2 — DTOs                                                     */
    /* ================================================================== */

    #[Test]
    public function layer2_dtos_directory_exists(): void
    {
        self::assertDirectoryExists(self::BASE . '/DTOs');
    }

    #[Test]
    public function layer2_dto_files_present(): void
    {
        $dtos = glob(self::BASE . '/DTOs/*.php');
        self::assertNotEmpty($dtos, 'Legal: DTOs directory must contain files');
    }

    #[Test]
    public function layer2_dtos_are_final_readonly(): void
    {
        $files = glob(self::BASE . '/DTOs/*.php') ?: [];

        foreach ($files as $file) {
            $content = file_get_contents($file);
            $name = pathinfo($file, PATHINFO_FILENAME);

            if (str_contains($content, 'class ')) {
                self::assertTrue(
                    str_contains($content, 'final') && str_contains($content, 'readonly'),
                    "DTO {$name} must be final readonly class",
                );
            }
        }
    }

    /* ================================================================== */
    /*  Layer 3 — Services                                                 */
    /* ================================================================== */

    #[Test]
    public function layer3_services_directory_exists(): void
    {
        self::assertDirectoryExists(self::BASE . '/Services');
    }

    #[Test]
    public function layer3_service_files_present(): void
    {
        $services = glob(self::BASE . '/Services/*.php');
        self::assertNotEmpty($services, 'Legal: Services directory must contain files');
    }

    #[Test]
    public function layer3_services_are_final(): void
    {
        $files = glob(self::BASE . '/Services/*.php') ?: [];

        foreach ($files as $file) {
            $content = file_get_contents($file);
            $name = pathinfo($file, PATHINFO_FILENAME);

            if (str_contains($content, 'class ') && !str_contains($content, 'interface ')) {
                self::assertTrue(
                    str_contains($content, 'final'),
                    "Service {$name} must be final class",
                );
            }
        }
    }

    #[Test]
    public function layer3_ai_constructor_exists(): void
    {
        self::assertDirectoryExists(
            self::BASE . '/Services/AI',
            'Legal: AI-constructor directory is PRODUCTION MANDATORY',
        );

        $aiFiles = glob(self::BASE . '/Services/AI/*.php');
        self::assertNotEmpty($aiFiles, 'Legal: Must have AI constructor service');
    }

    /* ================================================================== */
    /*  Layer 6 — Events                                                   */
    /* ================================================================== */

    #[Test]
    public function layer6_events_directory_exists(): void
    {
        self::assertDirectoryExists(self::BASE . '/Events');
    }

    /* ================================================================== */
    /*  Layer 7 — Listeners                                                */
    /* ================================================================== */

    #[Test]
    public function layer7_listeners_directory_exists(): void
    {
        self::assertDirectoryExists(self::BASE . '/Listeners');
    }

    /* ================================================================== */
    /*  Layer 8 — Jobs                                                     */
    /* ================================================================== */

    #[Test]
    public function layer8_jobs_directory_exists(): void
    {
        self::assertDirectoryExists(self::BASE . '/Jobs');
    }

    /* ================================================================== */
    /*  Canon Compliance                                                    */
    /* ================================================================== */

    #[Test]
    public function all_php_files_have_strict_types(): void
    {
        $files = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator(self::BASE, \RecursiveDirectoryIterator::SKIP_DOTS),
        );

        $violations = [];

        foreach ($files as $file) {
            if ($file->getExtension() !== 'php') {
                continue;
            }

            $content = file_get_contents($file->getPathname());

            if (!str_contains($content, 'declare(strict_types=1)')) {
                $violations[] = str_replace(self::BASE . '/', '', $file->getPathname());
            }
        }

        self::assertEmpty(
            $violations,
            'Legal: strict_types=1 violations: ' . implode(', ', array_slice($violations, 0, 5)),
        );
    }

    #[Test]
    public function services_have_no_forbidden_facades(): void
    {
        $forbidden = ['DB::', 'Log::', 'Auth::', 'Cache::', 'request(', 'config(', 'auth('];
        $files = glob(self::BASE . '/Services/*.php') ?: [];

        foreach ($files as $file) {
            $content = file_get_contents($file);
            $name = pathinfo($file, PATHINFO_FILENAME);

            foreach ($forbidden as $facade) {
                self::assertStringNotContainsString(
                    $facade,
                    $content,
                    "Legal/Services/{$name}: must not use {$facade}",
                );
            }
        }
    }

    #[Test]
    public function services_use_constructor_injection(): void
    {
        $files = glob(self::BASE . '/Services/*.php') ?: [];

        foreach ($files as $file) {
            $content = file_get_contents($file);
            $name = pathinfo($file, PATHINFO_FILENAME);

            if (str_contains($content, 'class ') && !str_contains($content, 'interface ')) {
                self::assertTrue(
                    str_contains($content, '__construct'),
                    "Legal/Services/{$name}: must use constructor injection",
                );
            }
        }
    }

    #[Test]
    public function minimum_file_length_60_lines(): void
    {
        $files = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator(self::BASE, \RecursiveDirectoryIterator::SKIP_DOTS),
        );

        $shortFiles = [];

        foreach ($files as $file) {
            if ($file->getExtension() !== 'php') {
                continue;
            }

            $lines = substr_count(file_get_contents($file->getPathname()), "\n") + 1;

            if ($lines < 60) {
                $relative = str_replace(self::BASE . '/', '', $file->getPathname());
                $shortFiles[] = "{$relative} ({$lines} lines)";
            }
        }

        self::assertEmpty(
            $shortFiles,
            'Legal: Files under 60 lines: ' . implode(', ', array_slice($shortFiles, 0, 5)),
        );
    }
}
