<?php

/**
 * ПОЛНЫЙ АУДИТ ПРОЕКТА ПО КРИТЕРИЯМ:
 * 1. Все файлы PHP менее строк, кроме api и роутеров = FAIL
 * 2. Все контроллеры без логгирования, защиты и т.д., с менее 150 строк = FAIL
 * 3. Если метод не работает, ответа нет, пустой, null, фасадные выражения = FAIL
 * 4. Если в файлах менее 4-5 методов, которые обязаны их иметь по логике = FAIL
 * 5. Если файлы не проверены на синтаксис или имеют ошибку = FAIL
 */

$base_path = base_path();
$audit_results = [
    'controllers' => [],
    'models' => [],
    'requests' => [],
    'policies' => [],
    'resources' => [],
    'services' => [],
    'other' => [],
    'total_issues' => 0
];

function analyze_php_file($file_path) {
    $content = file_get_contents($file_path);
    
    // Проверка синтаксиса
    $syntax_check = shell_exec("php -l " . escapeshellarg($file_path) . " 2>&1");
    $has_syntax_error = strpos($syntax_check, 'No syntax errors') === false;
    
    // Подсчет строк
    $lines = count(file($file_path));
    
    // Подсчет методов
    preg_match_all('/\s+(public|private|protected)\s+function\s+\w+/m', $content, $methods);
    $method_count = count($methods[0]);
    
    // Проверка логирования
    $has_logging = preg_match('/(Log::|logger\(|->log\(|\$this->log)/m', $content);
    
    // Проверка обработки ошибок
    $has_error_handling = preg_match('/(try\s*\{|catch\s*\(|throw\s|Exception)/m', $content);
    
    // Проверка пустых методов
    preg_match_all('/function\s+\w+\s*\(\s*\)\s*\{\s*\}/m', $content, $empty_methods);
    $empty_method_count = count($empty_methods[0]);
    
    // Проверка на return null/empty
    $has_empty_returns = preg_match('/(return\s*;|return\s+null;|return\s+\[\s*\];)/m', $content);
    
    return [
        'path' => $file_path,
        'lines' => $lines,
        'methods' => $method_count,
        'syntax_error' => $has_syntax_error,
        'has_logging' => $has_logging,
        'has_error_handling' => $has_error_handling,
        'empty_methods' => $empty_method_count,
        'empty_returns' => $has_empty_returns
    ];
}

function get_fail_reasons($analysis, $file_type = 'generic') {
    $reasons = [];
    
    // Критерий 1: Файлы менее определенного кол-ва строк
    if ($file_type === 'controller' && $analysis['lines'] < 150) {
        $reasons[] = "менее 150 строк для контроллера";
    }
    
    // Критерий 2: Контроллеры без логирования
    if ($file_type === 'controller' && !$analysis['has_logging']) {
        $reasons[] = "нет логирования";
    }
    
    // Критерий 2: Контроллеры без обработки ошибок
    if ($file_type === 'controller' && !$analysis['has_error_handling']) {
        $reasons[] = "нет обработки ошибок";
    }
    
    // Критерий 3: Пустые методы
    if ($analysis['empty_methods'] > 0) {
        $reasons[] = "пустые методы ({$analysis['empty_methods']})";
    }
    
    // Критерий 3: Пустые returns
    if ($analysis['empty_returns'] && $file_type !== 'model') {
        $reasons[] = "пустые returns";
    }
    
    // Критерий 4: Менее 4 методов
    if ($analysis['methods'] < 4 && $file_type !== 'request') {
        $reasons[] = "менее 4 методов ({$analysis['methods']})";
    }
    
    // Критерий 5: Синтаксическая ошибка
    if ($analysis['syntax_error']) {
        $reasons[] = "СИНТАКСИЧЕСКАЯ ОШИБКА";
    }
    
    return $reasons;
}

// Аудит контроллеров
echo "=== АУДИТ КОНТРОЛЛЕРОВ ===\n";
$controller_files = glob($base_path . '/app/Http/Controllers/**/*.php', GLOB_RECURSIVE);
foreach ($controller_files as $file) {
    if (basename($file) === 'Controller.php') continue;
    
    $analysis = analyze_php_file($file);
    $fail_reasons = get_fail_reasons($analysis, 'controller');
    
    if (!empty($fail_reasons)) {
        $audit_results['controllers'][] = [
            'file' => str_replace($base_path . '/', '', $file),
            'analysis' => $analysis,
            'fail_reasons' => $fail_reasons
        ];
        $audit_results['total_issues']++;
    }
}

// Аудит моделей
echo "=== АУДИТ МОДЕЛЕЙ ===\n";
$model_files = glob($base_path . '/app/Models/**/*.php', GLOB_RECURSIVE);
foreach ($model_files as $file) {
    $analysis = analyze_php_file($file);
    $fail_reasons = get_fail_reasons($analysis, 'model');
    
    if (!empty($fail_reasons)) {
        $audit_results['models'][] = [
            'file' => str_replace($base_path . '/', '', $file),
            'analysis' => $analysis,
            'fail_reasons' => $fail_reasons
        ];
        $audit_results['total_issues']++;
    }
}

// Аудит Requests
echo "=== АУДИТ HTTP REQUESTS ===\n";
$request_files = glob($base_path . '/app/Http/Requests/**/*.php', GLOB_RECURSIVE);
foreach ($request_files as $file) {
    $analysis = analyze_php_file($file);
    $fail_reasons = get_fail_reasons($analysis, 'request');
    
    if (!empty($fail_reasons)) {
        $audit_results['requests'][] = [
            'file' => str_replace($base_path . '/', '', $file),
            'analysis' => $analysis,
            'fail_reasons' => $fail_reasons
        ];
        $audit_results['total_issues']++;
    }
}

// Аудит Policies
echo "=== АУДИТ POLICIES ===\n";
$policy_files = glob($base_path . '/app/Policies/**/*.php', GLOB_RECURSIVE);
foreach ($policy_files as $file) {
    $analysis = analyze_php_file($file);
    $fail_reasons = get_fail_reasons($analysis, 'policy');
    
    if (!empty($fail_reasons)) {
        $audit_results['policies'][] = [
            'file' => str_replace($base_path . '/', '', $file),
            'analysis' => $analysis,
            'fail_reasons' => $fail_reasons
        ];
        $audit_results['total_issues']++;
    }
}

// Вывод результатов
echo "\n\n=== ИТОГОВЫЙ ОТЧЕТ ===\n";
echo "Контроллеры с проблемами: " . count($audit_results['controllers']) . "\n";
echo "Модели с проблемами: " . count($audit_results['models']) . "\n";
echo "Requests с проблемами: " . count($audit_results['requests']) . "\n";
echo "Policies с проблемами: " . count($audit_results['policies']) . "\n";
echo "ВСЕГО ФАЙЛОВ С ПРОБЛЕМАМИ: " . $audit_results['total_issues'] . "\n";

// Сохранение в JSON
file_put_contents($base_path . '/AUDIT_RESULTS.json', json_encode($audit_results, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));
echo "\nПолный отчет сохранен в AUDIT_RESULTS.json\n";
?>
