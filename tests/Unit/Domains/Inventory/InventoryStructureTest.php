<?php

declare(strict_types=1);

namespace Tests\Unit\Domains\Inventory;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use ReflectionClass;

/**
 * =================================================================
 *  9-СЛОЙНАЯ АРХИТЕКТУРА — STRUCTURAL COMPLIANCE TEST
 *  Модуль: Inventory
 * =================================================================
 *
 *  Проверяет соответствие CANON CatVRF 2026:
 *   Layer 1 — Models
 *   Layer 2 — DTOs
 *   Layer 3 — Services (+ AI Constructor)
 *   Layer 4 — Requests
 *   Layer 5 — Resources (JsonResource)
 *   Layer 6 — Events
 *   Layer 7 — Listeners
 *   Layer 8 — Jobs
 *   Layer 9 — Filament
 *   + Enums, Exceptions, Controllers, Policies
 *
 *  Каждый тест проверяет физическое существование файлов,
 *  namespace, strict_types, final, readonly, no facades.
 */
final class InventoryStructureTest extends TestCase
{
    private const BASE = __DIR__ . '/../../../../app/Domains/Inventory';

    /* ================================================================== */
    /*  Layer 1 — Models                                                   */
    /* ================================================================== */

    /** @return list<array{string, string}> */
    public static function modelsProvider(): array
    {
        return [
            ['Warehouse',      'App\Domains\Inventory\Models\Warehouse'],
            ['InventoryItem',  'App\Domains\Inventory\Models\InventoryItem'],
            ['StockMovement',  'App\Domains\Inventory\Models\StockMovement'],
            ['Reservation',    'App\Domains\Inventory\Models\Reservation'],
            ['InventoryCheck', 'App\Domains\Inventory\Models\InventoryCheck'],
        ];
    }

    #[Test]
    #[DataProvider('modelsProvider')]
    public function layer1_model_file_exists(string $name, string $fqcn): void
    {
        $path = self::BASE . "/Models/{$name}.php";
        self::assertFileExists($path, "Layer 1 — Model {$name} must exist");
    }

    #[Test]
    #[DataProvider('modelsProvider')]
    public function layer1_model_class_is_final(string $name, string $fqcn): void
    {
        self::assertTrue((new ReflectionClass($fqcn))->isFinal(), "Model {$name} must be final");
    }

    #[Test]
    public function layer1_model_directory_exists(): void
    {
        self::assertDirectoryExists(self::BASE . '/Models');
    }

    #[Test]
    public function layer1_models_count(): void
    {
        $files = glob(self::BASE . '/Models/*.php');
        self::assertGreaterThanOrEqual(5, count($files ?: []), 'Inventory must have at least 5 models');
    }

    /* ================================================================== */
    /*  Layer 2 — DTOs                                                     */
    /* ================================================================== */

    /** @return list<array{string, string}> */
    public static function dtosProvider(): array
    {
        return [
            ['CreateReservationDto',    'App\Domains\Inventory\DTOs\CreateReservationDto'],
            ['CreateStockMovementDto',  'App\Domains\Inventory\DTOs\CreateStockMovementDto'],
            ['CreateAdjustmentDto',     'App\Domains\Inventory\DTOs\CreateAdjustmentDto'],
            ['SearchInventoryDto',      'App\Domains\Inventory\DTOs\SearchInventoryDto'],
            ['ImportResultDto',         'App\Domains\Inventory\DTOs\ImportResultDto'],
        ];
    }

    #[Test]
    #[DataProvider('dtosProvider')]
    public function layer2_dto_file_exists(string $name, string $fqcn): void
    {
        $path = self::BASE . "/DTOs/{$name}.php";
        self::assertFileExists($path, "Layer 2 — DTO {$name} must exist");
    }

    #[Test]
    #[DataProvider('dtosProvider')]
    public function layer2_dto_is_final_readonly(string $name, string $fqcn): void
    {
        $ref = new ReflectionClass($fqcn);
        self::assertTrue($ref->isFinal(), "DTO {$name} must be final");
        self::assertTrue($ref->isReadOnly(), "DTO {$name} must be readonly");
    }

    #[Test]
    public function layer2_dto_directory_exists(): void
    {
        self::assertDirectoryExists(self::BASE . '/DTOs');
    }

    /* ================================================================== */
    /*  Layer 3 — Services (+ AI Constructor)                              */
    /* ================================================================== */

    /** @return list<array{string, string}> */
    public static function servicesProvider(): array
    {
        return [
            ['InventoryService',          'App\Domains\Inventory\Services\InventoryService'],
            ['WarehouseService',          'App\Domains\Inventory\Services\WarehouseService'],
            ['InventoryAuditService',     'App\Domains\Inventory\Services\InventoryAuditService'],
            ['InventoryConstructorService', 'App\Domains\Inventory\Services\AI\InventoryConstructorService'],
        ];
    }

