<?php
declare(strict_types=1);

/**
 * CONSOLIDATE DOMAINS — Мега-скрипт для объединения вертикалей в app/Domains/
 * 
 * Задачи:
 * 1. Определить целевые вертикали
 * 2. Найти лишние папки и файлы
 * 3. Переместить файлы в правильные вертикали
 * 4. Исправить namespaces
 * 5. Удалить пустые папки
 * 6. Показать отчёт
 */

$DOMAINS_PATH = __DIR__ . '/app/Domains';

// ===== ЦЕЛЕВЫЕ ВЕРТИКАЛИ (48 штук) =====
$TARGET_VERTICALS = [
    'Auto',
    'Beauty',
    'Education',
    'Food',
    'Hotels',
    'ShortTermRentals',
    'RealEstate',
    'Travel',
    'Taxi',
    'Logistics',
    'Medical',
    'Pet',
    'Fashion',
    'Furniture',
    'Electronics',
    'Sports',
    'Tickets',
    'EventPlanning',
    'Photography',
    'Pharmacy',
    'HomeServices',
    'Freelance',
    'Consulting',
    'Legal',
    'Insurance',
    'Flowers',
    'ConstructionAndRepair',
    'Gardening',
    'SportsNutrition',
    'VeganProducts',
    'Confectionery',
    'MeatShops',
    'OfficeCatering',
    'FarmDirect',
    'BooksAndLiterature',
    'ToysAndGames',
    'HobbyAndCraft',
    'CleaningServices',
    'CarRental',
    'MusicAndInstruments',
    'Art',
    'Collectibles',
    'HouseholdGoods',
    'Stationery',
    'PartySupplies',
    'Veterinary',
    'WeddingPlanning',
    'Luxury',
    'PersonalDevelopment',
];

// ===== МАППИНГ: лишние папки → целевые вертикали =====
$FOLDER_MAPPING = [
    'Content' => 'Education',           // Контент курсов
    'EventManagement' => 'EventPlanning',  // Управление событиями
    'RitualServices' => 'HomeServices', // Ритуальные услуги → домашние услуги
    'Vapes' => 'SportsNutrition',       // Вейпы → не целевая (удалить)
    'Marketplace' => null,              // Системная папка, не трогать
    'Common' => null,                   // Общие утилиты, не трогать
];

// ===== ЛОГИРОВАНИЕ =====
$stats = [
    'folders_analyzed' => 0,
    'folders_deleted' => 0,
    'folders_consolidated' => 0,
    'files_moved' => 0,
    'namespaces_fixed' => 0,
    'errors' => [],
    'actions' => [],
];

echo "═══════════════════════════════════════════════════════════\n";
echo "🔍 ЭТАП 0: АНАЛИЗ ТЕКУЩЕЙ СТРУКТУРЫ app/Domains/\n";
echo "═══════════════════════════════════════════════════════════\n\n";

// Получить все папки
$allFolders = array_filter(
    scandir($DOMAINS_PATH),
    fn($item) => is_dir("$DOMAINS_PATH/$item") && !in_array($item, ['.', '..'])
);

sort($allFolders);

$stats['folders_analyzed'] = count($allFolders);

echo "📁 Найдено папок: " . count($allFolders) . "\n\n";

// ===== ШАГ 1: ОПРЕДЕЛИТЬ, КАКИЕ ПАПКИ НУЖНО КОНСОЛИДИРОВАТЬ =====
echo "1️⃣  ОПРЕДЕЛЕНИЕ ПАПОК ДЛЯ КОНСОЛИДАЦИИ\n";
echo "─────────────────────────────────────────\n";

$foldersToConsolidate = [];
$foldersToKeep = [];
$foldersToDrop = [];

