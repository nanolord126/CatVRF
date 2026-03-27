<?php
declare(strict_types=1);

/**
 * FINAL DOMAINS CONSOLIDATION SCRIPT 2026
 * Перемещает 10 спорных папок в финальные места назначения
 * 
 * Маппинг:
 * 1. Appointments          → Common/Appointments
 * 2. Bloggers + Channels + Social → Content (новая вертикаль)
 * 3. Chat                  → Common/Chat
 * 4. Common                → оставить
 * 5. Ritual                → RitualServices (новая вертикаль)
 * 6. Shop                  → Marketplace (новая вертикаль)
 * 7. Vapes                 → оставить как есть
 * 8. Vault                 → Common/Security
 */

$basePath = __DIR__;
$domainsPath = $basePath . '/app/Domains';

// === МАППИНГ ПЕРЕМЕЩЕНИЙ ===
// Формат: 'SourceFolder' => ['target' => 'TargetPath', 'subdir' => 'Subdirectory']
$movements = [
    'Appointments' => ['target' => 'Common', 'subdir' => 'Appointments'],
    'Chat'         => ['target' => 'Common', 'subdir' => 'Chat'],
    'Vault'        => ['target' => 'Common', 'subdir' => 'Security'],
    'Bloggers'     => ['target' => 'Content', 'subdir' => 'Bloggers'],
    'Channels'     => ['target' => 'Content', 'subdir' => 'Channels'],
    'Social'       => ['target' => 'Content', 'subdir' => 'Social'],
    'Ritual'       => ['target' => 'RitualServices', 'subdir' => null],
    'Shop'         => ['target' => 'Marketplace', 'subdir' => null],
];

// Папки, которые не трогаем
$keepAsIs = ['Common', 'Vapes'];

$stats = [
    'moved_files'            => 0,
    'fixed_namespaces'       => 0,
    'updated_project_files'  => 0,
    'deleted_folders'        => 0,
    'errors'                 => [],
    'movements_log'          => [],
];

// Накапливаем замены namespace
$nsReplacements = [];

echo "\n=== FINAL DOMAINS CONSOLIDATION 2026 ===\n";
echo "Базовый путь: $basePath\n";
echo "Папок для перемещения: " . (count($movements)) . "\n";
echo "Папок, которые остаются: " . count($keepAsIs) . "\n\n";

// ========== ШАГ 1: Создаём целевые директории ==========
echo "--- ШАГ 1: Создание целевых директорий ---\n";

$targetDirs = ['Common', 'Content', 'RitualServices', 'Marketplace'];

foreach ($targetDirs as $dir) {
    $dirPath = $domainsPath . DIRECTORY_SEPARATOR . $dir;
    if (!is_dir($dirPath)) {
        mkdir($dirPath, 0755, true);
        echo "  ✓ Создана: $dir\n";
    }
}

// ========== ШАГ 2: Перемещаем файлы ==========
echo "\n--- ШАГ 2: Перемещение файлов ---\n";

foreach ($movements as $source => $config) {
    $sourcePath = $domainsPath . DIRECTORY_SEPARATOR . $source;

    if (!is_dir($sourcePath)) {
        $stats['errors'][] = "Исходная папка не найдена: $source";
        continue;
    }

    $target = $config['target'];
    $subdir = $config['subdir'];

    // Формируем целевой путь
    if ($subdir) {
        $targetBasePath = $domainsPath . DIRECTORY_SEPARATOR . $target . DIRECTORY_SEPARATOR . $subdir;
        $oldNsBase = 'App\\Domains\\' . $source;
        $newNsBase = 'App\\Domains\\' . $target . '\\' . $subdir;
    } else {
        $targetBasePath = $domainsPath . DIRECTORY_SEPARATOR . $target . DIRECTORY_SEPARATOR . $source;
        $oldNsBase = 'App\\Domains\\' . $source;
        $newNsBase = 'App\\Domains\\' . $target . '\\' . $source;
    }

    // Регистрируем замену namespace
    $nsReplacements[$oldNsBase] = $newNsBase;

    // Итерируемся по файлам
    $iterator = new RecursiveIteratorIterator(
        new RecursiveDirectoryIterator($sourcePath, RecursiveDirectoryIterator::SKIP_DOTS),
        RecursiveIteratorIterator::SELF_FIRST
    );

    $movedCount = 0;

    foreach ($iterator as $item) {
        if (!$item->isFile()) {
            continue;
        }

        $relativeToSource = ltrim(
            str_replace($sourcePath, '', $item->getPathname()),
            DIRECTORY_SEPARATOR
        );

        $targetFilePath = $targetBasePath . DIRECTORY_SEPARATOR . $relativeToSource;
        $targetDir      = dirname($targetFilePath);

        if (!is_dir($targetDir)) {
            mkdir($targetDir, 0755, true);
        }

        $content = file_get_contents($item->getPathname());

        // Обновляем namespace в файле
        if ($item->getExtension() === 'php') {
            $content = str_replace($oldNsBase, $newNsBase, $content);
            $stats['fixed_namespaces']++;
        }

        if (file_put_contents($targetFilePath, $content) !== false) {
            $stats['moved_files']++;
            $movedCount++;
        } else {
            $stats['errors'][] = "Не удалось записать: $targetFilePath";
        }
    }

    $targetDisplay = $subdir ? "$target/$subdir" : "$target/$source";
    echo "  [$source] → [$targetDisplay] ($movedCount файлов)\n";
    $stats['movements_log'][] = "$source → $targetDisplay ($movedCount файлов)";
}

