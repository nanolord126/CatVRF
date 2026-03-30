<?php
declare(strict_types=1);

/**
 * Массовое восстановление namespace/header для class-like PHP файлов в app/ и modules/.
 * - UTF-8 без BOM
 * - CRLF
 * - <?php declare(strict_types=1);
 * - корректный namespace на основе пути
 */

$roots = [
    'app' => 'App',
    'modules' => 'Modules',
];

$baseDir = __DIR__;
$fixed = 0;
$checked = 0;
$skipped = 0;

/**
 * @return array<int, string>
 */
function findPhpFiles(string $root): array
{
    if (!is_dir($root)) {
        return [];
    }

    $result = [];
    $it = new RecursiveIteratorIterator(
        new RecursiveDirectoryIterator($root, FilesystemIterator::SKIP_DOTS)
    );

    /** @var SplFileInfo $file */
    foreach ($it as $file) {
        if ($file->isFile() && strtolower($file->getExtension()) === 'php') {
            $result[] = $file->getPathname();
        }
    }

    return $result;
}

/**
 * @return string
 */
function normalizeToCrLf(string $text): string
{
    $text = str_replace(["\r\n", "\r"], "\n", $text);

    return str_replace("\n", "\r\n", $text);
}

/**
 * Удаляет битый namespace-блок/фрагменты только из верхней части файла.
 */
function cleanupTopBrokenNamespaceLines(string $body): string
{
    $lines = preg_split('/\R/u', $body) ?: [];

    // Ищем верхнюю границу "заголовка" (до first use/class-like)
    $topLimit = count($lines);
    foreach ($lines as $i => $line) {
        $trim = ltrim($line);
        if (
            str_starts_with($trim, 'use ') ||
            preg_match('/^(?:final\s+)?(?:abstract\s+)?(?:class|interface|trait|enum)\b/u', $trim)
        ) {
            $topLimit = $i;
            break;
        }
    }

    for ($i = 0; $i < $topLimit; $i++) {
        $line = $lines[$i] ?? '';
        $trim = trim($line);

        if ($trim === '') {
            continue;
        }

        // Не трогаем комментарии/docblock
        if (
            str_starts_with($trim, '//') ||
            str_starts_with($trim, '/*') ||
            str_starts_with($trim, '*') ||
            str_starts_with($trim, '*/')
        ) {
            continue;
        }

        // Явно битые строки namespace и их хвосты
        if (
            preg_match('/^namespace\b.*$/u', $trim) ||
            preg_match('/^namespac\b.*$/u', $trim) ||
            preg_match('/^(?:amespace|pace|ace|ce|e|mains|omains|Domains|pp|ns|ins|\\Domains)\\.*;\s*$/u', $trim)
        ) {
            $lines[$i] = '';
        }
    }

    return implode("\r\n", $lines);
}

foreach ($roots as $rootRel => $rootNs) {
    $rootAbs = $baseDir . DIRECTORY_SEPARATOR . $rootRel;
    $files = findPhpFiles($rootAbs);

    foreach ($files as $path) {
        $checked++;
        $content = file_get_contents($path);
        if ($content === false) {
            $skipped++;
            continue;
        }

        // Снять BOM
        if (str_starts_with($content, "\xEF\xBB\xBF")) {
            $content = substr($content, 3);
        }

        // Интересуют class-like файлы
        if (!preg_match('/\b(?:class|interface|trait|enum)\b/u', $content)) {
            continue;
        }

        $normalizedPath = str_replace('\\', '/', $path);
        $baseNormalized = str_replace('\\', '/', $baseDir);
        $relative = ltrim(str_replace($baseNormalized, '', $normalizedPath), '/');

        $dir = str_replace('\\', '/', dirname($relative));
        $nsParts = explode('/', $dir);

        if ($nsParts[0] !== $rootRel) {
            $skipped++;
            continue;
        }

        array_shift($nsParts); // убираем app/modules
        $expectedNamespace = $rootNs . (count($nsParts) ? '\\' . implode('\\', $nsParts) : '');

        // Отрезаем <?php + возможный declare
        $body = preg_replace(
            '/^\s*<\?php\s*(?:declare\s*\(\s*strict_types\s*=\s*1\s*\)\s*;\s*)?/u',
            '',
            $content,
            1
        );
        if (!is_string($body)) {
            $skipped++;
            continue;
        }

        $body = normalizeToCrLf($body);
        $body = cleanupTopBrokenNamespaceLines($body);

        // Удалить дубли корректного namespace, если остались
        $body = preg_replace('/^\s*namespace\s+[A-Za-z0-9_\\\\]+\s*;\s*(?:\r\n)?/um', '', $body);
        if (!is_string($body)) {
            $skipped++;
            continue;
        }

        $body = ltrim($body, "\r\n");

        // Сжимаем только избыточные пустые строки в начале блока после namespace
        $body = preg_replace('/^(?:\r\n){3,}/', "\r\n\r\n", $body);
        if (!is_string($body)) {
            $skipped++;
            continue;
        }

        $newContent = "<?php declare(strict_types=1);\r\n\r\nnamespace {$expectedNamespace};\r\n\r\n" . $body;
        $newContent = normalizeToCrLf($newContent);

        if ($newContent !== normalizeToCrLf($content)) {
            file_put_contents($path, $newContent);
            $fixed++;
        }
    }
}

echo "checked={$checked}; fixed={$fixed}; skipped={$skipped}" . PHP_EOL;
