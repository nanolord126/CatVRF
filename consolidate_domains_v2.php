<?php
declare(strict_types=1);

/**
 * CONSOLIDATE DOMAINS v2 — Реальная консолидация файлов
 */

$DOMAINS_PATH = __DIR__ . '/app/Domains';

$FOLDER_MAPPING = [
    'Content' => 'Education',
    'EventManagement' => 'EventPlanning',
    'RitualServices' => 'HomeServices',
    'Vapes' => 'SportsNutrition',
];

$stats = [
    'files_moved' => 0,
    'namespaces_fixed' => 0,
    'folders_deleted' => 0,
];

echo "CONSOLIDATING DOMAINS...\n";

// Обработать каждую папку на консолидацию
foreach ($FOLDER_MAPPING as $source => $target) {
    $sourcePath = "$DOMAINS_PATH/$source";
    $targetPath = "$DOMAINS_PATH/$target";

    if (!is_dir($sourcePath)) {
        continue;
    }

    // Переместить все файлы
    $files = getFilesRecursive($sourcePath);

    if (empty($files)) {
        // Если папка пустая, просто удалить
        rmdir($sourcePath);
        $stats['folders_deleted']++;
        echo "DELETED (empty): $source\n";
        continue;
    }

    // Создать целевую папку, если нужно
    if (!is_dir($targetPath)) {
        mkdir($targetPath, 0755, true);
    }

    foreach ($files as $file) {
        if (!is_file($file)) {
            continue;
        }

        // Вычислить относительный путь
        $relPath = str_replace($sourcePath . DIRECTORY_SEPARATOR, '', $file);
        $destFile = $targetPath . DIRECTORY_SEPARATOR . $relPath;

        // Создать директории в целевой папке
        $destDir = dirname($destFile);
        if (!is_dir($destDir)) {
            @mkdir($destDir, 0755, true);
        }

        // Скопировать файл
        if (copy($file, $destFile)) {
            // Исправить namespace если PHP файл
            if (strtolower(pathinfo($file, PATHINFO_EXTENSION)) === 'php') {
                fixNamespace($destFile, $source, $target);
                $stats['namespaces_fixed']++;
            }

            // Удалить исходный файл
            unlink($file);
            $stats['files_moved']++;
        }
    }

    // Удалить исходную папку с подпапками
    deleteDir($sourcePath);
    $stats['folders_deleted']++;
    echo "CONSOLIDATED: $source => $target (" . count($files) . " files)\n";
}

// Отчёт
echo "\n================================================\n";
echo "CONSOLIDATION REPORT\n";
echo "================================================\n";
echo "Files moved: {$stats['files_moved']}\n";
echo "Namespaces fixed: {$stats['namespaces_fixed']}\n";
echo "Folders deleted: {$stats['folders_deleted']}\n";
echo "\nFinal structure:\n";

$folders = scandir($DOMAINS_PATH);
$folders = array_filter($folders, fn($f) => is_dir("$DOMAINS_PATH/$f") && $f[0] !== '.');
sort($folders);

foreach ($folders as $folder) {
    $count = countFiles("$DOMAINS_PATH/$folder");
    echo "  $folder ($count files)\n";
}

// ФУНКЦИИ

function getFilesRecursive($dir) {
    $files = [];
    $iterator = new RecursiveDirectoryIterator($dir, RecursiveDirectoryIterator::SKIP_DOTS);
    $it = new RecursiveIteratorIterator($iterator);

    foreach ($it as $file) {
        if ($file->isFile()) {
            $files[] = $file->getRealPath();
        }
    }

    return $files;
}

function fixNamespace($file, $oldVert, $newVert) {
    $content = file_get_contents($file);

    $oldNS = "namespace App\\Domains\\$oldVert";
    $newNS = "namespace App\\Domains\\$newVert";

    $content = str_replace($oldNS, $newNS, $content);

    $oldUse = "use App\\Domains\\$oldVert";
    $newUse = "use App\\Domains\\$newVert";

    $content = str_replace($oldUse, $newUse, $content);

    file_put_contents($file, $content);
}

function deleteDir($dir) {
    if (!is_dir($dir)) {
        return;
    }

    $files = array_diff(scandir($dir), ['.', '..']);

    foreach ($files as $file) {
        $path = $dir . DIRECTORY_SEPARATOR . $file;

        if (is_dir($path)) {
            deleteDir($path);
        } else {
            @unlink($path);
        }
    }

    @rmdir($dir);
}

function countFiles($dir) {
    if (!is_dir($dir)) {
        return 0;
    }

    $count = 0;
    $iterator = new RecursiveDirectoryIterator($dir, RecursiveDirectoryIterator::SKIP_DOTS);
    $it = new RecursiveIteratorIterator($iterator);

    foreach ($it as $file) {
        if ($file->isFile()) {
            $count++;
        }
    }

    return $count;
}
?>
