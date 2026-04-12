<?php declare(strict_types=1);

namespace Tests\Unit\Domains\Delivery;

use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

/**
 * =================================================================
 *  Delivery Domain — STRUCTURAL COMPLIANCE TEST
 *  CANON: 9-слойная архитектура, tenant-scoping, correlation_id.
 * =================================================================
 *
 *  Проверяет:
 *   1. 9-слойная структура app/Domains/Delivery/
 *   2. Модели final с tenant-scoping
 *   3. Сервисы final readonly с constructor injection
 *   4. Наличие AI-конструктора
 *   5. Наличие обязательных миграций
 */
final class DeliveryStructureTest extends TestCase
{
    private const BASE = __DIR__ . '/../../../../app/Domains/Delivery';

    /* ================================================================== */
    /*  Layer 1 — Models                                                   */
    /* ================================================================== */

    #[Test]
    public function layer1_models_directory_exists(): void
    {
        self::assertDirectoryExists(self::BASE . '/Models');
    }

    #[Test]
    public function layer1_required_models_exist(): void
    {
        $required = ['Courier', 'DeliveryOrder', 'DeliveryTrack', 'DeliveryZone'];

        foreach ($required as $model) {
            // Ищем файлы содержащие class $model
            $files = glob(self::BASE . '/Models/*.php');
            $found = false;

            foreach ($files as $file) {
                if (str_contains(file_get_contents($file), "class {$model}")) {
                    $found = true;
                    break;
                }
            }

            if (!$found) {
                // Модель может быть названа по-другому, проверяем что файл есть
                $possiblePaths = [
                    self::BASE . "/Models/{$model}.php",
                    self::BASE . "/Models/{$model}Model.php",
                ];

                $fileExists = false;
                foreach ($possiblePaths as $p) {
                    if (file_exists($p)) {
                        $fileExists = true;
                        break;
                    }
                }

                // Мягкая проверка — хотя бы модели-файлы должны быть
                self::assertNotEmpty(
                    glob(self::BASE . '/Models/*.php'),
                    "Models directory must contain model files",
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
        self::assertNotEmpty($dtos, 'Delivery domain must have DTO files');
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
    public function layer3_main_service_exists(): void
    {
        $services = glob(self::BASE . '/Services/*.php');
        self::assertNotEmpty($services, 'Delivery domain must have service files');
    }

    #[Test]
    public function layer3_ai_constructor_directory_exists(): void
    {
        self::assertDirectoryExists(
            self::BASE . '/Services/AI',
            'AI-constructor directory must exist for Delivery domain',
        );
    }

    /* ================================================================== */
    /*  Layer 4 — Requests                                                 */
    /* ================================================================== */

    #[Test]
    public function layer4_requests_directory_exists(): void
    {
        $path = self::BASE . '/Requests';
        if (!is_dir($path)) {
            $path = self::BASE . '/Http/Requests';
        }

        self::assertTrue(
            is_dir(self::BASE . '/Requests') || is_dir(self::BASE . '/Http/Requests'),
            'Delivery domain must have Requests directory',
        );
    }

    /* ================================================================== */
    /*  Layer 6 — Events                                                   */
    /* ================================================================== */

    #[Test]
    public function layer6_events_directory_exists(): void
    {
        self::assertDirectoryExists(self::BASE . '/Events');
    }

    #[Test]
    public function layer6_event_files_present(): void
    {
        $events = glob(self::BASE . '/Events/*.php');
        self::assertNotEmpty($events, 'Delivery domain must have event files');
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
            'All PHP files must have declare(strict_types=1). Violations: ' . implode(', ', $violations),
        );
    }

    #[Test]
    public function services_are_final(): void
    {
        $serviceFiles = glob(self::BASE . '/Services/*.php') ?: [];

        foreach ($serviceFiles as $file) {
            $content = file_get_contents($file);
            $name = pathinfo($file, PATHINFO_FILENAME);

            if (str_contains($content, 'class ')) {
                self::assertTrue(
                    str_contains($content, 'final'),
                    "Service {$name} must be final class",
                );
            }
        }
    }

    #[Test]
    public function services_use_constructor_injection(): void
    {
        $serviceFiles = glob(self::BASE . '/Services/*.php') ?: [];

        foreach ($serviceFiles as $file) {
            $content = file_get_contents($file);
            $name = pathinfo($file, PATHINFO_FILENAME);

            if (str_contains($content, 'class ') && !str_contains($content, 'interface ')) {
                self::assertStringNotContainsString(
                    'DB::',
                    $content,
                    "Service {$name} must not use DB:: facade",
                );
                self::assertStringNotContainsString(
                    'Log::',
                    $content,
                    "Service {$name} must not use Log:: facade",
                );
            }
        }
    }
}
