<?php

/**
 * Скрипт для полной регистрации всех Policy классов в AuthServiceProvider
 * Автоматически генерирует $policies массив со всеми mappings
 */

// Получить все Policy файлы
$policiesDir = __DIR__ . '/app/Policies';
$policyMappings = [];

// Основные policies
$files = array_diff(scandir($policiesDir), ['..', '.', 'Marketplace']);
foreach ($files as $file) {
    if (!str_ends_with($file, 'Policy.php') || 
        $file === 'BasePolicy.php' || 
        $file === 'BaseSecurityPolicy.php') {
        continue;
    }
    
    $className = str_replace('Policy.php', '', $file);
    $modelNamespace = "App\\Models\\Tenants\\"; // Предположительно для тенант моделей
    $policyClass = "App\\Policies\\{$className}Policy";
    
    $policyMappings[] = "\t\t// {$className}\n\t\t// {$modelNamespace}{$className}::class => {$policyClass}::class,";
}

// Marketplace policies
$marketplaceDir = __DIR__ . '/app/Policies/Marketplace';
if (is_dir($marketplaceDir)) {
    $mpFiles = array_diff(scandir($marketplaceDir), ['..', '.']);
    foreach ($mpFiles as $file) {
        if (!str_ends_with($file, 'Policy.php')) {
            continue;
        }
        
        $className = str_replace('Policy.php', '', $file);
        $policyClass = "App\\Policies\\Marketplace\\{$className}Policy";
        
        $policyMappings[] = "\t\t// Marketplace: {$className}\n\t\t// App\\Models\\Marketplace\\{$className}::class => {$policyClass}::class,";
    }
}

// Генерируем список
$output = "// Generated policy mappings (for reference)\n";
$output .= "// Copy and paste these lines into \$policies array in AuthServiceProvider.php\n\n";
$output .= implode("\n", $policyMappings);

echo $output;

// Сохраняем в файл
file_put_contents(__DIR__ . '/POLICY_MAPPINGS.txt', $output);
echo "\n\n✅ Policy mappings saved to POLICY_MAPPINGS.txt\n";
echo "Total policies: " . count($policyMappings) . "\n";
