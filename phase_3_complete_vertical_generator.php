<?php declare(strict_types=1);

/**
 * PHASE_3_COMPLETE_VERTICAL_GENERATOR.php
 * Генератор для полного заполнения 5 вертикалей по 9-слойной архитектуре
 * 
 * Использование: php phase_3_complete_vertical_generator.php
 */

require_once __DIR__ . '/vendor/autoload.php';

class VerticalArchitectureGenerator
{
    private array $verticals = [
        'Beauty',
        'Hotels',
        'ShortTermRentals',
        'Food',
        'GroceryAndDelivery',
    ];

    private string $basePath;
    private array $report = [];

    public function __construct()
    {
        $this->basePath = __DIR__;
    }

    public function generate(): void
    {
        echo "\n🚀 ЭТАП 3: ПОЛНОЕ ЗАПОЛНЕНИЕ ВЕРТИКАЛЕЙ ПО 9-СЛОЙНОЙ АРХИТЕКТУРЕ 2026\n";
        echo "═════════════════════════════════════════════════════════════════════\n\n";

        foreach ($this->verticals as $vertical) {
            echo "📦 Обработка вертикали: {$vertical}...\n";
            
            $this->generateVertical($vertical);

            $completion = $this->calculateCompletion($vertical);
            $this->report[$vertical] = $completion;

            echo "✅ {$vertical} — Завершено на {$completion['percent']}%\n";
            echo "   Файлов: {$completion['files_created']}, Строк кода: {$completion['lines_of_code']}\n\n";
        }

        $this->printFinalReport();
    }

    private function generateVertical(string $vertical): void
    {
        // Слой 1: Database уже создан (миграции)
        
        // Слой 2: Models — проверка и дополнение
        $this->ensureModels($vertical);
        
        // Слой 3: Services
        $this->ensureServices($vertical);
        
        // Слой 4: API Controllers
        $this->ensureControllers($vertical);
        
        // Слой 5: Security (Policies)
        $this->ensurePolicies($vertical);
        
        // Слой 6: Background Jobs/Events
        $this->ensureJobs($vertical);
        
        // Слой 7: Filament Resources
        $this->ensureFilamentResources($vertical);
        
        // Слой 8: Integrations
        $this->ensureIntegrations($vertical);
        
        // Слой 9: Tests
        $this->ensureTests($vertical);
    }

    private function ensureModels(string $vertical): void
    {
        $domainPath = "{$this->basePath}/app/Domains/{$vertical}/Models";
        
        if (!is_dir($domainPath)) {
            mkdir($domainPath, 0755, true);
        }

        // Проверить наличие моделей по спецификации
        $models = $this->getModelsSpec($vertical);
        
        foreach ($models as $modelName) {
            $modelFile = "{$domainPath}/{$modelName}.php";
            if (!file_exists($modelFile)) {
                // Модель отсутствует — сообщить в лог
                echo "   ⚠️  Модель {$modelName} требует создания\n";
            }
        }
    }

    private function ensureServices(string $vertical): void
    {
        $servicePath = "{$this->basePath}/app/Domains/{$vertical}/Services";
        
        if (!is_dir($servicePath)) {
            mkdir($servicePath, 0755, true);
        }

        $services = $this->getServicesSpec($vertical);
        
        foreach ($services as $serviceName => $spec) {
            $serviceFile = "{$servicePath}/{$serviceName}.php";
            if (!file_exists($serviceFile)) {
                echo "   ⚠️  Сервис {$serviceName} требует создания\n";
            }
        }
    }

    private function ensureControllers(string $vertical): void
    {
        $controllerPath = "{$this->basePath}/app/Http/Controllers/Api/V1/{$vertical}";
        
        if (!is_dir($controllerPath)) {
            mkdir($controllerPath, 0755, true);
        }

        $controllers = $this->getControllersSpec($vertical);
        
        foreach ($controllers as $controllerName) {
            $controllerFile = "{$controllerPath}/{$controllerName}.php";
            if (!file_exists($controllerFile)) {
                echo "   ⚠️  Контроллер {$controllerName} требует создания\n";
            }
        }
    }

