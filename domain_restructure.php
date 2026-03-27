<?php
declare(strict_types=1);

/**
 * DOMAIN RESTRUCTURE SCRIPT 2026
 * Жёсткая реструктуризация app/Domains/ по каноническому списку вертикалей.
 * 
 * Алгоритм:
 * 1. Перемещает файлы из запрещённых папок в подпапки разрешённых вертикалей
 * 2. Обновляет namespace в перемещённых файлах
 * 3. Обновляет ВСЕ use-ссылки во всём проекте
 * 4. Удаляет пустые исходные папки
 */

$basePath = __DIR__;
$domainsPath = $basePath . '/app/Domains';

// === МАППИНГ: исходная папка => целевая вертикаль ===
// При перемещении используется стратегия SUBDIR:
// AcademicTutoring/* => Education/AcademicTutoring/*
// Namespace: App\Domains\AcademicTutoring\... => App\Domains\Education\AcademicTutoring\...
$mapping = [
    // → Education
    'AcademicTutoring'          => 'Education',
    'BusinessTraining'          => 'Education',
    'Courses'                   => 'Education',
    'DrivingSchools'            => 'Education',
    'Kids'                      => 'Education',
    'KidsCenters'               => 'Education',
    'LanguageLearning'          => 'Education',
    'LanguageTutoring'          => 'Education',
    'Tutoring'                  => 'Education',

    // → Consulting
    'Accounting'                => 'Consulting',
    'AccountingServices'        => 'Consulting',
    'AI'                        => 'Consulting',
    'Analytics'                 => 'Consulting',
    'ArtificialIntelligence'    => 'Consulting',
    'BiotechConsulting'         => 'Consulting',
    'BlockchainConsulting'      => 'Consulting',
    'BrandStrategy'             => 'Consulting',
    'BusinessConsulting'        => 'Consulting',
    'BusinessConsultingV2'      => 'Consulting',
    'BusinessModelInnovation'   => 'Consulting',
    'BusinessValuation'         => 'Consulting',
    'ChangeManagement'          => 'Consulting',
    'CloudArchitecture'         => 'Consulting',
    'CloudStorage'              => 'Consulting',
    'CompetitiveIntelligence'   => 'Consulting',
    'ConsultingFirm'            => 'Consulting',
    'ContentMarketing'          => 'Consulting',
    'ContinuousImprovement'     => 'Consulting',
    'CustomerRetention'         => 'Consulting',
    'CybersecurityConsulting'   => 'Consulting',
    'DataAnalytics'             => 'Consulting',
    'DeepLearning'              => 'Consulting',
    'DigitalMarketing'          => 'Consulting',
    'DigitalTransformation'     => 'Consulting',
    'EnergyInnovation'          => 'Consulting',
    'Finances'                  => 'Consulting',
    'FinancialPlanning'         => 'Consulting',
    'GeneticEngineering'        => 'Consulting',
    'HR'                        => 'Consulting',
    'HRConsulting'              => 'Consulting',
    'HumanResourcesConsulting'  => 'Consulting',
    'InnovationManagement'      => 'Consulting',
    'InternetServices'          => 'Consulting',
    'IoTConsulting'             => 'Consulting',
    'KnowledgeManagement'       => 'Consulting',
    'LeanManufacturing'         => 'Consulting',
    'MarketExpansion'           => 'Consulting',
    'MarketingConsultancy'      => 'Consulting',
    'MaterialsScience'          => 'Consulting',
    'MolecularBiology'          => 'Consulting',
    'NanotechConsulting'        => 'Consulting',
    'NeurotechConsulting'       => 'Consulting',
    'OperationsManagement'      => 'Consulting',
    'OptimizationServices'      => 'Consulting',
    'OrganizationalDevelopment' => 'Consulting',
    'PerformanceConsulting'     => 'Consulting',
    'ProcessImprovement'        => 'Consulting',
    'ProductDevelopment'        => 'Consulting',
    'ProfessionalServices'      => 'Consulting',
    'QualityAssurance'          => 'Consulting',
    'QualityControl'            => 'Consulting',
    'QuantumComputing'          => 'Consulting',
    'ResearchDevelopment'       => 'Consulting',
    'RoboticsSystems'           => 'Consulting',
    'SecurityConsulting'        => 'Consulting',
    'SEOServices'               => 'Consulting',
    'SpaceConsulting'           => 'Consulting',
    'StrategicConsulting'       => 'Consulting',
    'StrategicPlanning'         => 'Consulting',
    'SuccessionPlanning'        => 'Consulting',
    'SustainabilityConsulting'  => 'Consulting',
    'TalentAcquisition'         => 'Consulting',

    // → Legal
    'ComplianceConsulting'      => 'Legal',
    'ConflictResolution'        => 'Legal',
    'DataPrivacy'               => 'Legal',
    'LegalConsulting'           => 'Legal',
    'LegalServices'             => 'Legal',
    'TaxConsulting'             => 'Legal',

    // → Auto
    'AutonomousVehicles'        => 'Auto',
    'Cars'                      => 'Auto',
    'CarSales'                  => 'Auto',
    'CarWashing'                => 'Auto',
    'VehicleDealing'            => 'Auto',

    // → Beauty
    'BathsSaunas'               => 'Beauty',
    'BeautyServices'            => 'Beauty',
    'Cosmetics'                 => 'Beauty',
    'MassageTherapy'            => 'Beauty',
    'SpaWellness'               => 'Beauty',
    'Wellness'                  => 'Beauty',

    // → Food
    'Bars'                      => 'Food',
    'Beverages'                 => 'Food',
    'CoffeeShops'               => 'Food',
    'Grocery'                   => 'Food',
    'HealthyFood'               => 'Food',
    'ReadyMeals'                => 'Food',
    'TeaHouses'                 => 'Food',

    // → Hotels
    'HotelManagement'           => 'Hotels',
    'Lodging'                   => 'Hotels',

    // → FarmDirect
    'Agro'                      => 'FarmDirect',
    'FreshProduce'              => 'FarmDirect',

    // → ConstructionAndRepair (создаётся заново)
    'AdvancedManufacturing'     => 'ConstructionAndRepair',
    'ArchitectureServices'      => 'ConstructionAndRepair',
    'Construction'              => 'ConstructionAndRepair',
    'ConstructionMaterials'     => 'ConstructionAndRepair',
    'EngineeringConsulting'     => 'ConstructionAndRepair',

    // → Insurance
    'AssuranceServices'         => 'Insurance',
    'InsuranceServices'         => 'Insurance',
    'RiskManagement'            => 'Insurance',

    // → PersonalDevelopment
    'AstrologicalServices'      => 'PersonalDevelopment',
    'CareerCounseling'          => 'PersonalDevelopment',
    'CoachingServices'          => 'PersonalDevelopment',
    'ExecutiveCoaching'         => 'PersonalDevelopment',
    'LeadershipDevelopment'     => 'PersonalDevelopment',
    'LifeCoaching'              => 'PersonalDevelopment',

    // → Collectibles (создаётся заново)
    'AuctionHouses'             => 'Collectibles',

    // → HomeServices
    'Babysitting'               => 'HomeServices',
    'TechSupport'               => 'HomeServices',

    // → Sports
    'Billiards'                 => 'Sports',
    'DanceInstructor'           => 'Sports',
    'DanceStudios'              => 'Sports',
    'Fitness'                   => 'Sports',
    'PersonalTraining'          => 'Sports',
    'SportingGoods'             => 'Sports',

    // → HobbyAndCraft (создаётся заново)
    'BoardGames'                => 'HobbyAndCraft',
    'Hobby'                     => 'HobbyAndCraft',

    // → BooksAndLiterature (создаётся заново)
    'Books'                     => 'BooksAndLiterature',

    // → Freelance
    'Copywriting'               => 'Freelance',
    'SoftwareDevelopment'       => 'Freelance',
    'TranslationServices'       => 'Freelance',
    'WritingServices'           => 'Freelance',

    // → Art (создаётся заново)
    'ArtisticServices'          => 'Art',
    'ContentProduction'         => 'Art',
    'CreativeAgency'            => 'Art',
    'GraphicsDesign'            => 'Art',
    'IllustrationDesign'        => 'Art',
    'PodcastProduction'         => 'Art',
    'ThreeD'                    => 'Art',
    'UXDesign'                  => 'Art',
    'VideoEditing'              => 'Art',
    'WebDesign'                 => 'Art',

    // → Photography
    'PhotographyServices'       => 'Photography',
    'VideoProduction'           => 'Photography',

    // → Flowers
    'FlowerDelivery'            => 'Flowers',
    'GiftDelivery'              => 'Flowers',

    // → PartySupplies
    'Gifts'                     => 'PartySupplies',

    // → Gardening
    'GardenServices'            => 'Gardening',

    // → HouseholdGoods (создаётся заново)
    'HomeAppliance'             => 'HouseholdGoods',

    // → Luxury
    'Jewelry'                   => 'Luxury',

    // → EventPlanning
    'Entertainment'             => 'EventPlanning',
    'EventHalls'                => 'EventPlanning',
    'Events'                    => 'EventPlanning',
    'Karaoke'                   => 'EventPlanning',
    'TeamBuilding'              => 'EventPlanning',

    // → Tickets
    'EntertainmentBooking'      => 'Tickets',

    // → Fashion
    'FashionRetail'             => 'Fashion',
    'PersonalShopping'          => 'Fashion',
    'PersonalStyling'           => 'Fashion',

    // → Furniture
    'InteriorDesign'            => 'Furniture',

    // → Medical
    'Dentistry'                 => 'Medical',
    'MedicalHealthcare'         => 'Medical',
    'NursingServices'           => 'Medical',
    'Psychology'                => 'Medical',

    // → Pharmacy
    'MedicalSupplies'           => 'Pharmacy',

    // → Logistics
    'LogisticsConsulting'       => 'Logistics',
    'MovingServices'            => 'Logistics',
    'SupplierRelationship'      => 'Logistics',
    'SupplyChainFinance'        => 'Logistics',
    'SupplyChainOptimization'   => 'Logistics',
    'TradeServices'             => 'Logistics',
    'VendorManagement'          => 'Logistics',
    'WarehouseRentals'          => 'Logistics',

    // → Pet
    'PetServices'               => 'Pet',
    'PetSitting'                => 'Pet',

    // → RealEstate
    'OfficeRentals'             => 'RealEstate',
    'ShopRentals'               => 'RealEstate',

    // → Travel
    'TravelTourism'             => 'Travel',

    // → MusicAndInstruments (создаётся заново)
    'Music'                     => 'MusicAndInstruments',
    'MusicalInstruments'        => 'MusicAndInstruments',
    'MusicProduction'           => 'MusicAndInstruments',

    // → CleaningServices (создаётся заново)
    'Cleaning'                  => 'CleaningServices',
    'Laundry'                   => 'CleaningServices',

    // → ToysAndGames (создаётся заново)
    'Toys'                      => 'ToysAndGames',
    'ToysKids'                  => 'ToysAndGames',
];

