<?php
declare(strict_types=1);

$DOMAINS_PATH = 'app/Domains';
$BASE_PATH = __DIR__;
$FULL_DOMAINS = "$BASE_PATH/$DOMAINS_PATH";

// Маппинг папок на консолидацию
$CONSOLIDATION_MAP = [
    'Content' => 'Education',
    'EventManagement' => 'EventPlanning',
    'RitualServices' => 'HomeServices',
    'Vapes' => 'SportsNutrition',
];

$stats = [
    'files_moved' => 0,
    'namespaces_fixed' => 0,
    'folders_deleted' => 0,
    'errors' => [],
    'actions' => [],
];

echo "=".str_repeat("=", 68)."\n";
echo "| CONSOLIDATE DOMAINS v3 - PRODUCTION                            |\n";
echo "=".str_repeat("=", 68)."\n\n";

// ЭТАП 1: Анализ и перемещение файлов
foreach ($CONSOLIDATION_MAP as $sourceFolder => $targetFolder) {
    $sourcePath = "$FULL_DOMAINS/$sourceFolder";
    $targetPath = "$FULL_DOMAINS/$targetFolder";

    echo "[1] Processing: $sourceFolder => $targetFolder\n";

    if (!is_dir($sourcePath)) {
        echo "    ⚠️  Source folder not found\n\n";
        continue;
    }

    // Получить все файлы рекурсивно
    $files = glob_recursive("$sourcePath/*");
    $phpFiles = array_filter($files, fn($f) => is_file($f) && pathinfo($f, PATHINFO_EXTENSION) === 'php');
    $otherFiles = array_filter($files, fn($f) => is_file($f) && pathinfo($f, PATHINFO_EXTENSION) !== 'php');

    if (empty($files)) {
        echo "    ✓ Folder is empty\n";
        echo "    Deleting: $sourceFolder\n\n";
        deleteRecursive($sourcePath);
        $stats['folders_deleted']++;
        continue;
    }

    // Создать целевую папку, если нужно
    if (!is_dir($targetPath)) {
        mkdir($targetPath, 0755, true);
    }

    // Переместить файлы
    echo "    Moving ".count($files)." files...\n";

    foreach ($files as $file) {
        if (!is_file($file)) {
            continue;
        }

        // Вычислить относительный путь
        $relPath = str_replace("$sourcePath/", '', $file);
        $destFile = "$targetPath/$relPath";
        $destDir = dirname($destFile);

        // Создать директорию назначения
        if (!is_dir($destDir)) {
            @mkdir($destDir, 0755, true);
        }

        // Копировать файл
        if (@copy($file, $destFile)) {
            // Для PHP файлов исправить namespace
            if (pathinfo($file, PATHINFO_EXTENSION) === 'php') {
                $oldContent = file_get_contents($destFile);
                $newContent = fixNamespace($oldContent, $sourceFolder, $targetFolder);

                if ($oldContent !== $newContent) {
                    file_put_contents($destFile, $newContent);
                    $stats['namespaces_fixed']++;
                    $stats['actions'][] = "Namespace fixed: $relPath";
                }
            }

            $stats['files_moved']++;
            @unlink($file);
        } else {
            $stats['errors'][] = "Failed to copy: $file";
        }
    }

    // Удалить исходную папку
    if (deleteRecursive($sourcePath)) {
        $stats['folders_deleted']++;
        echo "    ✓ Deleted source folder\n\n";
    }
}

// ЭТАП 2: Пересчитать число файлов в каждой папке
echo "\n".str_repeat("=", 70)."\n";
echo "FINAL STRUCTURE\n";
echo str_repeat("=", 70)."\n\n";

$finalFolders = array_filter(
    scandir($FULL_DOMAINS),
    fn($item) => is_dir("$FULL_DOMAINS/$item") && !in_array($item, ['.', '..'])
);

sort($finalFolders);

$totalFiles = 0;
foreach ($finalFolders as $folder) {
    $count = countFilesRecursive("$FULL_DOMAINS/$folder");
    $totalFiles += $count;
    printf("  %-30s %4d files\n", $folder.'/', $count);
}

// ИТОГОВЫЙ ОТЧЁТ
echo "\n".str_repeat("=", 70)."\n";
echo "CONSOLIDATION REPORT\n";
echo str_repeat("=", 70)."\n";
echo "Total folders: " . count($finalFolders) . "\n";
echo "Total files in Domains: $totalFiles\n";
echo "\nStatistics:\n";
echo "  • Files moved: {$stats['files_moved']}\n";
echo "  • Namespaces fixed: {$stats['namespaces_fixed']}\n";
echo "  • Folders deleted/consolidated: {$stats['folders_deleted']}\n";

if (!empty($stats['errors'])) {
    echo "\nErrors:\n";
    foreach ($stats['errors'] as $error) {
        echo "  ❌ $error\n";
    }
}

echo "\n✅ CONSOLIDATION COMPLETED!\n\n";

// ===== UTILITY FUNCTIONS =====

function glob_recursive($pattern, $flags = 0) {
    $files = glob($pattern, $flags);
    foreach (glob(dirname($pattern).'/*', GLOB_ONLYDIR|GLOB_NOSORT) as $dir) {
        $files = array_merge($files, glob_recursive($dir.'/'.basename($pattern), $flags));
    }
    return $files;
}

function fixNamespace($content, $oldVert, $newVert) {
    // Исправить namespace
    $content = str_replace(
        "namespace App\\Domains\\$oldVert",
        "namespace App\\Domains\\$newVert",
        $content
    );

    // Исправить use statements
    $content = str_replace(
        "use App\\Domains\\$oldVert",
        "use App\\Domains\\$newVert",
        $content
    );

    return $content;
}

function deleteRecursive($dir) {
    if (!is_dir($dir)) {
        return true;
    }

    $files = array_diff(scandir($dir), ['.', '..']);

    foreach ($files as $file) {
        $path = "$dir/$file";

        if (is_dir($path)) {
            deleteRecursive($path);
        } else {
            @unlink($path);
        }
    }

    return @rmdir($dir);
}

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
