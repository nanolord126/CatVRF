<?php

declare(strict_types=1);

namespace Tests\Unit\Domains\Wallet;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use ReflectionClass;

/**
 * =================================================================
 *  9-СЛОЙНАЯ АРХИТЕКТУРА — STRUCTURAL COMPLIANCE TEST
 *  Модуль: Wallet
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
final class WalletStructureTest extends TestCase
{
    private const BASE = __DIR__ . '/../../../../app/Domains/Wallet';

    /* ================================================================== */
    /*  Layer 1 — Models                                                   */
    /* ================================================================== */

    #[Test]
    public function layer1_model_directory_exists(): void
    {
        self::assertDirectoryExists(self::BASE . '/Models');
    }

    #[Test]
    public function layer1_wallet_model_exists(): void
    {
        self::assertFileExists(self::BASE . '/Models/Wallet.php');
    }

    #[Test]
    public function layer1_model_is_final(): void
    {
        $ref = new ReflectionClass(\App\Domains\Wallet\Models\Wallet::class);
        self::assertTrue($ref->isFinal(), 'Wallet model must be final');
    }

    /* ================================================================== */
    /*  Layer 2 — DTOs                                                     */
    /* ================================================================== */

    /** @return list<array{string, string}> */
    public static function dtosProvider(): array
    {
        return [
            ['CreateTopUpDto',        'App\Domains\Wallet\DTOs\CreateTopUpDto'],
            ['CreateWithdrawalDto',   'App\Domains\Wallet\DTOs\CreateWithdrawalDto'],
            ['CreateTransactionDto',  'App\Domains\Wallet\DTOs\CreateTransactionDto'],
            ['SearchTopUpDto',        'App\Domains\Wallet\DTOs\SearchTopUpDto'],
            ['SearchTransactionDto',  'App\Domains\Wallet\DTOs\SearchTransactionDto'],
            ['SearchWithdrawalDto',   'App\Domains\Wallet\DTOs\SearchWithdrawalDto'],
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
        self::assertFileExists(self::BASE . '/Services/WalletService.php');
    }

    #[Test]
    public function layer3_main_service_is_final_readonly(): void
    {
        $ref = new ReflectionClass(\App\Domains\Wallet\Services\WalletService::class);
        self::assertTrue($ref->isFinal(), 'WalletService must be final');
        self::assertTrue($ref->isReadOnly(), 'WalletService must be readonly');
    }

    #[Test]
    public function layer3_ai_constructor_exists(): void
    {
        self::assertFileExists(
            self::BASE . '/Services/AI/WalletConstructorService.php',
            'CANON: AI Constructor MANDATORY for every vertical',
        );
    }

    #[Test]
    public function layer3_ai_constructor_is_final_readonly(): void
    {
        $ref = new ReflectionClass(\App\Domains\Wallet\Services\AI\WalletConstructorService::class);
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
            ['StoreWalletRequest'],
            ['TopUpWalletRequest'],
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
        $fqcn = "App\\Domains\\Wallet\\Http\\Requests\\{$name}";
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
    public function layer5_wallet_resource_exists(): void
    {
        self::assertFileExists(self::BASE . '/Resources/WalletResource.php');
    }

    /* ================================================================== */
    /*  Layer 6 — Events                                                   */
    /* ================================================================== */

    /** @return list<array{string}> */
    public static function eventsProvider(): array
    {
        return [
            ['WalletCreated'],
            ['WalletUpdated'],
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
        $fqcn = "App\\Domains\\Wallet\\Events\\{$name}";
        self::assertTrue((new ReflectionClass($fqcn))->isFinal(), "Event {$name} must be final");
    }

    /* ================================================================== */
    /*  Layer 7 — Listeners                                                */
    /* ================================================================== */

    /** @return list<array{string}> */
    public static function listenersProvider(): array
    {
        return [
            ['LogWalletCreated'],
            ['LogWalletUpdated'],
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
        $fqcn = "App\\Domains\\Wallet\\Listeners\\{$name}";
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
        self::assertFileExists(self::BASE . '/Jobs/ProcessWalletJob.php');
    }

    #[Test]
    public function layer8_job_is_final(): void
    {
        $ref = new ReflectionClass(\App\Domains\Wallet\Jobs\ProcessWalletJob::class);
        self::assertTrue($ref->isFinal(), 'ProcessWalletJob must be final');
    }

    /* ================================================================== */
    /*  Layer 9 — Filament                                                 */
    /* ================================================================== */

    #[Test]
    public function layer9_filament_directory_exists(): void
    {
        self::assertDirectoryExists(self::BASE . '/Filament');
        self::assertDirectoryExists(self::BASE . '/Filament/Resources');
    }

    #[Test]
    public function layer9_filament_resource_exists(): void
    {
        self::assertFileExists(self::BASE . '/Filament/Resources/WalletResource.php');
    }

    #[Test]
    public function layer9_filament_pages_exist(): void
    {
        $pages = [
            'CreateWallet.php',
            'EditWallet.php',
            'ListWallets.php',
        ];

        foreach ($pages as $page) {
            self::assertFileExists(
                self::BASE . "/Filament/Resources/WalletResource/Pages/{$page}",
                "Filament page {$page} must exist",
            );
        }
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
        self::assertFileExists(self::BASE . '/Enums/BalanceTransactionType.php');
    }

    #[Test]
    public function exceptions_directory_exists(): void
    {
        self::assertDirectoryExists(self::BASE . '/Exceptions');
    }

    #[Test]
    public function exceptions_files_exist(): void
    {
        self::assertFileExists(self::BASE . '/Exceptions/InsufficientBalanceException.php');
        self::assertFileExists(self::BASE . '/Exceptions/WalletOperationException.php');
    }

    #[Test]
    public function controllers_directory_exists(): void
    {
        self::assertDirectoryExists(self::BASE . '/Controllers');
    }

    #[Test]
    public function controllers_files_exist(): void
    {
        self::assertFileExists(self::BASE . '/Controllers/WalletController.php');
    }

    #[Test]
    public function controller_is_final(): void
    {
        $ref = new ReflectionClass(\App\Domains\Wallet\Controllers\WalletController::class);
        self::assertTrue($ref->isFinal(), 'WalletController must be final');
    }

    #[Test]
    public function policies_directory_exists(): void
    {
        self::assertDirectoryExists(self::BASE . '/Policies');
    }

    #[Test]
    public function policies_files_exist(): void
    {
        self::assertFileExists(self::BASE . '/Policies/WalletPolicy.php');
    }

    /* ================================================================== */
    /*  CANON: strict_types in EVERY file                                  */
    /* ================================================================== */

    #[Test]
    public function all_php_files_have_strict_types(): void
    {
        $files = $this->getAllPhpFiles(self::BASE);
        self::assertNotEmpty($files, 'Wallet module must have PHP files');

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
            '9-Filament'    => is_dir(self::BASE . '/Filament'),
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