// Папки, которые нужно уточнить у пользователя — пропускаем
$skipFolders = [
    'Appointments', 'Bloggers', 'Channels', 'Chat', 'Common',
    'Ritual', 'Shop', 'Social', 'Vapes', 'Vault',
];

// Статистика
$stats = [
    'moved_files'        => 0,
    'fixed_ns_in_moved'  => 0,
    'deleted_folders'    => 0,
    'errors'             => [],
];

// Накапливаем все namespace-замены для project-wide обновления
// Формат: 'App\\Domains\\OldName' => 'App\\Domains\\NewName\\OldName'
$nsReplacements = [];

echo "\n=== DOMAIN RESTRUCTURE 2026 ===\n";
echo "Базовый путь: $basePath\n";
echo "Папок для перемещения: " . count($mapping) . "\n\n";

// ========== ШАГ 1: Перемещаем файлы ==========
echo "--- ШАГ 1: Перемещение файлов ---\n";

foreach ($mapping as $source => $target) {
    $sourcePath = $domainsPath . DIRECTORY_SEPARATOR . $source;

    if (!is_dir($sourcePath)) {
        // Папка уже не существует — пропускаем
        continue;
    }

    $targetBase = $domainsPath . DIRECTORY_SEPARATOR . $target . DIRECTORY_SEPARATOR . $source;

    // Формируем замены namespace для этого модуля
    $oldNsBase = 'App\\Domains\\' . $source;
    $newNsBase = 'App\\Domains\\' . $target . '\\' . $source;
    $nsReplacements[$oldNsBase] = $newNsBase;

    // Итерируемся по всем файлам в исходной папке
    $iterator = new RecursiveIteratorIterator(
        new RecursiveDirectoryIterator($sourcePath, RecursiveDirectoryIterator::SKIP_DOTS),
        RecursiveIteratorIterator::SELF_FIRST
    );

    $movedInThis = 0;

    foreach ($iterator as $item) {
        if (!$item->isFile()) {
            continue;
        }

        $relativeToSource = ltrim(
            str_replace($sourcePath, '', $item->getPathname()),
            DIRECTORY_SEPARATOR
        );

        // Целевой путь: Domains/Target/Source/relative
        $targetFilePath = $targetBase . DIRECTORY_SEPARATOR . $relativeToSource;
        $targetDir      = dirname($targetFilePath);

        if (!is_dir($targetDir)) {
            mkdir($targetDir, 0755, true);
        }

        // Читаем содержимое
        $content = file_get_contents($item->getPathname());

        // Обновляем namespace и use в САМОМ файле
        if ($item->getExtension() === 'php') {
            $content = str_replace($oldNsBase, $newNsBase, $content);
        }

        // Записываем в новое место
        if (file_put_contents($targetFilePath, $content) !== false) {
            $stats['moved_files']++;
            $movedInThis++;
            if ($item->getExtension() === 'php') {
                $stats['fixed_ns_in_moved']++;
            }
        } else {
            $stats['errors'][] = "Не удалось записать: $targetFilePath";
        }
    }

    echo "  [$source] => [$target] ($movedInThis файлов)\n";
}

