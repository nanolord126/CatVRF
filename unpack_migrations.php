<?php
/**
 * Распаковать все сжатые миграции
 */

$migrationDirs = [
    __DIR__ . '/database/migrations',
    __DIR__ . '/database/migrations/tenant',
];

$unpackCount = 0;
$errors = [];

foreach ($migrationDirs as $dir) {
    if (!is_dir($dir)) continue;
    
    $files = glob($dir . '/*.php');
    foreach ($files as $file) {
        $content = file_get_contents($file);
        
        // Если файл сжат в одну строку (или почти)
        if (substr_count($content, "\n") < 10) {
            // Используем простой regex для распаковки
            $content = str_replace(
                [' public function ', ' private function ', ' protected function ', ' final class ', ' class ', ' return new class '],
                ["\n    public function ", "\n    private function ", "\n    protected function ", "\n    final class ", "\n    class ", "\n    return new class "],
                $content
            );
            
            $content = str_replace(
                [' { ', '} '],
                [" {\n        ", "\n    } "],
                $content
            );
            
            // Убираем лишние пробелы
            $content = preg_replace('/\s+/', ' ', $content);
            
            // Снова распаковываем
            $content = str_replace(
                [' { ', '} ', '; '],
                [" {\n        ", "\n    }\n", ";\n        "],
                $content
            );
            
            file_put_contents($file, $content);
            $unpackCount++;
        }
    }
}

echo "Распакованных миграций: $unpackCount\n";