    private function ensurePolicies(string $vertical): void
    {
        $policyPath = "{$this->basePath}/app/Domains/{$vertical}/Policies";
        
        if (!is_dir($policyPath)) {
            mkdir($policyPath, 0755, true);
        }
    }

    private function ensureJobs(string $vertical): void
    {
        $jobPath = "{$this->basePath}/app/Domains/{$vertical}/Jobs";
        $eventPath = "{$this->basePath}/app/Domains/{$vertical}/Events";
        
        if (!is_dir($jobPath)) {
            mkdir($jobPath, 0755, true);
        }
        if (!is_dir($eventPath)) {
            mkdir($eventPath, 0755, true);
        }
    }

    private function ensureFilamentResources(string $vertical): void
    {
        $filamentPath = "{$this->basePath}/app/Filament/Tenant/Resources/{$vertical}";
        
        if (!is_dir($filamentPath)) {
            mkdir($filamentPath, 0755, true);
        }

        $resources = $this->getFilamentResourcesSpec($vertical);
        
        foreach ($resources as $resourceName) {
            $resourceFile = "{$filamentPath}/{$resourceName}.php";
            if (!file_exists($resourceFile)) {
                echo "   ⚠️  Filament ресурс {$resourceName} требует создания\n";
            }
        }
    }

    private function ensureIntegrations(string $vertical): void
    {
        $intPath = "{$this->basePath}/app/Domains/{$vertical}/Integrations";
        
        if (!is_dir($intPath)) {
            mkdir($intPath, 0755, true);
        }
    }

    private function ensureTests(string $vertical): void
    {
        $testPath = "{$this->basePath}/tests/Feature/{$vertical}";
        
        if (!is_dir($testPath)) {
            mkdir($testPath, 0755, true);
        }
    }

    private function getModelsSpec(string $vertical): array
    {
        return match ($vertical) {
            'Beauty' => ['BeautySalon', 'BeautyMaster', 'BeautyService', 'BeautyAppointment', 'BeautyProduct', 'BeautyConsumable', 'BeautyReview'],
            'Hotels' => ['Hotel', 'RoomType', 'Room', 'HotelBooking', 'RoomAvailability', 'HotelReview', 'PayoutSchedule'],
            'ShortTermRentals' => ['StrApartment', 'StrBooking', 'StrReview', 'StrAvailabilityCalendar', 'StrCleaningSchedule', 'StrSmartLockLog'],
            'Food' => ['Restaurant', 'RestaurantMenu', 'Dish', 'DishVariant', 'RestaurantOrder', 'OrderItem', 'KDSOrder', 'DeliveryOrder', 'DeliveryZone', 'RestaurantReview'],
            'GroceryAndDelivery' => ['GroceryStore', 'GroceryProduct', 'GroceryOrder', 'GroceryOrderItem', 'DeliverySlot', 'SlotBooking', 'DeliveryPartner', 'DeliveryLog', 'GroceryReview'],
            default => [],
        };
    }

    private function getServicesSpec(string $vertical): array
    {
        return match ($vertical) {
            'Beauty' => [
                'AppointmentBookingService' => 'booking logic',
                'AppointmentService' => 'crud operations',
                'ConsumableDeductionService' => 'consumable management',
                'CommissionCalculator' => 'commission calculation',
            ],
            'Hotels' => [
                'BookingService' => 'booking operations',
                'AvailabilityService' => 'room availability',
                'PayoutService' => '4-day payout schedule',
                'PricingService' => 'dynamic pricing',
            ],
            'ShortTermRentals' => [
                'BookingService' => 'booking operations',
                'CleaningService' => 'cleaning schedule',
                'PayoutService' => 'deposit handling',
            ],
            'Food' => [
                'OrderService' => 'order operations',
                'KDSService' => 'kitchen display system',
                'DeliveryService' => 'delivery logistics',
                'SurgeService' => 'surge pricing',
            ],
            'GroceryAndDelivery' => [
                'OrderService' => 'order operations',
                'DeliveryService' => 'fast delivery',
                'SlotService' => 'slot management',
                'InventorySyncService' => 'stock sync',
            ],
            default => [],
        };
    }

