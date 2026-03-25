<?php
/**
 * COMPREHENSIVE AUDIT SCRIPT 2026
 * Найти и подсчитать все нарушения канона
 */

$projectRoot = __DIR__;
$violations = [
    'stubs' => [],
    'todos' => [],
    'debug_functions' => [],
    'facades' => [],
    'short_files' => [],
    'empty_methods' => [],
    'null_returns' => [],
    'missing_correlation_id' => [],
    'missing_tenant_scoping' => [],
    'missing_fraud_check' => [],
    'missing_audit_log' => [],
    'files_with_bom' => [],
    'crlf_issues' => [],
];

function auditFile($filepath) {
    global $violations;
    
    if (!is_file($filepath) || !in_array(pathinfo($filepath, PATHINFO_EXTENSION), ['php'])) {
        return;
    }

    $content = file_get_contents($filepath);
    $relativePath = str_replace(__DIR__, '', $filepath);
    
    // 1. Проверка BOM
    if (substr($content, 0, 3) === "\xEF\xBB\xBF") {
        $violations['files_with_bom'][] = $relativePath;
    }
    
    // 2. Проверка CRLF
    if (strpos($content, "\r\n") === false && strpos($content, "\n") !== false) {
        // Есть LF но нет CRLF
        $violations['crlf_issues'][] = $relativePath;
    }
    
    // 3. Стабы и return null
    if (preg_match('/return\s+null\s*;/', $content)) {
        $violations['null_returns'][] = $relativePath;
    }
    
    if (preg_match('/return\s+\[\]\s*;/', $content) && strpos($filepath, '/migrations/') === false) {
        $violations['stubs'][] = $relativePath;
    }
    
    if (preg_match('/throw new \\\\Exception\("Not implemented"\)/', $content)) {
        $violations['stubs'][] = $relativePath;
    }
    
    // 4. TODO/FIXME/HACK
    if (preg_match('/(\/\/\s*TODO|\/\/\s*FIXME|\/\/\s*HACK|@todo|later|temporary)/', $content)) {
        $violations['todos'][] = $relativePath;
    }
    
    // 5. Debug функции
    if (preg_match('/(die\(|dd\(|dump\(|var_dump\(|print_r\()/', $content)) {
        $violations['debug_functions'][] = $relativePath;
    }
    
    // 6. Facades
    if (preg_match('/(auth\(\)|Auth::|Cache::|Queue::|Gate::|response\(\)|Response::|id\(\))/', $content)) {
        $violations['facades'][] = $relativePath;
    }
    
    // 7. Короткие файлы (< 60 строк, кроме миграций и конфигов)
    $lines = substr_count($content, "\n");
    if ($lines < 60 && 
        strpos($filepath, '/migrations/') === false && 
        strpos($filepath, '/config/') === false &&
        strpos($filepath, '/database/seeders/') === false) {
        $violations['short_files'][] = "$relativePath ($lines строк)";
    }
    
    // 8. Пустые методы
    if (preg_match('/public\s+function\s+\w+\s*\(\s*\)\s*\{\s*\}/', $content)) {
        $violations['empty_methods'][] = $relativePath;
    }
    
    // 9. Проверка correlation_id в мутациях
    if (preg_match('/DB::transaction|->create\(|->update\(|->save\(/', $content)) {
        if (!preg_match('/correlation_id/', $content)) {
            $violations['missing_correlation_id'][] = $relativePath;
        }
    }
    
    // 10. Проверка tenant scoping
    if (strpos($filepath, '/Models/') !== false && preg_match('/class\s+\w+\s+extends\s+Model/', $content)) {
        if (!preg_match('/protected static function booted/', $content) || !preg_match('/TenantScope/', $content)) {
            if (strpos($filepath, 'Tenant.php') === false && strpos($filepath, 'User.php') === false) {
                $violations['missing_tenant_scoping'][] = $relativePath;
            }
        }
    }
    
    // 11. Проверка fraud-check в Services
    if (strpos($filepath, '/Services/') !== false && preg_match('/public function/', $content)) {
        if (preg_match('/DB::transaction/', $content) && !preg_match('/FraudControlService|fraudControl|fraudCheck/', $content)) {
            $violations['missing_fraud_check'][] = $relativePath;
        }
    }
    
    // 12. Проверка audit-log в критичных операциях
    if (preg_match('/DB::transaction|->create\(|->update\(/', $content)) {
        if (!preg_match('/Log::channel\([\'"]audit[\'"]\)/', $content)) {
            $violations['missing_audit_log'][] = $relativePath;
        }
    }
}

// Сканировать все файлы
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
                auditFile($file->getPathname());
            }
        }
    }
}

// Вывести результаты
echo "╔════════════════════════════════════════════════════════════════╗\n";
echo "║          COMPREHENSIVE AUDIT REPORT 2026                       ║\n";
echo "╚════════════════════════════════════════════════════════════════╝\n\n";

$totalViolations = 0;
foreach ($violations as $category => $items) {
    if (!empty($items)) {
        $count = count($items);
        $totalViolations += $count;
        
        echo "❌ $category: $count\n";
        
        // Показать первые 5 примеров
        foreach (array_slice($items, 0, 5) as $item) {
            echo "   - $item\n";
        }
        
        if ($count > 5) {
            echo "   ... и ещё " . ($count - 5) . " файлов\n";
        }
        echo "\n";
    }
}

echo "╔════════════════════════════════════════════════════════════════╗\n";
echo "║ TOTAL VIOLATIONS: $totalViolations\n";
echo "╚════════════════════════════════════════════════════════════════╝\n";

// Сохранить в JSON
file_put_contents(
    "$projectRoot/AUDIT_REPORT_2026.json",
    json_encode($violations, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES)
);

echo "\n✅ Report saved to: AUDIT_REPORT_2026.json\n";
