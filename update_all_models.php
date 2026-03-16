<?php

/**
 * Скрипт для обновления всех Model классов на наследование от BaseModel
 * Заменяет "extends Model" на "extends BaseModel"
 * Удаляет импорт Model если не используется больше нигде
 */

$modelsDir = __DIR__ . '/app/Models';
$files = array_diff(scandir($modelsDir), ['..', '.', 'BaseModel.php']);

$updated = 0;
$failed = 0;

foreach ($files as $file) {
    if (!str_ends_with($file, '.php') || is_dir("$modelsDir/$file")) {
        continue;
    }
    
    $filePath = "$modelsDir/$file";
    $content = file_get_contents($filePath);
    
    // Пропускаем если уже extends BaseModel
    if (strpos($content, 'extends BaseModel') !== false) {
        echo "✓ $file (already uses BaseModel)\n";
        continue;
    }
    
    // Пропускаем если extends что-то другое (например, ServiceProvider)
    if (strpos($content, 'extends ') !== false && strpos($content, 'extends Model') === false) {
        echo "- $file (uses different parent)\n";
        continue;
    }
    
    // Заменяем extends Model на extends BaseModel
    $newContent = str_replace(
        'extends Model',
        'extends BaseModel',
        $content
    );
    
    // Обновляем импорты
    $newContent = str_replace(
        'use Illuminate\Database\Eloquent\Model;',
        'use App\Models\BaseModel;',
        $newContent
    );
    
    if ($content === $newContent) {
        echo "- $file (no changes needed)\n";
        continue;
    }
    
    if (file_put_contents($filePath, $newContent) !== false) {
        echo "✓ $file (updated to BaseModel)\n";
        $updated++;
    } else {
        echo "✗ $file (FAILED)\n";
        $failed++;
    }
}

// Обновляем подпапки
$subdirs = ['AI', 'Analytics', 'B2B', 'Common', 'CRM', 'HR', 'RealEstate', 'Tenants'];
foreach ($subdirs as $subdir) {
    $path = "$modelsDir/$subdir";
    if (!is_dir($path)) continue;
    
    $subfiles = array_diff(scandir($path), ['..', '.']);
    foreach ($subfiles as $file) {
        if (!str_ends_with($file, '.php')) continue;
        
        $filePath = "$path/$file";
        $content = file_get_contents($filePath);
        
        if (strpos($content, 'extends BaseModel') !== false) {
            echo "✓ $subdir/$file (already uses BaseModel)\n";
            continue;
        }
        
        if (strpos($content, 'extends Model') === false) {
            echo "- $subdir/$file (no Model parent)\n";
            continue;
        }
        
        $newContent = str_replace(
            'extends Model',
            'extends BaseModel',
            $content
        );
        
        $newContent = str_replace(
            'use Illuminate\Database\Eloquent\Model;',
            'use App\Models\BaseModel;',
            $newContent
        );
        
        if (file_put_contents($filePath, $newContent) !== false) {
            echo "✓ $subdir/$file (updated)\n";
            $updated++;
        } else {
            echo "✗ $subdir/$file (FAILED)\n";
            $failed++;
        }
    }
}

echo "\n✅ Summary: Updated $updated files, Failed $failed files\n";
