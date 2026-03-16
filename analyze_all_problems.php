<?php
/**
 * СКРИПТ ДЛЯ АНАЛИЗА ВСЕХ ПРОБЛЕМНЫХ ФАЙЛОВ
 */

$problems = [
    'controllers' => [],
    'models' => [],
    'requests' => [],
    'policies' => [],
    'resources' => [],
    'services' => [],
];

$paths = [
    'controllers' => 'app/Http/Controllers/Tenant',
    'models' => 'app/Models',
    'requests' => 'app/Http/Requests',
    'policies' => 'app/Policies',
    'resources' => 'app/Filament/Tenant/Resources',
    'services' => 'app/Services',
];

foreach ($paths as $type => $path) {
    if (!is_dir($path)) continue;
    
    $files = new RecursiveIteratorIterator(
        new RecursiveDirectoryIterator($path),
        RecursiveIteratorIterator::LEAVES_ONLY
    );

    foreach ($files as $file) {
        if ($file->getExtension() !== 'php') continue;
        
        $content = file_get_contents($file);
        $lines = count(file($file));
        $methods = preg_match_all('/(public|private|protected)\s+function/', $content);
        
        $hasLogging = preg_match('/(Log::|logger\(|->log\()/m', $content);
        $hasErrorHandling = preg_match('/(try\s*\{|catch\s*\()/m', $content);
        $hasSyntaxError = shell_exec("php -l " . escapeshellarg($file) . " 2>&1") && strpos(shell_exec("php -l " . escapeshellarg($file) . " 2>&1"), 'No syntax errors') === false;
        
        $isFail = false;
        $reasons = [];
        
        // Логика определения FAIL
        if ($type === 'controllers' && $lines < 150) {
            $isFail = true;
            $reasons[] = "менее 150 строк";
        }
        if ($type === 'controllers' && !$hasLogging) {
            $isFail = true;
            $reasons[] = "нет логирования";
        }
        if ($type === 'controllers' && !$hasErrorHandling) {
            $isFail = true;
            $reasons[] = "нет обработки ошибок";
        }
        if ($methods < 4 && $type !== 'requests') {
            $isFail = true;
            $reasons[] = "менее 4 методов";
        }
        if ($hasSyntaxError) {
            $isFail = true;
            $reasons[] = "СИНТАКСИЧЕСКАЯ ОШИБКА";
        }
        
        if ($isFail) {
            $problems[$type][] = [
                'file' => str_replace(getcwd() . '\\', '', $file),
                'lines' => $lines,
                'methods' => $methods,
                'reasons' => $reasons,
            ];
        }
    }
}

$report = [
    'total_problems' => array_sum(array_map('count', $problems)),
    'by_type' => array_map('count', $problems),
    'details' => $problems,
];

echo json_encode($report, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) . "\n";
?>
