<?php

declare(strict_types=1);

$pagesDir = 'app/Filament/Tenant/Resources';
$iterator = new RecursiveIteratorIterator(
    new RecursiveDirectoryIterator($pagesDir, RecursiveDirectoryIterator::SKIP_DOTS),
    RecursiveIteratorIterator::SELF_FIRST
);

$fixed = 0;

foreach ($iterator as $file) {
    if ($file->getExtension() !== 'php' || !str_contains($file->getPathname(), '/Pages/')) {
        continue;
    }
    
    $path = $file->getPathname();
    $content = file_get_contents($path);
    
    // Извлекаем имя ресурса из пути
    if (preg_match('/Resources\\\\([^\\\\]+)\\\\Pages/', str_replace('/', '\\', $path), $m)) {
        $resourceName = $m[1];
        
        // Заменяем неправильный импорт
        $content = preg_replace(
            '/use App\\\\Filament\\\\Tenant\\\\Resources\\\\app\\\\Filament\\\\Tenant\\\\Resources\\\\AiAssistantChatResource;/',
            'use App\\Filament\\Tenant\\Resources\\' . $resourceName . 'Resource;',
            $content
        );
        
        // Заменяем неправильный resource
        $content = preg_replace(
            '/protected static string \$resource = AiAssistantChatResource::class;/',
            'protected static string $resource = ' . $resourceName . 'Resource::class;',
            $content
        );
        
        file_put_contents($path, $content);
        $fixed++;
        
        if ($fixed % 10 === 0) {
            echo ".";
        }
    }
}

echo "\n✅ Fixed: $fixed Pages\n";
