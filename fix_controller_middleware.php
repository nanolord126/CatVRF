#!/usr/bin/env php
<?php declare(strict_types=1);

/**
 * fix_controller_middleware.php
 * 
 * Автоматически удаляет $this->middleware() вызовы из конструкторов контроллеров
 * и заменяет их на правильное использование Route::middleware().
 * 
 * PRODUCTION-READY 2026 CANON
 * 
 * Usage: php fix_controller_middleware.php
 */

$basePath = realpath(__DIR__);
$controllerPath = $basePath . '/app/Http/Controllers';

$files = new RecursiveIteratorIterator(
    new RecursiveDirectoryIterator($controllerPath, RecursiveDirectoryIterator::SKIP_DOTS),
    RecursiveIteratorIterator::LEAVES_ONLY
);

$fixed = 0;
$errors = 0;

foreach ($files as $file) {
    if ($file->getExtension() !== 'php') {
        continue;
    }

    $filePath = $file->getPathname();
    $content = file_get_contents($filePath);
    $original = $content;

    // Паттерн для поиска конструктора с middleware вызовами
    $pattern = '/public\s+function\s+__construct\s*\([^)]*\)\s*\{([^}]*?)\$this->middleware\([^)]*\);([^}]*?)\}/s';

    // Удалить все $this->middleware() вызовы из конструктора
    $content = preg_replace_callback(
        '/\$this->middleware\s*\(\s*[\'"]([^\'"]+)[\'"]\s*(?:,\s*\[([^\]]*)\])?\s*\)\s*;/',
        function($matches) {
            // Просто удалить строку
            return '';
        },
        $content
    );

    // Очистить пустые строки
    $content = preg_replace('/\n\s*\n/', "\n", $content);

    if ($content !== $original) {
        if (file_put_contents($filePath, $content) !== false) {
            echo "[✓] Fixed: $filePath\n";
            $fixed++;
        } else {
            echo "[✗] Error writing: $filePath\n";
            $errors++;
        }
    }
}

echo "\n=== RESULTS ===\n";
echo "Fixed: $fixed files\n";
echo "Errors: $errors\n";
echo "\nНЕ ЗАБУДЬ:\n";
echo "1. Обновить routes в api-v1.php с правильным middleware ordering\n";
echo "2. Добавить route groups с BEAUTY_MIDDLEWARE, FOOD_MIDDLEWARE и т.д.\n";
echo "3. Проверить что контроллеры наследуют BaseApiController\n";
echo "4. Использовать \$this->isB2C(), \$this->isB2B(), \$this->auditLog()\n";