    #[Test]
    #[DataProvider('servicesProvider')]
    public function layer3_service_file_exists(string $name, string $fqcn): void
    {
        $ref  = new ReflectionClass($fqcn);
        $file = $ref->getFileName();
        self::assertNotFalse($file, "Layer 3 — Service {$name} must exist");
        self::assertFileExists($file);
    }

    #[Test]
    #[DataProvider('servicesProvider')]
    public function layer3_service_is_final_readonly(string $name, string $fqcn): void
    {
        $ref = new ReflectionClass($fqcn);
        self::assertTrue($ref->isFinal(), "Service {$name} must be final");
        self::assertTrue($ref->isReadOnly(), "Service {$name} must be readonly");
    }

    #[Test]
    public function layer3_ai_constructor_exists(): void
    {
        $path = self::BASE . '/Services/AI/InventoryConstructorService.php';
        self::assertFileExists($path, 'CANON: AI Constructor MANDATORY for every vertical');
    }

    #[Test]
    public function layer3_services_directory_exists(): void
    {
        self::assertDirectoryExists(self::BASE . '/Services');
        self::assertDirectoryExists(self::BASE . '/Services/AI');
    }

    /* ================================================================== */
    /*  Layer 4 — Requests                                                 */
    /* ================================================================== */

    /** @return list<array{string, string}> */
    public static function requestsProvider(): array
    {
        return [
            ['ReserveStockRequest', 'App\Domains\Inventory\Http\Requests\ReserveStockRequest'],
            ['AdjustStockRequest',  'App\Domains\Inventory\Http\Requests\AdjustStockRequest'],
        ];
    }

    #[Test]
    #[DataProvider('requestsProvider')]
    public function layer4_request_file_exists(string $name, string $fqcn): void
    {
        $path = self::BASE . "/Http/Requests/{$name}.php";
        self::assertFileExists($path, "Layer 4 — Request {$name} must exist");
    }

    #[Test]
    #[DataProvider('requestsProvider')]
    public function layer4_request_is_final(string $name, string $fqcn): void
    {
        self::assertTrue((new ReflectionClass($fqcn))->isFinal(), "Request {$name} must be final");
    }

    #[Test]
    public function layer4_requests_directory_exists(): void
    {
        self::assertDirectoryExists(self::BASE . '/Http/Requests');
    }

    /* ================================================================== */
    /*  Layer 5 — Resources (API JsonResource)                             */
    /* ================================================================== */

    /** @return list<array{string, string}> */
    public static function resourcesProvider(): array
    {
        return [
            ['InventoryItemResource',  'App\Domains\Inventory\Http\Resources\InventoryItemResource'],
            ['StockMovementResource',  'App\Domains\Inventory\Http\Resources\StockMovementResource'],
            ['ReservationResource',    'App\Domains\Inventory\Http\Resources\ReservationResource'],
        ];
    }

    #[Test]
    #[DataProvider('resourcesProvider')]
    public function layer5_resource_file_exists(string $name, string $fqcn): void
    {
        $path = self::BASE . "/Http/Resources/{$name}.php";
        self::assertFileExists($path, "Layer 5 — Resource {$name} must exist");
    }

    #[Test]
    #[DataProvider('resourcesProvider')]
    public function layer5_resource_is_final(string $name, string $fqcn): void
    {
        self::assertTrue((new ReflectionClass($fqcn))->isFinal(), "Resource {$name} must be final");
    }

    #[Test]
    public function layer5_resources_directory_exists(): void
    {
        self::assertDirectoryExists(self::BASE . '/Http/Resources');
    }

    /* ================================================================== */
    /*  Layer 6 — Events                                                   */
    /* ================================================================== */

    /** @return list<array{string, string}> */
    public static function eventsProvider(): array
    {
        return [
            ['StockReserved',           'App\Domains\Inventory\Events\StockReserved'],
            ['StockReleased',           'App\Domains\Inventory\Events\StockReleased'],
            ['StockUpdated',            'App\Domains\Inventory\Events\StockUpdated'],
            ['InventoryCheckCreated',   'App\Domains\Inventory\Events\InventoryCheckCreated'],
            ['InventoryCheckUpdated',   'App\Domains\Inventory\Events\InventoryCheckUpdated'],
        ];
    }

    #[Test]
    #[DataProvider('eventsProvider')]
    public function layer6_event_file_exists(string $name, string $fqcn): void
    {
        $path = self::BASE . "/Events/{$name}.php";
        self::assertFileExists($path, "Layer 6 — Event {$name} must exist");
    }