echo "\nПеремещено файлов: {$stats['moved_files']}\n";
echo "Исправлено namespace: {$stats['fixed_namespaces']}\n\n";

// ========== ШАГ 3: Project-wide namespace update ==========
echo "--- ШАГ 3: Project-wide namespace update ---\n";

// Сортируем по длине (длиннее вперёд) для правильного matching
uksort($nsReplacements, static function (string $a, string $b): int {
    return strlen($b) - strlen($a);
});

$scanDirs = [
    $basePath . '/app',
    $basePath . '/tests',
    $basePath . '/database',
    $basePath . '/routes',
    $basePath . '/config',
];

$totalPhpFiles = 0;

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
            $stats['updated_project_files']++;
        }
    }
}

echo "Просканировано PHP-файлов: $totalPhpFiles\n";
echo "Обновлено файлов: {$stats['updated_project_files']}\n\n";

// ========== ШАГ 4: Удаляем исходные пустые папки ==========
echo "--- ШАГ 4: Удаление исходных папок ---\n";

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

foreach (array_keys($movements) as $source) {
    $sourcePath = $domainsPath . DIRECTORY_SEPARATOR . $source;
    if (is_dir($sourcePath)) {
        if (removeDirectoryRecursive($sourcePath)) {
            $stats['deleted_folders']++;
            echo "  ✓ Удалено: $source\n";
        } else {
            $stats['errors'][] = "Не удалось удалить: $source";
        }
    }
}

echo "\nУдалено папок: {$stats['deleted_folders']}\n\n";

// ========== ФИНАЛЬНЫЙ ОТЧЁТ ==========
echo "=== ОТЧЁТ ПО РАЗБОРУ ПАПОК ===\n\n";

echo "✓ Appointments → перемещено в Common/Appointments\n";
echo "✓ Bloggers + Channels + Social → объединено в Content\n";
echo "✓ Chat → перемещено в Common/Chat\n";
echo "✓ Common → оставлено как есть\n";
echo "✓ Ritual → перемещено в RitualServices\n";
echo "✓ Shop → перемещено в Marketplace\n";
echo "✓ Vapes → оставлено как есть\n";
echo "✓ Vault → перемещено в Common/Security\n\n";

echo "=== СТАТИСТИКА ===\n";
echo "• Перемещено файлов: {$stats['moved_files']}\n";
echo "• Исправлено namespace в файлах: {$stats['fixed_namespaces']}\n";
echo "• Обновлено project-wide файлов: {$stats['updated_project_files']}\n";
echo "• Удалено папок: {$stats['deleted_folders']}\n\n";

if (!empty($stats['errors'])) {
    echo "=== ОШИБКИ ===\n";
    foreach ($stats['errors'] as $err) {
        echo "  ❌ $err\n";
    }
    echo "\n";
}

// Финальная проверка структуры
echo "=== ФИНАЛЬНАЯ СТРУКТУРА app/Domains/ ===\n";
$remaining = scandir($domainsPath);
$finalDomains = [];
foreach ($remaining as $item) {
    if ($item === '.' || $item === '..') {
        continue;
    }
    if (is_dir($domainsPath . DIRECTORY_SEPARATOR . $item)) {
        $finalDomains[] = $item;
    }
}
sort($finalDomains);

echo "Всего вертикалей: " . count($finalDomains) . "\n";
echo "Вертикали: " . implode(', ', $finalDomains) . "\n\n";

// Проверка содержимого Common
echo "=== Содержимое Common ===\n";
$commonPath = $domainsPath . '/Common';
if (is_dir($commonPath)) {
    $commonDirs = array_diff(scandir($commonPath), ['.', '..']);
    foreach ($commonDirs as $item) {
        $itemPath = $commonPath . '/' . $item;
        if (is_dir($itemPath)) {
            $phpCount = 0;
            $iter = new RecursiveIteratorIterator(
                new RecursiveDirectoryIterator($itemPath, RecursiveDirectoryIterator::SKIP_DOTS)
            );
            foreach ($iter as $f) {
                if ($f->isFile() && $f->getExtension() === 'php') {
                    $phpCount++;
                }
            }
            echo "  • Common/$item ($phpCount PHP-файлов)\n";
        }
    }
}

echo "\n=== ГОТОВО ===\n";
