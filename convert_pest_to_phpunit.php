<?php
declare(strict_types=1);

/**
 * Скрипт для конвертации Pest тестов в PHPUnit формат
 * Конвертирует функции `it()` в методы PHPUnit `test_*`
 */

class PestToPhpUnitConverter
{
    private string $filePath;
    private string $content;
    private string $className;

    public function convert(string $filePath): bool
    {
        $this->filePath = $filePath;
        
        if (!file_exists($filePath)) {
            echo "❌ File not found: $filePath\n";
            return false;
        }

        $this->content = file_get_contents($filePath);
        $this->className = $this->extractClassName();

        if (!$this->className) {
            echo "⚠️  Cannot determine class name from $filePath\n";
            return false;
        }

        // Конвертируем синтаксис Pest
        $this->convertItBlocks();
        $this->convertExpectStatements();
        $this->convertHooks();
        $this->fixImports();

        // Записываем обратно
        file_put_contents($filePath, $this->content);
        echo "✅ Converted: $filePath\n";
        return true;
    }

    private function extractClassName(): ?string
    {
        // Из пути вычисляем имя класса
        $baseName = basename($this->filePath, '.php');
        if (preg_match('/^([A-Z]\w+)(?:Test)?$/', $baseName, $matches)) {
            return "Tests\\Unit\\Services\\" . ($matches[1] . 'Test');
        }
        return null;
    }

    private function convertItBlocks(): void
    {
        // Конвертируем it('description', fn() => {...}) в public function test_description()
        $this->content = preg_replace_callback(
            '/it\([\'"]([^\'"]+)[\'"]\s*,\s*function\s*\(\)\s*\{(.*?)\n\s*\}\);/s',
            function ($matches) {
                $description = $matches[1];
                $body = trim($matches[2]);
                
                // Конвертируем имя теста
                $methodName = 'test_' . $this->sanitizeMethodName($description);
                
                return "    /** @test */\n    public function $methodName(): void\n    {\n$body\n    }";
            },
            $this->content
        );
    }

    private function convertExpectStatements(): void
    {
        // expect(...)->toBe(...) -> $this->assertEquals(...)
        $this->content = preg_replace(
            '/expect\(\s*(\$?\w+(?:->[\w()]+)*)\s*\)->toBe\(\s*(.*?)\s*\)/s',
            '$this->assertEquals($2, $1)',
            $this->content
        );

        // expect(...)->toEqual(...) -> $this->assertEquals(...)
        $this->content = preg_replace(
            '/expect\(\s*(\$?\w+(?:->[\w()]+)*)\s*\)->toEqual\(\s*(.*?)\s*\)/s',
            '$this->assertEquals($2, $1)',
            $this->content
        );

        // expect(...)->toBeTrue() -> $this->assertTrue(...)
        $this->content = preg_replace(
            '/expect\(\s*(\$?\w+(?:->[\w()]+)*)\s*\)->toBeTrue\(\)/s',
            '$this->assertTrue($1)',
            $this->content
        );

        // expect(...)->toBeFalse() -> $this->assertFalse(...)
        $this->content = preg_replace(
            '/expect\(\s*(\$?\w+(?:->[\w()]+)*)\s*\)->toBeFalse\(\)/s',
            '$this->assertFalse($1)',
            $this->content
        );

        // expect(...)->toThrow(...) -> $this->expectException(...)
        $this->content = preg_replace(
            '/expect\(\s*(?:fn\(\)\s*=>\s*)?([^)]+)\s*\)->toThrow\(\s*([^\)]+)\s*\)/s',
            '$this->expectException($2)',
            $this->content
        );
    }

    private function convertHooks(): void
    {
        // beforeEach(fn() => {...}) -> public function setUp(): void {...}
        $this->content = preg_replace_callback(
            '/beforeEach\(\s*(?:fn\(\)\s*=>\s*)?\{(.*?)\n\}\);/s',
            function ($matches) {
                $body = trim($matches[1]);
                return "    public function setUp(): void\n    {\n        parent::setUp();\n$body\n    }";
            },
            $this->content
        );

        // afterEach(fn() => {...}) -> public function tearDown(): void {...}
        $this->content = preg_replace_callback(
            '/afterEach\(\s*(?:fn\(\)\s*=>\s*)?\{(.*?)\n\}\);/s',
            function ($matches) {
                $body = trim($matches[1]);
                return "    public function tearDown(): void\n    {\n$body\n        parent::tearDown();\n    }";
            },
            $this->content
        );
    }

    private function fixImports(): void
    {
        // Удаляем Pest use statements
        $this->content = preg_replace(
            '/use Pest\\\{.*?};/s',
            '',
            $this->content
        );

        // Заменяем на PHPUnit использования
        if (strpos($this->content, 'use Tests\\BaseTestCase') === false && 
            strpos($this->content, 'use Tests\\SimpleTestCase') === false &&
            strpos($this->content, 'use Tests\\TenancyTestCase') === false) {
            // Добавляем use SimpleTestCase если нет ничего
            $this->content = "<?php\ndeclare(strict_types=1);\n\nuse Tests\\SimpleTestCase;\n\n" . 
                            str_replace("<?php\ndeclare(strict_types=1);\n\n", "", $this->content);
        }

        // Очищаем лишние пустые строки
        $this->content = preg_replace('/\n\n\n+/', "\n\n", $this->content);
    }

    private function sanitizeMethodName(string $description): string
    {
        // Конвертируем описание в имя метода
        $name = strtolower($description);
        $name = preg_replace('/[^a-z0-9]+/', '_', $name);
        $name = trim($name, '_');
        return $name;
    }
}

// === MAIN EXECUTION ===

echo "🔄 Converting Pest tests to PHPUnit format...\n\n";

$testDir = __DIR__ . '/tests';
$converter = new PestToPhpUnitConverter();
$converted = 0;
$failed = 0;

// Рекурсивно обходим все PHP файлы
$iterator = new RecursiveIteratorIterator(
    new RecursiveDirectoryIterator($testDir),
    RecursiveIteratorIterator::SELF_FIRST
);

foreach ($iterator as $file) {
    if ($file->getExtension() !== 'php') {
        continue;
    }

    $filePath = $file->getPathname();
    
    // Пропускаем BaseTestCase и другие служебные файлы
    if (basename($filePath) === 'BaseTestCase.php' || 
        basename($filePath) === 'SimpleTestCase.php' ||
        basename($filePath) === 'TenancyTestCase.php') {
        continue;
    }

    // Проверяем, есть ли Pest синтаксис
    $content = file_get_contents($filePath);
    if (!preg_match('/\bit\s*\(/', $content)) {
        continue;
    }

    if ($converter->convert($filePath)) {
        $converted++;
    } else {
        $failed++;
    }
}

echo "\n✅ Summary:\n";
echo "   Converted: $converted files\n";
echo "   Failed: $failed files\n";
echo "\nТеперь запустите: php artisan test tests/ --no-coverage\n";
