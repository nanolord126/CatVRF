<?php

/**
 * Скрипт для обновления всех Marketplace Policy файлов на BaseSecurityPolicy
 */

$policiesDir = __DIR__ . '/app/Policies/Marketplace';
$files = glob($policiesDir . '/*.php');

$updated = 0;
$failed = 0;

foreach ($files as $file) {
    $filename = basename($file);
    
    echo "Processing Marketplace: $filename ... ";
    
    $content = file_get_contents($file);
    
    // Проверяем уже ли обновлен файл
    if (strpos($content, 'extends BaseSecurityPolicy') !== false) {
        echo "✓ Already updated\n";
        continue;
    }
    
    // Извлекаем имя модели из имени файла
    $policyClassName = str_replace('Policy.php', '', $filename);
    
    // Создаём новое содержимое
    $newContent = <<<PHP
<?php

declare(strict_types=1);

namespace App\\Policies\\Marketplace;

use App\\Models\\User;
use App\\Policies\\BaseSecurityPolicy;
use App\\Models\\Marketplace\\$policyClassName;

/**
 * {$policyClassName}Policy - Авторизация для Marketplace вертикали $policyClassName
 */
final class {$policyClassName}Policy extends BaseSecurityPolicy
{
    /**
     * Просмотр списка
     */
    public function viewAny(User \$user): bool
    {
        return \$user->active && \$user->hasAnyRole(['admin', 'manager', 'viewer']);
    }

    /**
     * Просмотр конкретного элемента
     */
    public function view(User \$user, $policyClassName \$model): bool
    {
        if (!\$this->isFromThisTenant(\$model)) {
            return false;
        }
        
        return \$user->active && \$user->hasAnyRole(['admin', 'manager', 'viewer']);
    }

    /**
     * Создание
     */
    public function create(User \$user): bool
    {
        return \$user->active && \$user->hasAnyRole(['admin', 'manager']);
    }

    /**
     * Обновление
     */
    public function update(User \$user, $policyClassName \$model): bool
    {
        if (!\$this->isFromThisTenant(\$model)) {
            return false;
        }
        
        return \$user->active && \$user->hasAnyRole(['admin', 'manager']);
    }

    /**
     * Удаление
     */
    public function delete(User \$user, $policyClassName \$model): bool
    {
        if (!\$this->isFromThisTenant(\$model)) {
            return false;
        }
        
        return \$user->active && \$user->hasRole('admin');
    }

    /**
     * Восстановление
     */
    public function restore(User \$user, $policyClassName \$model): bool
    {
        if (!\$this->isFromThisTenant(\$model)) {
            return false;
        }
        
        return \$user->active && \$user->hasRole('admin');
    }

    /**
     * Окончательное удаление
     */
    public function forceDelete(User \$user, $policyClassName \$model): bool
    {
        if (!\$this->isFromThisTenant(\$model)) {
            return false;
        }
        
        return \$user->active && \$user->hasRole('admin');
    }
}
PHP;

    if (file_put_contents($file, $newContent) !== false) {
        echo "✓ Updated\n";
        $updated++;
    } else {
        echo "✗ Failed\n";
        $failed++;
    }
}

echo "\n✅ Summary: Updated $updated files, Failed $failed files\n";