echo "\nПеремещено файлов: {$stats['moved_files']}\n";
echo "Обновлено namespace в перемещённых файлах: {$stats['fixed_ns_in_moved']}\n\n";

// ========== ШАГ 2: Project-wide namespace update ==========
echo "--- ШАГ 2: Project-wide namespace update ---\n";
echo "Собираем список PHP-файлов проекта...\n";

// Сортируем замены по длине строки (длиннее вперёд), чтобы избежать partial match
uksort($nsReplacements, static function (string $a, string $b): int {
    return strlen($b) - strlen($a);
});

// Папки для сканирования
$scanDirs = [
    $basePath . '/app',
    $basePath . '/tests',
    $basePath . '/database',
    $basePath . '/routes',
    $basePath . '/config',
];

$totalPhpFiles   = 0;
$updatedFiles    = 0;

foreach ($scanDirs as $scanDir) {
    if (!is_dir($scanDir)) {
        continue;
    }

    $phpIterator = new RecursiveIteratorIterator(
        new RecursiveDirectoryIterator($scanDir, RecursiveDirectoryIterator::SKIP_DOTS)
    );

    foreach ($phpIterator as $phpFile) {
        if (!$phpFile->isFile() || $phpFile->getExtension() !== 'php') {
            continue;
        }

        $totalPhpFiles++;
        $content    = file_get_contents($phpFile->getPathname());
        $newContent = str_replace(
            array_keys($nsReplacements),
            array_values($nsReplacements),
            $content
        );

        if ($newContent !== $content) {
            file_put_contents($phpFile->getPathname(), $newContent);
            $updatedFiles++;
        }
    }
}

