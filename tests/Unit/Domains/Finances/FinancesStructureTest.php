<?php

declare(strict_types=1);

namespace Tests\Unit\Domains\Finances;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use ReflectionClass;

/**
 * =================================================================
 *  9-СЛОЙНАЯ АРХИТЕКТУРА — STRUCTURAL COMPLIANCE TEST
 *  Модуль: Finances
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
 */
final class FinancesStructureTest extends TestCase
{
    private const BASE = __DIR__ . '/../../../../app/Domains/Finances';

    /* ================================================================== */
    /*  Layer 1 — Models                                                   */
    /* ================================================================== */

    #[Test]
    public function layer1_model_directory_exists(): void
    {
        self::assertDirectoryExists(self::BASE . '/Models');
    }

    #[Test]
    public function layer1_finance_record_model_exists(): void
    {
        self::assertFileExists(self::BASE . '/Models/FinanceRecord.php');
    }

    #[Test]
    public function layer1_model_is_final(): void
    {
        $ref = new ReflectionClass(\App\Domains\Finances\Models\FinanceRecord::class);
        self::assertTrue($ref->isFinal(), 'FinanceRecord model must be final');
    }

    /* ================================================================== */
    /*  Layer 2 — DTOs                                                     */
    /* ================================================================== */

    /** @return list<array{string, string}> */
    public static function dtosProvider(): array
    {
        return [
            ['CreateBudgetDto',       'App\Domains\Finances\DTOs\CreateBudgetDto'],
            ['CreateReportDto',       'App\Domains\Finances\DTOs\CreateReportDto'],
            ['CreateTransactionDto',  'App\Domains\Finances\DTOs\CreateTransactionDto'],
            ['SearchBudgetDto',       'App\Domains\Finances\DTOs\SearchBudgetDto'],
            ['SearchReportDto',       'App\Domains\Finances\DTOs\SearchReportDto'],
            ['SearchTransactionDto',  'App\Domains\Finances\DTOs\SearchTransactionDto'],
        ];
    }

