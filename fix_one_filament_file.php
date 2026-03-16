<?php
// Простая нормализация сжатых PHP файлов

$file = 'C:\\opt\\kotvrf\\CatVRF\\app\\Filament\\Tenant\\Resources\\B2B\\HRExchangeOfferResource\\Pages\\CreateHRExchangeOffer.php';

$content = file_get_contents($file);

// Шаг 1: Удалить BOM
if (substr($content, 0, 3) === "\xEF\xBB\xBF") {
    $content = substr($content, 3);
}

// Шаг 2: Заменить знаки препинания на с новыми строками
$content = str_replace(['{', '} '], ["{\n", "}\n"], $content);

// Шаг 3: Удалить лишние пробелы перед скобками
$content = preg_replace('/\s+{/', " {", $content);

// Шаг 4: Очистить от двойных пробелов
$content = preg_replace('/\s{2,}/', ' ', $content);

// Шаг 5: Добавить правильный отступ
$lines = explode("\n", $content);
$indent = 0;
$result = [];

foreach ($lines as $line) {
    $trimmed = trim($line);
    if (empty($trimmed)) continue;
    
    // Уменьшаем отступ для }
    if (str_starts_with($trimmed, '}')) {
        $indent = max(0, $indent - 1);
    }
    
    $result[] = str_repeat('    ', $indent) . $trimmed;
    
    // Увеличиваем отступ после {
    if (str_ends_with($trimmed, '{')) {
        $indent++;
    }
}

$formatted = implode("\n", $result) . "\n";

file_put_contents($file, $formatted, FILE_TEXT);
echo "File formatted successfully\n";

// Проверим синтаксис
exec("php -l \"$file\"", $output, $ret);
echo implode("\n", $output) . "\n";
