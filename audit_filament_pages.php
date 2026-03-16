<?php

declare(strict_types=1);

$projectRoot = __DIR__;

// Рекурсивно найти все Pages файлы
function findPagesFiles($dir) {
    $files = [];
    $items = scandir($dir);
    
    foreach ($items as $item) {
        if ($item === '.' || $item === '..') continue;
        
        $path = $dir . '/' . $item;
        
        if (is_dir($path)) {
            if ($item === 'Pages') {
                // Нашли папку Pages, собираем все PHP файлы
                $pagesItems = scandir($path);
                foreach ($pagesItems as $page) {
                    if ($page === '.' || $page === '..' || !str_ends_with($page, '.php')) continue;
                    $files[] = $path . '/' . $page;
                }
            } else {
                // Продолжаем рекурсию
                $files = array_merge($files, findPagesFiles($path));
            }
        }
    }
    
    return $files;
}

$pagesFiles = findPagesFiles($projectRoot . '/app/Filament/Tenant/Resources');
sort($pagesFiles);

$issues = [
    'namespace_errors' => [],
    'missing_resources' => [],
    'invalid_model_paths' => [],
    'missing_models' => [],
    'invalid_resource_configs' => [],
    'import_errors' => [],
    'parse_errors' => [],
];

$totalPages = 0;
$validPages = 0;
$brokenPages = 0;
$pageDetails = [];

echo "=" . str_repeat("=", 110) . "\n";
echo "FILAMENT PAGES AUDIT REPORT\n";
echo str_repeat("=", 110) . "\n\n";

