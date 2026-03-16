<?php

$baseDir = 'app/Filament/Tenant/Resources/Marketplace';
$iterator = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($baseDir));

foreach ($iterator as $file) {
    if ($file->isFile() && $file->getExtension() === 'php' && strpos($file->getPath(), 'Pages') !== false) {
        $filePath = $file->getPathname();
        $content = file_get_contents($filePath);
        
        // Extract resource name from path
        preg_match('/Marketplace\/(\w+)\/Pages/', $filePath, $matches);
        if (isset($matches[1])) {
            $resourceName = $matches[1];
            $newNamespace = "namespace App\\Filament\\Tenant\\Resources\\Marketplace\\$resourceName\\Pages;";
            $content = preg_replace(
                '/namespace App\\\\Filament\\\\Tenant\\\\Resources\\\\[^;]*Pages;/',
                $newNamespace,
                $content
            );
            file_put_contents($filePath, $content);
            echo "Fixed: " . basename($filePath) . "\n";
        }
    }
}
echo "All Marketplace Pages fixed.\n";
