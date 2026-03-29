<?php
declare(strict_types=1);

/**
 * FINAL VERIFICATION SCRIPT
 * Проверка что консолидация выполнена правильно
 */

$DOMAINS_PATH = __DIR__ . '/app/Domains';

$TARGET_VERTICALS = [
    'Auto', 'Beauty', 'Education', 'Food', 'Hotels', 'ShortTermRentals',
    'RealEstate', 'Travel', 'Taxi', 'Logistics', 'Medical', 'Pet', 'Fashion',
    'Furniture', 'Electronics', 'Sports', 'Tickets', 'EventPlanning',
    'Photography', 'Pharmacy', 'HomeServices', 'Freelance', 'Consulting',
    'Legal', 'Insurance', 'Flowers', 'ConstructionAndRepair', 'Gardening',
    'SportsNutrition', 'VeganProducts', 'Confectionery', 'MeatShops',
    'OfficeCatering', 'FarmDirect', 'BooksAndLiterature', 'ToysAndGames',
    'HobbyAndCraft', 'CleaningServices', 'CarRental', 'MusicAndInstruments',
    'Art', 'Collectibles', 'HouseholdGoods', 'PartySupplies',
    'Veterinary', 'WeddingPlanning', 'Luxury', 'PersonalDevelopment',
    // Stationery was empty and deleted
];

// Получить текущие папки
$currentFolders = array_filter(
    scandir($DOMAINS_PATH),
    fn($item) => is_dir("$DOMAINS_PATH/$item") && !in_array($item, ['.', '..'])
);

sort($currentFolders);

echo "════════════════════════════════════════════════════════════════\n";
echo "FINAL VERIFICATION - app/Domains/ CONSOLIDATION\n";
echo "════════════════════════════════════════════════════════════════\n\n";

// 1. Проверить целевые вертикали
echo "[1] CHECKING TARGET VERTICALS\n";
echo "─────────────────────────────────\n";

sort($TARGET_VERTICALS);
$foundTargets = array_intersect($TARGET_VERTICALS, $currentFolders);
$missingTargets = array_diff($TARGET_VERTICALS, $currentFolders);

echo "Found: " . count($foundTargets) . " / " . count($TARGET_VERTICALS) . "\n";

if (!empty($missingTargets)) {
    echo "\n⚠️  Missing target verticals:\n";
    foreach ($missingTargets as $vertical) {
        echo "  • $vertical\n";
    }
} else {
    echo "✅ All target verticals present\n";
}

// 2. Проверить системные папки
echo "\n[2] CHECKING SYSTEM FOLDERS\n";
echo "─────────────────────────────────\n";

$systemFolders = ['Common', 'Marketplace'];
$foundSystem = array_intersect($systemFolders, $currentFolders);

echo "Expected: " . count($systemFolders) . "\n";
echo "Found: " . count($foundSystem) . "\n";

if (!empty($foundSystem)) {
    foreach ($foundSystem as $folder) {
        echo "  ✅ $folder\n";
    }
}

// 3. Проверить нежелательные папки
echo "\n[3] CHECKING FOR UNWANTED FOLDERS\n";
echo "─────────────────────────────────\n";

$unwantedFolders = ['Content', 'EventManagement', 'RitualServices', 'Vapes', 'Stationery'];
$foundUnwanted = array_intersect($unwantedFolders, $currentFolders);

if (empty($foundUnwanted)) {
    echo "✅ No unwanted folders found\n";
} else {
    echo "❌ Found unwanted folders:\n";
    foreach ($foundUnwanted as $folder) {
        echo "  • $folder\n";
    }
}

// 4. Дублирующиеся папки
echo "\n[4] CHECKING FOR DUPLICATES\n";
echo "─────────────────────────────────\n";

$duplicates = array_filter(
    $currentFolders,
    fn($folder) => !in_array($folder, $TARGET_VERTICALS) && 
                   !in_array($folder, $systemFolders)
);

if (empty($duplicates)) {
    echo "✅ No duplicate/extra folders\n";
} else {
    echo "⚠️  Extra folders found:\n";
    foreach ($duplicates as $folder) {
        echo "  • $folder\n";
    }
}

// 5. Пустые папки
echo "\n[5] CHECKING FOR EMPTY FOLDERS\n";
echo "─────────────────────────────────\n";

$emptyCount = 0;
foreach ($currentFolders as $folder) {
    $path = "$DOMAINS_PATH/$folder";
    $count = countFilesRecursive($path);

    if ($count === 0) {
        echo "❌ Empty: $folder\n";
        $emptyCount++;
    }
}

if ($emptyCount === 0) {
    echo "✅ No empty folders\n";
}

// 6. Итоговая статистика
echo "\n════════════════════════════════════════════════════════════════\n";
echo "FINAL STATISTICS\n";
echo "════════════════════════════════════════════════════════════════\n";

$totalFiles = 0;
$maxFiles = 0;
$maxFolder = '';
$minFiles = PHP_INT_MAX;
$minFolder = '';

foreach ($currentFolders as $folder) {
    $count = countFilesRecursive("$DOMAINS_PATH/$folder");
    $totalFiles += $count;

    if ($count > $maxFiles) {
        $maxFiles = $count;
        $maxFolder = $folder;
    }

    if ($count > 0 && $count < $minFiles) {
        $minFiles = $count;
        $minFolder = $folder;
    }
}

echo "\nTotal folders: " . count($currentFolders) . "\n";
echo "Total files: $totalFiles\n";
echo "Average files per folder: " . round($totalFiles / count($currentFolders), 1) . "\n";
echo "Largest folder: $maxFolder ($maxFiles files)\n";
echo "Smallest folder (non-empty): $minFolder ($minFiles files)\n";

// 7. Финальный вердикт
echo "\n════════════════════════════════════════════════════════════════\n";

$status = 'PASS';

if (!empty($missingTargets)) {
    $status = 'FAIL';
}

if (!empty($foundUnwanted)) {
    $status = 'FAIL';
}

if ($emptyCount > 0) {
    $status = 'FAIL';
}

if ($status === 'PASS') {
    echo "✅ VERIFICATION PASSED - CONSOLIDATION SUCCESSFUL\n";
} else {
    echo "❌ VERIFICATION FAILED - ISSUES FOUND\n";
}

echo "════════════════════════════════════════════════════════════════\n";

// ФУНКЦИИ
function countFilesRecursive($dir) {
    if (!is_dir($dir)) {
        return 0;
    }

    $count = 0;
    $iterator = new RecursiveDirectoryIterator($dir, RecursiveDirectoryIterator::SKIP_DOTS);

    foreach (new RecursiveIteratorIterator($iterator) as $file) {
        if ($file->isFile()) {
            $count++;
        }
    }

    return $count;
}
?>