foreach ($pagesFiles as $filePath) {
    $totalPages++;
    $relativePath = str_replace($projectRoot . '/', '', $filePath);
    
    // Читаем файл
    $content = file_get_contents($filePath);
    
    $pageInfo = [
        'file' => $relativePath,
        'valid' => true,
        'errors' => [],
    ];
    
    // Парсим PHP токены
    try {
        $tokens = token_get_all($content, TOKEN_PARSE);
    } catch (\Throwable $e) {
        $pageInfo['valid'] = false;
        $pageInfo['errors'][] = "Parse error: " . $e->getMessage();
        $issues['parse_errors'][] = ['file' => $relativePath, 'error' => $e->getMessage()];
        $brokenPages++;
        $pageDetails[] = $pageInfo;
        continue;
    }
    
    // Извлекаем namespace, use statements, class name
    $namespace = null;
    $useStatements = [];
    $className = null;
    
    for ($i = 0; $i < count($tokens); $i++) {
        $token = $tokens[$i];
        
        if (!is_array($token)) continue;
        
        // Namespace
        if ($token[0] === T_NAMESPACE) {
            $namespace = '';
            for ($j = $i + 1; $j < count($tokens); $j++) {
                $t = $tokens[$j];
                if (!is_array($t)) {
                    if ($t === ';') break;
                    continue;
                }
                if (in_array($t[0], [T_STRING, T_NS_SEPARATOR])) {
                    $namespace .= $t[1];
                }
            }
        }
        
        // Use statements
        if ($token[0] === T_USE) {
            $statement = '';
            $alias = null;
            
            for ($j = $i + 1; $j < count($tokens); $j++) {
                $t = $tokens[$j];
                
                if (!is_array($t)) {
                    if ($t === ';' || $t === ',') {
                        if ($statement) {
                            $useStatements[$alias ?? $statement] = $statement;
                        }
                        if ($t === ';') break;
                        $statement = '';
                        $alias = null;
                        continue;
                    }
                    continue;
                }
                
                if (in_array($t[0], [T_STRING, T_NS_SEPARATOR])) {
                    $statement .= $t[1];
                } elseif ($t[0] === T_AS) {
                    for ($k = $j + 1; $k < count($tokens); $k++) {
                        if (is_array($tokens[$k]) && $tokens[$k][0] === T_STRING) {
                            $alias = $tokens[$k][1];
                            break;
                        }
                    }
                }
            }
        }
        
        // Class name
        if ($token[0] === T_CLASS) {
            for ($j = $i + 1; $j < count($tokens); $j++) {
                if (is_array($tokens[$j]) && $tokens[$j][0] === T_STRING) {
                    $className = $tokens[$j][1];
                    break;
                }
            }
        }
    }
    
    // Проверяем namespace корректность
    $expectedNamespace = str_replace('\\\\', '\\', 'App\\' . 
        str_replace('/', '\\', 
            substr(dirname($filePath), strlen($projectRoot . '/app/Filament/Tenant/Resources/'))
        )
    );
    
    // Очистить дополнительные слэши
    $expectedNamespace = preg_replace('/\\\\+/', '\\', $expectedNamespace);
    
    if ($namespace !== $expectedNamespace) {
        $pageInfo['valid'] = false;
        $pageInfo['errors'][] = "Namespace: expected '$expectedNamespace', got '$namespace'";
        $issues['namespace_errors'][] = [
            'file' => $relativePath,
            'expected' => $expectedNamespace,
            'actual' => $namespace,
        ];
    }
    
    // Найти Resource в use statements или в коде
    $resourceClass = null;
    $foundResource = false;
    
    // Проверяем use statements
    foreach ($useStatements as $alias => $fullPath) {
        if (str_contains($alias, 'Resource') || str_ends_with($fullPath, 'Resource')) {
            $resourceClass = $fullPath;
            $foundResource = true;
            break;
        }
    }
    
    // Если не нашли, ищем в строке $resource
    if (!$foundResource) {
        if (preg_match('/protected\s+static\s+string\s+\$resource\s*=\s*(\w+)::class/', $content, $m)) {
            $resourceClass = $m[1];
            // Пытаемся разрешить его из use statements
            if (isset($useStatements[$resourceClass])) {
                $resourceClass = $useStatements[$resourceClass];
            }
        }
    }
    
    if (!$resourceClass) {
        $pageInfo['valid'] = false;
        $pageInfo['errors'][] = "Resource class not found";
        $issues['missing_resources'][] = ['file' => $relativePath, 'error' => 'Not found'];
        $brokenPages++;
        $pageDetails[] = $pageInfo;
        continue;
    }
    
    // Проверяем существование Resource файла
    $resourceFile = null;
    
    if (str_starts_with($resourceClass, 'App\\')) {
        $resourceFile = $projectRoot . '/app/' . str_replace('App\\', '', str_replace('\\', '/', $resourceClass)) . '.php';
    } else {
        // Пытаемся вывести из структуры папок
        preg_match('/Resources[\\\\\/]([^\\\\\/]+)[\\\\\/]Pages/', $filePath, $m);
        if ($m) {
            $resourceName = $m[1];
            $resourceFile = $projectRoot . '/app/Filament/Tenant/Resources/' . $resourceName . '/' . $resourceName . '.php';
        }
    }
    
    if (!$resourceFile || !file_exists($resourceFile)) {
        $pageInfo['valid'] = false;
        $pageInfo['errors'][] = "Resource file not found: " . ($resourceFile ?? 'unknown path');
        $issues['missing_resources'][] = [
            'file' => $relativePath,
            'resource' => $resourceClass,
            'path' => $resourceFile,
        ];
        $brokenPages++;
        $pageDetails[] = $pageInfo;
        continue;
    }
    
    // Проверяем содержимое Resource
    $resourceContent = file_get_contents($resourceFile);
    
    // Ищем $model
    if (!preg_match('/protected\s+static\s+\?string\s+\$model\s*=\s*([^;]+);/', $resourceContent, $modelMatch)) {
        $pageInfo['valid'] = false;
        $pageInfo['errors'][] = "Resource missing \$model property";
        $issues['invalid_resource_configs'][] = [
            'resource' => $resourceClass,
            'file' => basename($resourceFile),
            'issue' => 'Missing $model',
        ];
        $brokenPages++;
        $pageDetails[] = $pageInfo;
        continue;
    }
    
    $modelClass = trim(str_replace('::class', '', $modelMatch[1]));
    
    // Проверяем существование Model файла
    if (str_starts_with($modelClass, 'App\\')) {
        $modelFile = $projectRoot . '/app/' . str_replace('App\\', '', str_replace('\\', '/', $modelClass)) . '.php';
    } else {
        $pageInfo['valid'] = false;
        $pageInfo['errors'][] = "Invalid model path: $modelClass";
        $issues['invalid_model_paths'][] = [
            'resource' => $resourceClass,
            'model' => $modelClass,
        ];
        $brokenPages++;
        $pageDetails[] = $pageInfo;
        continue;
    }
    
    if (!file_exists($modelFile)) {
        $pageInfo['valid'] = false;
        $pageInfo['errors'][] = "Model file not found: $modelClass";
        $issues['missing_models'][] = [
            'resource' => $resourceClass,
            'model' => $modelClass,
            'expected_path' => $modelFile,
        ];
        $brokenPages++;
        $pageDetails[] = $pageInfo;
        continue;
    }
    
    // Проверяем импорты
    foreach ($useStatements as $alias => $fullPath) {
        // Пропускаем встроенные пакеты
        if (str_starts_with($fullPath, 'Filament\\') ||
            str_starts_with($fullPath, 'Illuminate\\') ||
            str_starts_with($fullPath, 'Laravel\\') ||
            str_starts_with($fullPath, 'Symfony\\') ||
            str_starts_with($fullPath, 'PHPUnit\\')) {
            continue;
        }
        
        // Проверяем файл
        if (!str_starts_with($fullPath, 'App\\')) {
            continue;
        }
        
        $importFile = $projectRoot . '/app/' . str_replace('App\\', '', str_replace('\\', '/', $fullPath)) . '.php';
        
        if (!file_exists($importFile)) {
            $pageInfo['valid'] = false;
            $pageInfo['errors'][] = "Import not found: $fullPath";
            $issues['import_errors'][] = [
                'file' => $relativePath,
                'import' => $fullPath,
            ];
        }
    }
    
    if ($pageInfo['valid']) {
        $validPages++;
    } else {
        $brokenPages++;
    }
    
    $pageDetails[] = $pageInfo;
}