echo "Просканировано PHP-файлов: $totalPhpFiles\n";
echo "Обновлено ссылок в проекте: $updatedFiles\n\n";

// ========== ШАГ 3: Удаляем пустые исходные папки ==========
echo "--- ШАГ 3: Удаление исходных папок ---\n";

function removeDirectoryRecursive(string $dir): bool
{
    if (!is_dir($dir)) {
        return false;
    }
    $items = new RecursiveIteratorIterator(
        new RecursiveDirectoryIterator($dir, RecursiveDirectoryIterator::SKIP_DOTS),
        RecursiveIteratorIterator::CHILD_FIRST
    );
    foreach ($items as $item) {
        if ($item->isDir()) {
            rmdir($item->getPathname());
        } else {
            unlink($item->getPathname());
        }
    }
    return rmdir($dir);
}

foreach (array_keys($mapping) as $source) {
    $sourcePath = $domainsPath . DIRECTORY_SEPARATOR . $source;
    if (is_dir($sourcePath)) {
        if (removeDirectoryRecursive($sourcePath)) {
            $stats['deleted_folders']++;
            echo "  Удалено: $source\n";
        } else {
            $stats['errors'][] = "Не удалось удалить: $source";
        }
    }
}

echo "\nУдалено папок: {$stats['deleted_folders']}\n\n";