    #[Test]
    #[DataProvider('dtosProvider')]
    public function layer2_dto_file_exists(string $name, string $fqcn): void
    {
        self::assertFileExists(self::BASE . "/DTOs/{$name}.php", "Layer 2 — DTO {$name} must exist");
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

    #[Test]
    public function layer3_services_directory_exists(): void
    {
        self::assertDirectoryExists(self::BASE . '/Services');
        self::assertDirectoryExists(self::BASE . '/Services/AI');
    }

    #[Test]
    public function layer3_main_service_exists(): void
    {
        self::assertFileExists(self::BASE . '/Services/FinancesService.php');
    }

    #[Test]
    public function layer3_main_service_is_final_readonly(): void
    {
        $ref = new ReflectionClass(\App\Domains\Finances\Services\FinancesService::class);
        self::assertTrue($ref->isFinal(), 'FinancesService must be final');
        self::assertTrue($ref->isReadOnly(), 'FinancesService must be readonly');
    }

    #[Test]
    public function layer3_ai_constructor_exists(): void
    {
        self::assertFileExists(
            self::BASE . '/Services/AI/FinancialAdvisorConstructorService.php',
            'CANON: AI Constructor MANDATORY for every vertical',
        );
    }

    #[Test]
    public function layer3_ai_constructor_is_final_readonly(): void
    {
        $ref = new ReflectionClass(\App\Domains\Finances\Services\AI\FinancialAdvisorConstructorService::class);
        self::assertTrue($ref->isFinal(), 'AI Constructor must be final');
        self::assertTrue($ref->isReadOnly(), 'AI Constructor must be readonly');
    }

    /* ================================================================== */
    /*  Layer 4 — Requests                                                 */
    /* ================================================================== */

    /** @return list<array{string}> */
    public static function requestsProvider(): array
    {
        return [
            ['StoreFinanceRecordRequest'],
            ['UpdateFinanceRecordRequest'],
        ];
    }

    #[Test]
    public function layer4_requests_directory_exists(): void
    {
        self::assertDirectoryExists(self::BASE . '/Http/Requests');
    }

    #[Test]
    #[DataProvider('requestsProvider')]
    public function layer4_request_file_exists(string $name): void
    {
        self::assertFileExists(
            self::BASE . "/Http/Requests/{$name}.php",
            "Layer 4 — Request {$name} must exist",
        );
    }

    #[Test]
    #[DataProvider('requestsProvider')]
    public function layer4_request_is_final(string $name): void
    {
        $fqcn = "App\\Domains\\Finances\\Http\\Requests\\{$name}";
        self::assertTrue((new ReflectionClass($fqcn))->isFinal(), "Request {$name} must be final");
    }

    /* ================================================================== */
    /*  Layer 5 — Resources (API JsonResource)                             */
    /* ================================================================== */

    #[Test]
    public function layer5_resources_directory_exists(): void
    {
        self::assertDirectoryExists(self::BASE . '/Resources');
    }

    #[Test]
    public function layer5_finance_record_resource_exists(): void
    {
        self::assertFileExists(self::BASE . '/Resources/FinanceRecordResource.php');
    }

    /* ================================================================== */
    /*  Layer 6 — Events                                                   */
    /* ================================================================== */

    /** @return list<array{string}> */
    public static function eventsProvider(): array
    {
        return [
            ['FinanceRecordCreated'],
            ['FinanceRecordUpdated'],
        ];
    }

    #[Test]
    public function layer6_events_directory_exists(): void
    {
        self::assertDirectoryExists(self::BASE . '/Events');
    }

    #[Test]
    #[DataProvider('eventsProvider')]
    public function layer6_event_file_exists(string $name): void
    {
        self::assertFileExists(
            self::BASE . "/Events/{$name}.php",
            "Layer 6 — Event {$name} must exist",
        );
    }

    #[Test]
    #[DataProvider('eventsProvider')]
    public function layer6_event_is_final(string $name): void
    {
        $fqcn = "App\\Domains\\Finances\\Events\\{$name}";
        self::assertTrue((new ReflectionClass($fqcn))->isFinal(), "Event {$name} must be final");
    }

    /* ================================================================== */
    /*  Layer 7 — Listeners                                                */
    /* ================================================================== */

    /** @return list<array{string}> */
    public static function listenersProvider(): array
    {
        return [
            ['LogFinanceRecordCreated'],
            ['LogFinanceRecordUpdated'],
        ];
    }

    #[Test]
    public function layer7_listeners_directory_exists(): void
    {
        self::assertDirectoryExists(self::BASE . '/Listeners');
    }

    #[Test]
    #[DataProvider('listenersProvider')]
    public function layer7_listener_file_exists(string $name): void
    {
        self::assertFileExists(
            self::BASE . "/Listeners/{$name}.php",
            "Layer 7 — Listener {$name} must exist",
        );
    }

    #[Test]
    #[DataProvider('listenersProvider')]
    public function layer7_listener_is_final(string $name): void
    {
        $fqcn = "App\\Domains\\Finances\\Listeners\\{$name}";
        self::assertTrue((new ReflectionClass($fqcn))->isFinal(), "Listener {$name} must be final");
    }

    /* ================================================================== */
    /*  Layer 8 — Jobs                                                     */
    /* ================================================================== */

    #[Test]
    public function layer8_jobs_directory_exists(): void
    {
        self::assertDirectoryExists(self::BASE . '/Jobs');
    }

    #[Test]
    public function layer8_process_job_exists(): void
    {
        self::assertFileExists(self::BASE . '/Jobs/ProcessFinanceRecordJob.php');
    }

    #[Test]
    public function layer8_job_is_final(): void
    {
        $ref = new ReflectionClass(\App\Domains\Finances\Jobs\ProcessFinanceRecordJob::class);
        self::assertTrue($ref->isFinal(), 'ProcessFinanceRecordJob must be final');
    }

    /* ================================================================== */
    /*  Layer 9 — Filament                                                 */
    /* ================================================================== */

    #[Test]
    public function layer9_filament_directory_exists(): void
    {
        self::assertDirectoryExists(self::BASE . '/Presentation/Filament');
    }

    #[Test]
    public function layer9_filament_dashboard_page_exists(): void
    {
        self::assertFileExists(self::BASE . '/Presentation/Filament/Pages/FinanceDashboard.php');
    }

    #[Test]
    public function layer9_filament_widgets_exist(): void
    {
        self::assertFileExists(self::BASE . '/Presentation/Filament/Widgets/PayoutsOverview.php');
        self::assertFileExists(self::BASE . '/Presentation/Filament/Widgets/RevenueChart.php');
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
        self::assertFileExists(self::BASE . '/Enums/TransactionType.php');
        self::assertFileExists(self::BASE . '/Enums/PayoutStatus.php');
    }

    #[Test]
    public function exceptions_directory_exists(): void
    {
        self::assertDirectoryExists(self::BASE . '/Exceptions');
    }

    #[Test]
    public function exceptions_files_exist(): void
    {
        self::assertFileExists(self::BASE . '/Exceptions/FinanceOperationException.php');
    }

    #[Test]
    public function controllers_directory_exists(): void
    {
        self::assertDirectoryExists(self::BASE . '/Controllers');
    }

    #[Test]
    public function controllers_files_exist(): void
    {
        self::assertFileExists(self::BASE . '/Controllers/FinanceRecordController.php');
    }

    #[Test]
    public function controller_is_final(): void
    {
        $ref = new ReflectionClass(\App\Domains\Finances\Controllers\FinanceRecordController::class);
        self::assertTrue($ref->isFinal(), 'FinanceRecordController must be final');
    }

    #[Test]
    public function policies_directory_exists(): void
    {
        self::assertDirectoryExists(self::BASE . '/Policies');
    }

    #[Test]
    public function policies_files_exist(): void
    {
        self::assertFileExists(self::BASE . '/Policies/FinanceRecordPolicy.php');
    }

    /* ================================================================== */
    /*  CANON: strict_types in EVERY file                                  */
    /* ================================================================== */

    #[Test]
    public function all_php_files_have_strict_types(): void
    {
        $files = $this->getAllPhpFiles(self::BASE);
        self::assertNotEmpty($files, 'Finances module must have PHP files');

        $violations = [];
        foreach ($files as $file) {
            $content = (string) file_get_contents($file);
            if (!str_contains($content, 'declare(strict_types=1)')) {
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
    /*  CANON: correlation_id in services                                   */
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
    /*  9-Layer Summary                                                    */
    /* ================================================================== */

    #[Test]
    public function nine_layer_compliance_summary(): void
    {
        $layers = [
            '1-Models'      => is_dir(self::BASE . '/Models'),
            '2-DTOs'        => is_dir(self::BASE . '/DTOs'),
            '3-Services'    => is_dir(self::BASE . '/Services'),
            '3-AI'          => is_dir(self::BASE . '/Services/AI'),
            '4-Requests'    => is_dir(self::BASE . '/Http/Requests'),
            '5-Resources'   => is_dir(self::BASE . '/Resources'),
            '6-Events'      => is_dir(self::BASE . '/Events'),
            '7-Listeners'   => is_dir(self::BASE . '/Listeners'),
            '8-Jobs'        => is_dir(self::BASE . '/Jobs'),
            '9-Filament'    => is_dir(self::BASE . '/Presentation/Filament'),
            'Enums'         => is_dir(self::BASE . '/Enums'),
            'Exceptions'    => is_dir(self::BASE . '/Exceptions'),
            'Controllers'   => is_dir(self::BASE . '/Controllers'),
            'Policies'      => is_dir(self::BASE . '/Policies'),
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