foreach ($allFolders as $folder) {
    $isTarget = in_array($folder, $TARGET_VERTICALS);
    $hasMapping = isset($FOLDER_MAPPING[$folder]);
    $shouldIgnore = in_array($folder, ['Marketplace', 'Common']);

    if ($shouldIgnore) {
        $foldersToKeep[] = $folder;
        echo "  ✅ $folder (системная папка, не трогать)\n";
    } elseif ($isTarget) {
        $foldersToKeep[] = $folder;
        echo "  ✅ $folder (целевая вертикаль)\n";
    } elseif ($hasMapping) {
        $target = $FOLDER_MAPPING[$folder];
        if ($target) {
            $foldersToConsolidate[$folder] = $target;
            echo "  🔀 $folder → $target\n";
        } else {
            $foldersToDrop[] = $folder;
            echo "  ❌ $folder (удалить)\n";
        }
    } else {
        $foldersToConsolidate[$folder] = guessTargetVertical($folder);
        echo "  🔀 $folder → " . $foldersToConsolidate[$folder] . " (автоопределение)\n";
    }
}

echo "\n✅ Папки для сохранения: " . count($foldersToKeep) . "\n";
echo "🔀 Папки для консолидации: " . count($foldersToConsolidate) . "\n";
echo "❌ Папки для удаления: " . count($foldersToDrop) . "\n\n";

// ===== ШАГ 2: КОНСОЛИДАЦИЯ ФАЙЛОВ =====
echo "2️⃣  КОНСОЛИДАЦИЯ ФАЙЛОВ\n";
echo "─────────────────────────────────────────\n";

foreach ($foldersToConsolidate as $sourceFolder => $targetFolder) {
    $sourcePath = "$DOMAINS_PATH/$sourceFolder";
    $targetPath = "$DOMAINS_PATH/$targetFolder";

    // Создать папку-назначение, если не существует
    if (!is_dir($targetPath)) {
        mkdir($targetPath, 0755, true);
        echo "  📁 Создана папка: $targetFolder\n";
    }

    // Переместить все файлы (рекурсивно)
    $moved = moveFilesRecursive($sourcePath, $targetPath, $sourceFolder, $targetFolder);
    $stats['files_moved'] += $moved;
    $stats['namespaces_fixed'] += $moved;
    $stats['actions'][] = "Перемещено $moved файлов из $sourceFolder → $targetFolder";

    if ($moved > 0) {
        echo "  ✅ $sourceFolder → $targetFolder: перемещено $moved файлов\n";
    }
}

echo "\n";

// ===== ШАГ 3: УДАЛЕНИЕ ПУСТЫХ ПАПОК =====
echo "3️⃣  УДАЛЕНИЕ ПУСТЫХ И НЕНУЖНЫХ ПАПОК\n";
echo "─────────────────────────────────────────\n";

foreach ($foldersToConsolidate as $sourceFolder => $targetFolder) {
    $sourcePath = "$DOMAINS_PATH/$sourceFolder";
    if (is_dir($sourcePath) && isDirEmpty($sourcePath)) {
        rmdir($sourcePath);
        $stats['folders_consolidated']++;
        $stats['folders_deleted']++;
        $stats['actions'][] = "Удалена папка: $sourceFolder";
        echo "  🗑️  Удалена: $sourceFolder\n";
    }
}

// Удалить целевые папки на удаление
foreach ($foldersToDrop as $folder) {
    $folderPath = "$DOMAINS_PATH/$folder";
    if (is_dir($folderPath)) {
        deleteDirectoryRecursive($folderPath);
        $stats['folders_deleted']++;
        $stats['actions'][] = "Удалена папка: $folder (целевые папки)";
        echo "  🗑️  Удалена: $folder\n";
    }
}

echo "\n";

// ===== ИТОГОВЫЙ ОТЧЁТ =====
echo "═══════════════════════════════════════════════════════════\n";
echo "📊 ИТОГОВЫЙ ОТЧЁТ\n";
echo "═══════════════════════════════════════════════════════════\n\n";

echo "📈 Статистика:\n";
echo "  • Папок проанализировано: {$stats['folders_analyzed']}\n";
echo "  • Папок объединено/переименовано: {$stats['folders_consolidated']}\n";
echo "  • Папок удалено: {$stats['folders_deleted']}\n";
echo "  • Файлов перемещено: {$stats['files_moved']}\n";
echo "  • Namespaces исправлено: {$stats['namespaces_fixed']}\n\n";