// ========== ФИНАЛЬНЫЙ ОТЧЁТ ==========
echo "=== ФИНАЛЬНЫЙ ОТЧЁТ ===\n\n";
echo "Перемещено файлов: {$stats['moved_files']}\n";
echo "Исправлено namespace: {$stats['fixed_ns_in_moved']}\n";
echo "Обновлено файлов (project-wide): $updatedFiles\n";
echo "Удалено папок: {$stats['deleted_folders']}\n";

echo "\n--- Оставшиеся папки в app/Domains/ ---\n";
$remaining = scandir($domainsPath);
$domainFolders = [];
foreach ($remaining as $item) {
    if ($item === '.' || $item === '..') {
        continue;
    }
    if (is_dir($domainsPath . DIRECTORY_SEPARATOR . $item)) {
        $domainFolders[] = $item;
    }
}
sort($domainFolders);
foreach ($domainFolders as $folder) {
    $phpCount = 0;
    $iter = new RecursiveIteratorIterator(
        new RecursiveDirectoryIterator($domainsPath . '/' . $folder, RecursiveDirectoryIterator::SKIP_DOTS)
    );
    foreach ($iter as $f) {
        if ($f->isFile() && $f->getExtension() === 'php') {
            $phpCount++;
        }
    }
    echo "  $folder ($phpCount PHP файлов)\n";
}

echo "\n--- Пропущенные папки (требуют решения пользователя) ---\n";
foreach ($skipFolders as $skip) {
    $skipPath = $domainsPath . DIRECTORY_SEPARATOR . $skip;
    if (is_dir($skipPath)) {
        $cnt = 0;
        $si = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($skipPath, RecursiveDirectoryIterator::SKIP_DOTS)
        );
        foreach ($si as $sf) {
            if ($sf->isFile() && $sf->getExtension() === 'php') {
                $cnt++;
            }
        }
        echo "  ❓ $skip ($cnt файлов) — куда переместить?\n";
    }
}

if (!empty($stats['errors'])) {
    echo "\n--- ОШИБКИ ---\n";
    foreach ($stats['errors'] as $err) {
        echo "  ❌ $err\n";
    }
}

echo "\n=== ГОТОВО ===\n";
