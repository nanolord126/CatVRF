#!/usr/bin/env php
<?php

declare(strict_types=1);

/**
 * Массовое переформатирование однострочных файлов в PSR-12
 */

$files = [
    'app/Domains/FashionRetail/Http/Controllers/B2BFashionController.php',
    'app/Domains/TravelTourism/Http/Controllers/B2BTravelController.php',
    'app/Domains/PetServices/Http/Controllers/B2BPetController.php',
    'app/Filament/Tenant/Resources/FreshProduceResource.php',
    'app/Filament/Tenant/Resources/ElectronicsResource.php',
    'app/Filament/Tenant/Resources/FarmDirectResource.php',
    'app/Filament/Tenant/Resources/AutoPartsResource.php',
    'app/Filament/Tenant/Resources/ConfectioneryResource.php',
    'app/Domains/MedicalHealthcare/Filament/Resources/B2BMedicalStorefrontResource.php',
];

$fixed = 0;
$failed = 0;

foreach ($files as $file) {
    $path = __DIR__ . '/' . $file;
    
    if (!file_exists($path)) {
        echo "SKIP: {$file} (not found)\n";
        continue;
    }

    try {
        $content = file_get_contents($path);
        
        // Проверяем что это однострочник
        $lines = substr_count($content, "\n");
        if ($lines > 10) {
            echo "SKIP: {$file} (already formatted)\n";
            continue;
        }

        // Запускаем PHP CS Fixer для форматирования
        $tempFile = sys_get_temp_dir() . '/temp_format_' . basename($file);
        file_put_contents($tempFile, $content);
        
        $command = sprintf(
            'vendor/bin/php-cs-fixer fix %s --rules=@PSR12 --quiet 2>&1',
            escapeshellarg($tempFile)
        );
        
        exec($command, $output, $returnCode);
        
        if ($returnCode === 0 || file_exists($tempFile)) {
            $formatted = file_get_contents($tempFile);
            
            // Добавляем declare(strict_types=1) если отсутствует
            if (!str_contains($formatted, 'declare(strict_types=1)')) {
                $formatted = preg_replace(
                    '/^<\?php\s+/',
                    "<?php\n\ndeclare(strict_types=1);\n\n",
                    $formatted
                );
            }
            
            file_put_contents($path, $formatted);
            unlink($tempFile);
            
            echo "OK:   {$file} (formatted)\n";
            $fixed++;
        } else {
            echo "FAIL: {$file} (CS Fixer error)\n";
            $failed++;
        }
    } catch (Throwable $e) {
        echo "ERROR: {$file} - {$e->getMessage()}\n";
        $failed++;
    }
}

echo "\n=== ИТОГО ===\n";
echo "Переформатировано: {$fixed}\n";
echo "Ошибок: {$failed}\n";
echo "\nВыполните: php artisan optimize:clear && php artisan route:cache\n";
