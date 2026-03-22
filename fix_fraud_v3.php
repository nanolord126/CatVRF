<?php
declare(strict_types=1);

/**
 * fix_fraud_v3.php
 *
 * Исправляет два критических бага от fix_fraud_v2.ps1:
 *
 * ТИП 1: `return         $this->fraudControlService->check(`
 *         Метод возвращает результат FCS вместо своей логики.
 *         DB::transaction( оказался без отступа на следующей строке.
 *
 * ТИП 2: `$varname =             $this->fraudControlService->check(`
 *         Переменная перезаписывается результатом FCS.
 *         DB::transaction( оказался без отступа на следующей строке.
 *
 * Оба типа имеют бэктик-баг: `$correlationId вместо $correlationId
 *
 * ИСПРАВЛЕНИЕ:
 *   - Убирает prefix (return / $var=) с check()
 *   - Фиксирует backtick
 *   - Восстанавливает prefix перед DB::transaction
 *   - Восстанавливает правильный отступ DB::transaction
 */

$baseDir = __DIR__ . '/app/Domains';

$files = new RecursiveIteratorIterator(
    new RecursiveDirectoryIterator($baseDir, RecursiveDirectoryIterator::SKIP_DOTS)
);

$totalFixed = 0;
$fixedFiles = [];
$errors = [];

foreach ($files as $file) {
    if ($file->getExtension() !== 'php') {
        continue;
    }

    $path = $file->getPathname();
    $content = file_get_contents($path);

    // Быстрая проверка — есть ли вообще backtick-баг
    if (!str_contains($content, '`$correlationId')) {
        continue;
    }

    $original = $content;

    /**
     * Главный паттерн (multi-line, x-mode):
     *
     * ^(indent)(prefix)\$this->fraudControlService->check(\n
     *   (4 аргумента)\n
     *   (null,)\n
     *   (indent_args)`$correlationId...\n
     * (indent));\n
     * ^DB::transaction
     *
     * Группы:
     * 1 = base indent (отступ строки с prefix)
     * 2 = prefix ("return   " / "$var =   ") — жадный, захватывает все пробелы
     * 3 = первые 4 аргумента (auth, __CLASS__, 0, request) — полные строки с отступами
     * 4 = строка с null,
     * 5 = содержимое строки после бэктика: $correlationId...
     * 6 = строка закрывающей );
     */
    $pattern = '/
        ^([ \t]+)                                   # 1: base indent
        (return\s+|\$\w[\w]*\s*=\s+)               # 2: prefix (greedy — тянет все пробелы)
        \$this->fraudControlService->check\(\n     # открывающий check(
        (                                            # 3: первые 4 аргумента
            [ \t]+auth\(\)->id\(\)[^\n]*\n
            [ \t]+__CLASS__[^\n]*\n
            [ \t]+0,\n
            [ \t]+request\(\)[^\n]*\n
        )
        ([ \t]+null,\n)                             # 4: null аргумент
        [ \t]+`                                     # бэктик — БАГ
        (\$correlationId[^\n]*\n)                   # 5: содержимое строки correlationId
        ([ \t]+\);\n)                               # 6: закрывающая );
        ^DB::transaction                            # DB::transaction без отступа — БАГ
    /mx';

    $content = preg_replace_callback($pattern, static function (array $m): string {
        $indent      = $m[1];
        $prefixRaw   = $m[2]; // "return         " | "$booking =             "
        $firstArgs   = $m[3]; // 4 аргумента с их собственными отступами
        $nullArg     = $m[4]; // "                null,\n"
        $corrContent = $m[5]; // "$correlationId ?? \Illuminate\Support\Str::uuid()->toString()\n"
        $closing     = $m[6]; // "        );\n"

        // Убираем trailing пробелы из prefix
        $prefixClean = rtrim($prefixRaw); // "return" | "$booking ="

        // Извлекаем отступ аргументов из строки null
        preg_match('/^([ \t]+)/', $nullArg, $nullM);
        $argIndent = $nullM[1] ?? ($indent . '    ');

        // Собираем исправленный блок
        $result  = $indent . '$this->fraudControlService->check(' . "\n";
        $result .= $firstArgs;
        $result .= $nullArg;
        $result .= $argIndent . $corrContent;  // без бэктика, правильный отступ
        $result .= $closing;
        $result .= $indent . $prefixClean . ' DB::transaction'; // prefix восстановлен + правильный отступ

        return $result;
    }, $content);

    if ($content !== $original) {
        if (file_put_contents($path, $content) === false) {
            $errors[] = $path;
        } else {
            $totalFixed++;
            $fixedFiles[] = $path;
        }
    }
}

echo "=================================================================\n";
echo "  fix_fraud_v3.php — FraudControlService broken check() fixer\n";
echo "=================================================================\n";
echo "Total files fixed: {$totalFixed}\n";

if ($fixedFiles) {
    echo "\nFixed files:\n";
    foreach ($fixedFiles as $f) {
        $rel = str_replace(['\\', __DIR__ . '/'], ['/', ''], $f);
        echo "  ✓ {$rel}\n";
    }
}

if ($errors) {
    echo "\nERRORS (could not write):\n";
    foreach ($errors as $e) {
        echo "  ✗ {$e}\n";
    }
}

echo "\nDone.\n";