    #[Test]
    #[DataProvider('eventsProvider')]
    public function layer6_event_is_final(string $name, string $fqcn): void
    {
        self::assertTrue((new ReflectionClass($fqcn))->isFinal(), "Event {$name} must be final");
    }

    #[Test]
    public function layer6_events_directory_exists(): void
    {
        self::assertDirectoryExists(self::BASE . '/Events');
    }

    #[Test]
    public function layer6_events_count(): void
    {
        $files = glob(self::BASE . '/Events/*.php');
        self::assertGreaterThanOrEqual(5, count($files ?: []), 'Inventory must have at least 5 events');
    }

    /* ================================================================== */
    /*  Layer 7 — Listeners                                                */
    /* ================================================================== */

    /** @return list<array{string, string}> */
    public static function listenersProvider(): array
    {
        return [
            ['LogStockReserved',           'App\Domains\Inventory\Listeners\LogStockReserved'],
            ['LogStockReleased',           'App\Domains\Inventory\Listeners\LogStockReleased'],
            ['LogStockUpdated',            'App\Domains\Inventory\Listeners\LogStockUpdated'],
            ['LogInventoryCheckCreated',   'App\Domains\Inventory\Listeners\LogInventoryCheckCreated'],
            ['LogInventoryCheckUpdated',   'App\Domains\Inventory\Listeners\LogInventoryCheckUpdated'],
        ];
    }

    #[Test]
    #[DataProvider('listenersProvider')]
    public function layer7_listener_file_exists(string $name, string $fqcn): void
    {
        $path = self::BASE . "/Listeners/{$name}.php";
        self::assertFileExists($path, "Layer 7 — Listener {$name} must exist");
    }

    #[Test]
    #[DataProvider('listenersProvider')]
    public function layer7_listener_is_final_readonly(string $name, string $fqcn): void
    {
        $ref = new ReflectionClass($fqcn);
        self::assertTrue($ref->isFinal(), "Listener {$name} must be final");
        self::assertTrue($ref->isReadOnly(), "Listener {$name} must be readonly");
    }

    #[Test]
    public function layer7_listeners_directory_exists(): void
    {
        self::assertDirectoryExists(self::BASE . '/Listeners');
    }

    /* ================================================================== */
    /*  Layer 8 — Jobs                                                     */
    /* ================================================================== */

    /** @return list<array{string, string}> */
    public static function jobsProvider(): array
    {
        return [
            ['ReservationCleanupJob',     'App\Domains\Inventory\Jobs\ReservationCleanupJob'],
            ['ProcessInventoryCheckJob',  'App\Domains\Inventory\Jobs\ProcessInventoryCheckJob'],
        ];
    }

    #[Test]
    #[DataProvider('jobsProvider')]
    public function layer8_job_file_exists(string $name, string $fqcn): void
    {
        $path = self::BASE . "/Jobs/{$name}.php";
        self::assertFileExists($path, "Layer 8 — Job {$name} must exist");
    }

    #[Test]
    #[DataProvider('jobsProvider')]
    public function layer8_job_is_final(string $name, string $fqcn): void
    {
        self::assertTrue((new ReflectionClass($fqcn))->isFinal(), "Job {$name} must be final");
    }

    #[Test]
    public function layer8_jobs_directory_exists(): void
    {
        self::assertDirectoryExists(self::BASE . '/Jobs');
    }

    /* ================================================================== */
    /*  Layer 9 — Filament                                                 */
    /* ================================================================== */

    #[Test]
    public function layer9_filament_resource_exists(): void
    {
        self::assertFileExists(
            self::BASE . '/Filament/Resources/InventoryCheckResource.php',
            'Layer 9 — Filament Resource must exist',
        );
    }

    #[Test]
    public function layer9_filament_pages_exist(): void
    {
        $pages = [
            'CreateInventoryCheck.php',
            'EditInventoryCheck.php',
            'ListInventoryChecks.php',
        ];

        foreach ($pages as $page) {
            self::assertFileExists(
                self::BASE . "/Filament/Resources/InventoryCheckResource/Pages/{$page}",
                "Layer 9 — Filament page {$page} must exist",
            );
        }
    }

    #[Test]
    public function layer9_filament_directory_exists(): void
    {
        self::assertDirectoryExists(self::BASE . '/Filament');
        self::assertDirectoryExists(self::BASE . '/Filament/Resources');
    }

    /* ================================================================== */
    /*  Mandatory extras: Enums, Exceptions, Controllers, Policies         */
    /* ================================================================== */

    #[Test]
    public function enums_directory_exists(): void
    {
        self::assertDirectoryExists(self::BASE . '/Enums');
    }

    #[Test]
    public function enums_files_exist(): void
    {
        self::assertFileExists(self::BASE . '/Enums/StockMovementType.php');
        self::assertFileExists(self::BASE . '/Enums/InventoryCheckStatus.php');
    }