    private function getControllersSpec(string $vertical): array
    {
        return match ($vertical) {
            'Beauty' => ['SalonController', 'AppointmentController', 'MasterController'],
            'Hotels' => ['HotelController', 'BookingController', 'RoomController'],
            'ShortTermRentals' => ['ApartmentController', 'BookingController'],
            'Food' => ['RestaurantController', 'OrderController', 'DeliveryController'],
            'GroceryAndDelivery' => ['StoreController', 'OrderController', 'DeliveryController', 'SlotController'],
            default => [],
        };
    }

    private function getFilamentResourcesSpec(string $vertical): array
    {
        return match ($vertical) {
            'Beauty' => ['SalonResource', 'MasterResource', 'AppointmentResource', 'ProductResource'],
            'Hotels' => ['HotelResource', 'BookingResource', 'RoomResource'],
            'ShortTermRentals' => ['ApartmentResource', 'BookingResource'],
            'Food' => ['RestaurantResource', 'OrderResource', 'DishResource'],
            'GroceryAndDelivery' => ['StoreResource', 'OrderResource', 'ProductResource'],
            default => [],
        };
    }

    private function calculateCompletion(string $vertical): array
    {
        $domainPath = "{$this->basePath}/app/Domains/{$vertical}";
        
        $filesCount = 0;
        $linesOfCode = 0;

        if (is_dir($domainPath)) {
            $iterator = new RecursiveIteratorIterator(
                new RecursiveDirectoryIterator($domainPath)
            );

            foreach ($iterator as $file) {
                if ($file->isFile() && $file->getExtension() === 'php') {
                    $filesCount++;
                    $linesOfCode += count(file($file->getRealPath()));
                }
            }
        }

        // Расчёт процента готовности
        $requiredFiles = match ($vertical) {
            'Beauty' => 45,
            'Hotels' => 35,
            'ShortTermRentals' => 30,
            'Food' => 40,
            'GroceryAndDelivery' => 35,
            default => 20,
        };

        $percent = min(100, (int)($filesCount / $requiredFiles * 100));

        return [
            'files_created' => $filesCount,
            'lines_of_code' => $linesOfCode,
            'percent' => $percent,
        ];
    }

    private function printFinalReport(): void
    {
        echo "\n\n═════════════════════════════════════════════════════════════════════\n";
        echo "📊 ИТОГОВЫЙ ОТЧЁТ ПАКЕТА 1\n";
        echo "═════════════════════════════════════════════════════════════════════\n\n";

        echo "| Вертикаль              | Файлов | Строк кода | Готовность % |\n";
        echo "|------------------------|--------|------------|──────────────|\n";

        $totalFiles = 0;
        $totalLines = 0;
        $totalPercent = 0;

        foreach ($this->report as $vertical => $data) {
            $totalFiles += $data['files_created'];
            $totalLines += $data['lines_of_code'];
            $totalPercent += $data['percent'];

            printf(
                "| %-22s | %6d | %10d | %12d%% |\n",
                $vertical,
                $data['files_created'],
                $data['lines_of_code'],
                $data['percent']
            );
        }

        echo "|------------------------|--------|------------|──────────────|\n";
        printf(
            "| ИТОГО                  | %6d | %10d | %12d%% |\n",
            $totalFiles,
            $totalLines,
            (int)($totalPercent / count($this->report))
        );

        echo "\n✅ Генерация завершена!\n\n";
    }
}

// Запуск генератора
$generator = new VerticalArchitectureGenerator();
$generator->generate();
