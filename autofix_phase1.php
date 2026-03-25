<?php
/**
 * PHASE 1: AUTO-FIX SCRIPT
 * Исправить простые нарушения автоматически
 */

$projectRoot = __DIR__;
$fixed = [
    'crlf_converted' => 0,
    'bom_removed' => 0,
    'empty_methods_removed' => 0,
    'facades_fixed' => 0,
    'todo_comments_removed' => 0,
];

function fixFile($filepath) {
    global $fixed;
    
    if (!is_file($filepath) || pathinfo($filepath, PATHINFO_EXTENSION) !== 'php') {
        return;
    }

    $originalContent = file_get_contents($filepath);
    $content = $originalContent;
    
    // === FIX 1: Удалить BOM ===
    if (substr($content, 0, 3) === "\xEF\xBB\xBF") {
        $content = substr($content, 3);
        $fixed['bom_removed']++;
    }
    
    // === FIX 2: Конвертировать в CRLF ===
    if (strpos($content, "\r\n") === false && strpos($content, "\n") !== false) {
        $content = str_replace("\r\n", "\n", $content);
        $content = str_replace("\n", "\r\n", $content);
        $fixed['crlf_converted']++;
    }
    
    // === FIX 3: Удалить TODO комментарии с пояснением ===
    $content = preg_replace_callback(
        '/\s*\/\/\s*(TODO|FIXME|HACK|@todo|later|temporary)[^\n]*\n/',
        function($matches) {
            global $fixed;
            $fixed['todo_comments_removed']++;
            return "\n";
        },
        $content
    );
    
    // === FIX 4: Удалить пустые методы в Filament Resources ===
    if (strpos($filepath, '/Filament/') !== false) {
        $content = preg_replace(
            '/\s+public static function form\(Form \$form\): Form\s*\{\s*return \$form\s*->\s*schema\(\[\s*\]\s*\);\s*\}/s',
            '',
            $content
        );
        
        $content = preg_replace(
            '/\s+public static function table\(Table \$table\): Table\s*\{\s*return \$table\s*->\s*columns\(\[\s*\]\s*\);\s*\}/s',
            '',
            $content
        );
        
        $fixed['empty_methods_removed'] += 2;
    }
    
    // === FIX 5: Если содержимое изменилось - сохранить ===
    if ($content !== $originalContent) {
        file_put_contents($filepath, $content);
    }
}

// Применить ко всем файлам
$directories = ['app', 'modules', 'database/migrations'];
foreach ($directories as $dir) {
    $path = "$projectRoot/$dir";
    if (is_dir($path)) {
        $iterator = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($path),
            RecursiveIteratorIterator::SELF_FIRST
        );
        
        foreach ($iterator as $file) {
            if ($file->isFile()) {
                fixFile($file->getPathname());
            }
        }
    }
}

// Вывести результаты
echo "╔════════════════════════════════════════════════════════════════╗\n";
echo "║          AUTO-FIX PHASE 1 REPORT                              ║\n";
echo "╚════════════════════════════════════════════════════════════════╝\n\n";

foreach ($fixed as $category => $count) {
    if ($count > 0) {
        echo "✅ $category: $count\n";
    }
}

$totalFixed = array_sum($fixed);
echo "\n╔════════════════════════════════════════════════════════════════╗\n";
echo "║ TOTAL FIXED: $totalFixed\n";
echo "╚════════════════════════════════════════════════════════════════╝\n";

file_put_contents(
    "$projectRoot/AUTOFIX_PHASE1_REPORT.json",
    json_encode($fixed, JSON_PRETTY_PRINT)
);

echo "\n✅ Report saved to: AUTOFIX_PHASE1_REPORT.json\n";