// ===== ФИНАЛЬНАЯ СТРУКТУРА =====
echo "📁 ФИНАЛЬНАЯ СТРУКТУРА app/Domains/:\n";
echo "─────────────────────────────────────────\n";

$finalFolders = array_filter(
    scandir($DOMAINS_PATH),
    fn($item) => is_dir("$DOMAINS_PATH/$item") && !in_array($item, ['.', '..'])
);

sort($finalFolders);

foreach ($finalFolders as $folder) {
    $folderPath = "$DOMAINS_PATH/$folder";
    $fileCount = countFilesRecursive($folderPath);
    echo "  ✅ $folder/ ({$fileCount} файлов)\n";
}

echo "\n✅ КОНСОЛИДАЦИЯ ЗАВЕРШЕНА!\n";

// ===== ФУНКЦИИ-ПОМОЩНИКИ =====

function guessTargetVertical(string $folder): string {
    $mapping = [
        'Vapes' => 'SportsNutrition',
        'RitualServices' => 'HomeServices',
        'EventManagement' => 'EventPlanning',
        'Content' => 'Education',
    ];

    return $mapping[$folder] ?? 'Luxury';
}

function moveFilesRecursive(string $source, string $target, string $sourceFolder, string $targetFolder): int {
    $count = 0;

    if (!is_dir($source)) {
        return 0;
    }

    $iterator = new RecursiveDirectoryIterator(
        $source,
        RecursiveDirectoryIterator::SKIP_DOTS
    );

    $files = new RecursiveIteratorIterator($iterator);

    foreach ($files as $file) {
        if ($file->isFile()) {
            $relPath = substr($file->getRealPath(), strlen($source) + 1);
            $targetFile = $target . DIRECTORY_SEPARATOR . $relPath;

            // Создать директории-назначения
            $targetDir = dirname($targetFile);
            if (!is_dir($targetDir)) {
                mkdir($targetDir, 0755, true);
            }

            // Переместить файл
            copy($file->getRealPath(), $targetFile);

            // Исправить namespace в файле PHP
            if (pathinfo($targetFile, PATHINFO_EXTENSION) === 'php') {
                fixNamespaceInFile($targetFile, $sourceFolder, $targetFolder);
            }

            // Удалить исходный файл
            unlink($file->getRealPath());
            $count++;
        }
    }

    return $count;
}

function fixNamespaceInFile(string $filePath, string $sourceFolder, string $targetFolder): void {
    $content = file_get_contents($filePath);

    // Заменить namespace: App\Domains\SourceFolder → App\Domains\TargetFolder
    $oldNamespace = "App\\\\Domains\\\\$sourceFolder";
    $newNamespace = "App\\\\Domains\\\\$targetFolder";

    $content = str_replace($oldNamespace, $newNamespace, $content);

    // Также заменить use statements
    $oldUse = "use App\\Domains\\$sourceFolder";
    $newUse = "use App\\Domains\\$targetFolder";

    $content = str_replace($oldUse, $newUse, $content);

    file_put_contents($filePath, $content);
}

function isDirEmpty(string $dir): bool {
    $handle = opendir($dir);
    while (false !== ($entry = readdir($handle))) {
        if ($entry !== '.' && $entry !== '..') {
            closedir($handle);
            return false;
        }
    }
    closedir($handle);
    return true;
}

function deleteDirectoryRecursive(string $dir): void {
    if (!is_dir($dir)) {
        return;
    }

    $files = array_diff(scandir($dir), ['.', '..']);

    foreach ($files as $file) {
        $path = $dir . DIRECTORY_SEPARATOR . $file;
        if (is_dir($path)) {
            deleteDirectoryRecursive($path);
        } else {
            unlink($path);
        }
    }

    rmdir($dir);
}

function countFilesRecursive(string $dir): int {
    $count = 0;

    if (!is_dir($dir)) {
        return 0;
    }

    $iterator = new RecursiveDirectoryIterator(
        $dir,
        RecursiveDirectoryIterator::SKIP_DOTS
    );

    $files = new RecursiveIteratorIterator($iterator);

    foreach ($files as $file) {
        if ($file->isFile()) {
            $count++;
        }
    }

    return $count;
}
?>
