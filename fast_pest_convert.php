<?php
declare(strict_types=1);

/**
 * Быстрый конвертер Pest->PHPUnit
 * Использует простые регулярные выражения для замены синтаксиса
 */

class FastPestConverter {
    public static function convertFile(string $path): bool {
        if (!file_exists($path)) return false;
        
        $content = file_get_contents($path);
        
        // Проверяем есть ли Pest синтаксис
        if (!preg_match('/^\s*it\s*\(/m', $content)) {
            return false;  // Not a Pest file
        }

        echo "Converting: " . basename($path) . " ... ";

        // 1. Заменяем use Tests\BaseTestCase на use Tests\SimpleTestCase
        $content = str_replace('use Tests\\BaseTestCase;', 'use Tests\\SimpleTestCase;', $content);

        // 2. Извлекаем имя класса из пути
        preg_match('/([A-Z]\w+)\.php$/', $path, $matches);
        $className = $matches[1] ?? 'TestCase';

        // 3. Добавляем объявление класса если его нет
        if (!preg_match('/class\s+' . preg_quote($className) . '/', $content)) {
            // Находим последний use statement и добавляем класс после него
            $lastUsePos = strrpos($content, 'use ');
            if ($lastUsePos !== false) {
                $endOfUse = strpos($content, ';', $lastUsePos) + 1;
                // Вставляем класс
                $classDecl = "\n\nclass $className extends SimpleTestCase\n{\n";
                $content = substr_replace($content, $classDecl, $endOfUse, 0);
                
                // Закрываем класс в конце
                $content = rtrim($content) . "\n}";
            }
        }

        // 4. Конвертируем it() блоки в методы класса
        $content = preg_replace_callback(
            '/^\s*it\s*\(\s*[\'"]([^\'"]+)[\'"]\s*,\s*function\s*\(\s*\)\s*\{(.*?)\n\s*\}\s*\);?/ms',
            function($m) {
                $desc = $m[1];
                $body = $m[2];
                $method = 'test_' . preg_replace('/[^a-z0-9]/i', '_', $desc);
                $method = trim($method, '_');
                $method = preg_replace('/_+/', '_', strtolower($method));
                return "    /** @test */\n    public function $method(): void\n    {$body\n    }";
            },
            $content
        );

        // 5. Конвертируем expect()->toBe() в assertions
        $content = preg_replace_callback(
            '/expect\s*\(\s*([^)]+?)\s*\)\s*->\s*toBe\s*\(\s*([^)]+?)\s*\)/s',
            function($m) {
                return '$this->assertEquals(' . trim($m[2]) . ', ' . trim($m[1]) . ')';
            },
            $content
        );

        // 6. Конвертируем expect()->toEqual()
        $content = preg_replace_callback(
            '/expect\s*\(\s*([^)]+?)\s*\)\s*->\s*toEqual\s*\(\s*([^)]+?)\s*\)/s',
            function($m) {
                return '$this->assertEquals(' . trim($m[2]) . ', ' . trim($m[1]) . ')';
            },
            $content
        );

        // 7. Конвертируем expect()->toBeTrue()
        $content = preg_replace(
            '/expect\s*\(\s*([^)]+?)\s*\)\s*->\s*toBeTrue\s*\(\s*\)/s',
            '$this->assertTrue($1)',
            $content
        );

        // 8. Конвертируем expect()->toBeFalse()
        $content = preg_replace(
            '/expect\s*\(\s*([^)]+?)\s*\)\s*->\s*toBeFalse\s*\(\s*\)/s',
            '$this->assertFalse($1)',
            $content
        );

        // 9. Удаляем use Pest statements
        $content = preg_replace('/use\s+Pest\\[^;]*;/s', '', $content);
        $content = preg_replace('/use\s+function\s+Pest[^;]*;/s', '', $content);

        // 10. Очищаем пустые строки
        $content = preg_replace('/\n\n\n+/', "\n\n", $content);

        // Записываем обратно
        file_put_contents($path, $content);
        echo "✅\n";
        return true;
    }
}

// === MAIN ===
$testDir = __DIR__ . '/tests';
$files = [
    'tests/Unit/Services/Wallet/WalletServiceTest.php',
    'tests/Feature/Payment/PaymentInitTest.php',
    'tests/Feature/Fraud/FraudDetectionTest.php',
    'tests/Chaos/ChaosEngineeringTest.php',
];

echo "\n🔄 Converting Pest tests to PHPUnit...\n\n";
$converted = 0;

foreach ($files as $file) {
    $fullPath = __DIR__ . '/' . $file;
    if (FastPestConverter::convertFile($fullPath)) {
        $converted++;
    }
}

echo "\n✅ Converted $converted files\n\n";
echo "Now run: php artisan test tests/ --no-coverage\n";
