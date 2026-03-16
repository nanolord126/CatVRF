<?php

$resourcesRoot = glob(__DIR__ . '/app/Filament/Tenant/Resources/Marketplace/*.php');

echo "🔍 ПРОВЕРКА НА ПУСТЫЕ РЕСУРСЫ\n";
echo "=" . str_repeat("=", 60) . "\n";

$emptyCount = 0;
foreach ($resourcesRoot as $file) {
    $size = filesize($file);
    if ($size === 0 || $size < 100) {
        echo "❌ ПУСТО (" . $size . " bytes): " . basename($file) . "\n";
        $emptyCount++;
    }
}

if ($emptyCount === 0) {
    echo "✅ Все ресурсы имеют содержимое!\n";
} else {
    echo "\n⚠️ Найдено $emptyCount пустых/маленьких файлов\n";
}

echo "\n✅ Проверка завершена\n";