// Вывод результатов
echo "SUMMARY:\n";
echo "--------\n";
echo sprintf("Total Pages:   %3d\n", $totalPages);
echo sprintf("Valid Pages:   %3d (%.1f%%)\n", $validPages, $totalPages > 0 ? ($validPages / $totalPages * 100) : 0);
echo sprintf("Broken Pages:  %3d (%.1f%%)\n", $brokenPages, $totalPages > 0 ? ($brokenPages / $totalPages * 100) : 0);
echo "\n";

// Детальные проблемы
$errorCategories = [
    'namespace_errors' => 'NAMESPACE ERRORS',
    'missing_resources' => 'MISSING OR INVALID RESOURCES',
    'invalid_model_paths' => 'INVALID MODEL PATHS',
    'missing_models' => 'MISSING MODELS',
    'invalid_resource_configs' => 'INVALID RESOURCE CONFIGURATIONS',
    'import_errors' => 'IMPORT ERRORS',
    'parse_errors' => 'PARSE ERRORS',
];

foreach ($errorCategories as $key => $title) {
    if (!empty($issues[$key])) {
        echo "\n" . str_repeat("=", 110) . "\n";
        echo $title . " (" . count($issues[$key]) . ")\n";
        echo str_repeat("=", 110) . "\n";
        
        foreach ($issues[$key] as $issue) {
            echo "\n";
            foreach ($issue as $k => $v) {
                if (is_string($v)) {
                    echo "  $k: $v\n";
                }
            }
        }
    }
}

// Список всех broken pages с ошибками
if ($brokenPages > 0) {
    echo "\n" . str_repeat("=", 110) . "\n";
    echo "ALL BROKEN PAGES DETAILS\n";
    echo str_repeat("=", 110) . "\n";
    
    $count = 0;
    foreach ($pageDetails as $page) {
        if (!$page['valid']) {
            $count++;
            echo "\n[{$count}] {$page['file']}\n";
            foreach ($page['errors'] as $error) {
                echo "  ✗ {$error}\n";
            }
        }
    }
}

echo "\n" . str_repeat("=", 110) . "\n";
echo "END OF AUDIT\n";
echo str_repeat("=", 110) . "\n";

?>