    #[Test]
    public function exceptions_directory_exists(): void
    {
        self::assertDirectoryExists(self::BASE . '/Exceptions');
    }

    #[Test]
    public function exceptions_files_exist(): void
    {
        self::assertFileExists(self::BASE . '/Exceptions/InsufficientStockException.php');
    }

    #[Test]
    public function controllers_directory_exists(): void
    {
        self::assertDirectoryExists(self::BASE . '/Http/Controllers');
    }

    #[Test]
    public function controllers_files_exist(): void
    {
        self::assertFileExists(self::BASE . '/Http/Controllers/InventoryController.php');
    }

    #[Test]
    public function policies_directory_exists(): void
    {
        self::assertDirectoryExists(self::BASE . '/Policies');
    }

    #[Test]
    public function policies_files_exist(): void
    {
        self::assertFileExists(self::BASE . '/Policies/InventoryCheckPolicy.php');
    }

    /* ================================================================== */
    /*  CANON: strict_types in EVERY file                                  */
    /* ================================================================== */

    #[Test]
    public function all_php_files_have_strict_types(): void
    {
        $files = $this->getAllPhpFiles(self::BASE);
        self::assertNotEmpty($files, 'Module must have PHP files');

        $violations = [];
        foreach ($files as $file) {
            $content = (string) file_get_contents($file);
            if (!str_contains($content, 'declare(strict_types=1);')) {
                $violations[] = basename($file);
            }
        }

        self::assertEmpty($violations, 'Files missing strict_types: ' . implode(', ', $violations));
    }

    /* ================================================================== */
    /*  CANON: NO facades anywhere                                         */
    /* ================================================================== */

    #[Test]
    public function no_php_file_imports_facades(): void
    {
        $files = $this->getAllPhpFiles(self::BASE);

        $violations = [];
        foreach ($files as $file) {
            $content = (string) file_get_contents($file);
            if (str_contains($content, 'use Illuminate\Support\Facades\\')) {
                $violations[] = basename($file);
            }
        }

        self::assertEmpty($violations, 'Files importing facades: ' . implode(', ', $violations));
    }

    /* ================================================================== */
    /*  CANON: correlation_id used in services and events                   */
    /* ================================================================== */

    #[Test]
    public function all_services_reference_correlation_id(): void
    {
        $serviceFiles = glob(self::BASE . '/Services/*.php') ?: [];
        $aiFiles      = glob(self::BASE . '/Services/AI/*.php') ?: [];
        $all          = array_merge($serviceFiles, $aiFiles);

        self::assertNotEmpty($all);

        $violations = [];
        foreach ($all as $file) {
            $content = (string) file_get_contents($file);
            if (!str_contains($content, 'correlation_id') && !str_contains($content, 'correlationId')) {
                $violations[] = basename($file);
            }
        }

        self::assertEmpty($violations, 'Services missing correlation_id: ' . implode(', ', $violations));
    }

    /* ================================================================== */
    /*  9-Layer Summary (informational, always passes)                     */
    /* ================================================================== */

    #[Test]
    public function nine_layer_compliance_summary(): void
    {
        $layers = [
            '1-Models'     => is_dir(self::BASE . '/Models'),
            '2-DTOs'       => is_dir(self::BASE . '/DTOs'),
            '3-Services'   => is_dir(self::BASE . '/Services'),
            '3-AI'         => is_dir(self::BASE . '/Services/AI'),
            '4-Requests'   => is_dir(self::BASE . '/Http/Requests'),
            '5-Resources'  => is_dir(self::BASE . '/Http/Resources'),
            '6-Events'     => is_dir(self::BASE . '/Events'),
            '7-Listeners'  => is_dir(self::BASE . '/Listeners'),
            '8-Jobs'       => is_dir(self::BASE . '/Jobs'),
            '9-Filament'   => is_dir(self::BASE . '/Filament'),
            'Enums'        => is_dir(self::BASE . '/Enums'),
            'Exceptions'   => is_dir(self::BASE . '/Exceptions'),
            'Controllers'  => is_dir(self::BASE . '/Http/Controllers'),
            'Policies'     => is_dir(self::BASE . '/Policies'),
        ];

        foreach ($layers as $layer => $exists) {
            self::assertTrue($exists, "Layer/dir '{$layer}' must exist");
        }
    }

    /* ================================================================== */
    /*  Helpers                                                            */
    /* ================================================================== */

    /** @return list<string> */
    private function getAllPhpFiles(string $dir): array
    {
        $result = [];
        $items  = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($dir, \FilesystemIterator::SKIP_DOTS),
        );

        foreach ($items as $item) {
            if ($item->isFile() && $item->getExtension() === 'php') {
                $result[] = $item->getPathname();
            }
        }

        return $result;
    }
}
