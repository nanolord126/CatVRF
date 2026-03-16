<?php

/**
 * Создаёт недостающие Pages файлы переименовывая существующие
 */

$basePath = __DIR__ . '/app/Filament/Tenant/Resources';

// Используемые требования - напрямую из getPages()
$mappings = [
    'AiAssistantChatResource/Pages/ListAiAssistantChat.php' => 'ListAiAssistantChats',
    'AiAssistantChatResource/Pages/CreateAiAssistantChat.php' => 'CreateAiAssistantChat',
    'AiAssistantChatResource/Pages/EditAiAssistantChat.php' => 'EditAiAssistantChat',
];

foreach ($mappings as $sourcePath => $targetName) {
    $fullSourcePath = $basePath . '/' . $sourcePath;
    $targetPath = dirname($fullSourcePath) . '/' . $targetName . '.php';
    
    if (!file_exists($fullSourcePath)) {
        echo "Source not found: $sourcePath\n";
        continue;
    }
    
    if (file_exists($targetPath)) {
        echo "Target already exists: $targetName.php\n";
        continue;
    }
    
    // Читаем содержимое
    $content = file_get_contents($fullSourcePath);
    
    // Заменяем имя класса
    $oldClassName = basename($sourcePath, '.php');
    if (str_contains($content, "class $oldClassName")) {
        $content = str_replace("class $oldClassName", "class $targetName", $content);
    }
    
    // Пишем новый файл
    file_put_contents($targetPath, $content);
    echo "[✓] Created: " . $targetName . ".php\n";
}
