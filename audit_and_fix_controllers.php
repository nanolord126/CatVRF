<?php
/**
 * Скрипт для аудита и исправления контроллеров
 * Использование: php audit_and_fix_controllers.php analyze > report.json
 * или: php audit_and_fix_controllers.php fix <controller_path>
 */

$base_path = __DIR__;
$controllers_path = $base_path . '/app/Http/Controllers';

function get_all_controllers($path = '') {
    global $controllers_path;
    $full_path = $controllers_path . ($path ? '/' . $path : '');
    $controllers = [];
    
    if (!is_dir($full_path)) return $controllers;
    
    $files = scandir($full_path);
    foreach ($files as $file) {
        if ($file === '.' || $file === '..' || $file === 'Controller.php') continue;
        
        $full_file = $full_path . '/' . $file;
        if (is_dir($full_file)) {
            $controllers = array_merge($controllers, get_all_controllers($path ? $path . '/' . $file : $file));
        } elseif (preg_match('/Controller\.php$/', $file)) {
            $rel_path = ($path ? $path . '/' : '') . $file;
            $controllers[] = [
                'name' => $file,
                'path' => $rel_path,
                'full_path' => $full_file,
            ];
        }
    }
    
    return $controllers;
}

function analyze_controller($full_path) {
    $content = file_get_contents($full_path);
    $lines = count(explode("\n", $content));
    
    // Проверка синтаксиса
    $output = [];
    $return_code = 0;
    exec("php -l " . escapeshellarg($full_path), $output, $return_code);
    $has_syntax_error = $return_code !== 0;
    
    // Подсчет методов
    preg_match_all('/\b(public|private|protected)\s+function\s+\w+/', $content, $matches);
    $method_count = count($matches[0]);
    
    // Проверка логирования
    $has_logging = preg_match('/(Log::|logger\(\)|->log\()/i', $content);
    
    // Проверка обработки ошибок
    $has_error_handling = preg_match('/(try\s*\{|catch\s*\(|throw\s)/i', $content);
    
    // Проверка пустых методов
    preg_match_all('/function\s+\w+\s*\([^)]*\)\s*\{\s*\}/', $content, $empty_methods);
    $empty_method_count = count($empty_methods[0]);
    
    // Проверка пустых возвращаемых значений
    $has_empty_returns = preg_match('/(return\s*;|return\s+null|return\s*\[\s*\]|->noContent\(\))/i', $content);
    
    return [
        'lines' => $lines,
        'methods' => $method_count,
        'has_logging' => (bool)$has_logging,
        'has_error_handling' => (bool)$has_error_handling,
        'has_syntax_error' => $has_syntax_error,
        'empty_methods' => $empty_method_count,
        'has_empty_returns' => (bool)$has_empty_returns,
    ];
}

function is_controller_fail($analysis) {
    $fail_reasons = [];
    
    if ($analysis['lines'] < 150 && $analysis['methods'] < 4) {
        $fail_reasons[] = 'менее 4 методов';
    }
    if (!$analysis['has_logging']) {
        $fail_reasons[] = 'нет логирования';
    }
    if (!$analysis['has_error_handling']) {
        $fail_reasons[] = 'нет обработки ошибок';
    }
    if ($analysis['has_syntax_error']) {
        $fail_reasons[] = 'синтаксическая ошибка';
    }
    if ($analysis['empty_methods'] > 0) {
        $fail_reasons[] = 'пустые методы';
    }
    if ($analysis['has_empty_returns']) {
        $fail_reasons[] = 'пустые возвращаемые значения';
    }
    
    return $fail_reasons;
}

// Основная логика
if (php_sapi_name() === 'cli') {
    $command = $argv[1] ?? 'analyze';
    
    if ($command === 'analyze') {
        $controllers = get_all_controllers();
        $results = [
            'total' => count($controllers),
            'issues' => [],
            'passed' => [],
        ];
        
        foreach ($controllers as $controller) {
            $analysis = analyze_controller($controller['full_path']);
            $fail_reasons = is_controller_fail($analysis);
            
            if (!empty($fail_reasons)) {
                $results['issues'][] = [
                    'path' => $controller['path'],
                    'analysis' => $analysis,
                    'fail_reasons' => $fail_reasons,
                ];
            } else {
                $results['passed'][] = $controller['path'];
            }
        }
        
        echo json_encode($results, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
    } elseif ($command === 'list-issues') {
        $controllers = get_all_controllers();
        foreach ($controllers as $controller) {
            $analysis = analyze_controller($controller['full_path']);
            $fail_reasons = is_controller_fail($analysis);
            
            if (!empty($fail_reasons)) {
                echo $controller['path'] . " => " . implode(', ', $fail_reasons) . "\n";
            }
        }
    }
}
