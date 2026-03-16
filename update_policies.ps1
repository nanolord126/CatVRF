# PowerShell скрипт для обновления всех Policy файлов с правильными методами авторизации

$policyFiles = @(
    "c:\opt\kotvrf\CatVRF\app\Policies\Marketplace\ConcertPolicy.php",
    "c:\opt\kotvrf\CatVRF\app\Policies\Marketplace\ConstructionPolicy.php",
    "c:\opt\kotvrf\CatVRF\app\Policies\Marketplace\ClothingPolicy.php",
    "c:\opt\kotvrf\CatVRF\app\Policies\Marketplace\ElectronicsPolicy.php",
    "c:\opt\kotvrf\CatVRF\app\Policies\Marketplace\CoursePolicy.php",
    "c:\opt\kotvrf\CatVRF\app\Policies\Marketplace\EducationCoursePolicy.php",
    "c:\opt\kotvrf\CatVRF\app\Policies\Marketplace\CosmeticsPolicy.php",
    "c:\opt\kotvrf\CatVRF\app\Policies\Marketplace\CourseInstructorPolicy.php",
    "c:\opt\kotvrf\CatVRF\app\Policies\Marketplace\EventPolicy.php",
    "c:\opt\kotvrf\CatVRF\app\Policies\Marketplace\ClinicPolicy.php",
    "c:\opt\kotvrf\CatVRF\app\Policies\Marketplace\FlowerPolicy.php",
    "c:\opt\kotvrf\CatVRF\app\Policies\Marketplace\AnimalProductPolicy.php",
    "c:\opt\kotvrf\CatVRF\app\Policies\Marketplace\SportProductPolicy.php",
    "c:\opt\kotvrf\CatVRF\app\Policies\Marketplace\DanceEventPolicy.php",
    "c:\opt\kotvrf\CatVRF\app\Policies\DomainPolicy.php",
    "c:\opt\kotvrf\CatVRF\app\Policies\BoardinghousePolicy.php",
    "c:\opt\kotvrf\CatVRF\app\Policies\CountryEstatePolicy.php",
    "c:\opt\kotvrf\CatVRF\app\Policies\DailyApartmentPolicy.php"
)

$template = @'
<?php

declare(strict_types=1);

namespace{namespace};

use App\Models\User;
use App\Policies\BaseSecurityPolicy;

/**
 * {ClassName} - Политика авторизации для модели {Model}
 * 
 * Обеспечивает:
 * - Проверку прав доступа пользователя
 * - Многотенантную изоляцию (проверка tenant_id)
 * - Логирование действий через Audit Log
 * - Корректную работу с soft deletes
 */
final class {ClassName} extends BaseSecurityPolicy
{
    /**
     * Может ли пользователь просматривать список всех ресурсов
     */
    public function viewAny(User $user): bool
    {
        return $user->active && $user->hasAnyRole(['admin', 'manager', 'operator', 'viewer']);
    }

    /**
     * Может ли пользователь просматривать конкретный ресурс
     */
    public function view(User $user, $model): bool
    {
        // Проверяем, что ресурс из одного с пользователем тенанта
        if (!$this->isFromThisTenant($model)) {
            return $this->denyWithAudit('Ресурс не принадлежит вашему тенанту')->allowed();
        }

        return $user->active && $user->hasAnyRole(['admin', 'manager', 'operator', 'viewer']);
    }

    /**
     * Может ли пользователь создавать ресурсы
     */
    public function create(User $user): bool
    {
        return $user->active && $user->hasAnyRole(['admin', 'manager', 'operator']);
    }

    /**
     * Может ли пользователь редактировать ресурс
     */
    public function update(User $user, $model): bool
    {
        // Проверяем принадлежность к тенанту
        if (!$this->isFromThisTenant($model)) {
            return $this->denyWithAudit('Ресурс не принадлежит вашему тенанту')->allowed();
        }

        return $user->active && $user->hasAnyRole(['admin', 'manager', 'operator']);
    }

    /**
     * Может ли пользователь удалять ресурсы
     */
    public function delete(User $user, $model): bool
    {
        // Только админы и менеджеры из того же тенанта
        if (!$this->isFromThisTenant($model)) {
            return $this->denyWithAudit('Ресурс не принадлежит вашему тенанту')->allowed();
        }

        return $user->active && $user->hasAnyRole(['admin', 'manager']);
    }

    /**
     * Может ли пользователь восстанавливать мягко удаленные ресурсы
     */
    public function restore(User $user, $model): bool
    {
        // Только админы из того же тенанта
        if (!$this->isFromThisTenant($model)) {
            return $this->denyWithAudit('Ресурс не принадлежит вашему тенанту')->allowed();
        }

        return $user->active && $user->hasRole('admin');
    }

    /**
     * Может ли пользователь полностью удалять ресурсы
     */
    public function forceDelete(User $user, $model): bool
    {
        // Только супер-админы
        if (!$this->isFromThisTenant($model)) {
            return $this->denyWithAudit('Ресурс не принадлежит вашему тенанту')->allowed();
        }

        return $user->active && $user->hasRole('super-admin');
    }
}
'@

Write-Host "Обновление Policy файлов..." -ForegroundColor Cyan

$count = 0
foreach ($file in $policyFiles) {
    if (Test-Path $file) {
        # Получаем имя класса из имени файла
        $className = [System.IO.Path]::GetFileNameWithoutExtension($file)
        $content = Get-Content $file -Raw
        
        # Извлекаем namespace
        if ($content -match 'namespace\s+(.*?);') {
            $namespace = $matches[1]
            
            # Заменяем content с правильным шаблоном
            $updatedContent = $template -replace '{namespace}', $namespace -replace '{ClassName}', $className -replace '{Model}', $className -replace 'Model', ''
            
            # Конвертируем в UTF-8 с CRLF
            $updatedContent = $updatedContent -replace "`r`n", "`n"
            $updatedContent = $updatedContent -replace "`n", "`r`n"
            
            [System.IO.File]::WriteAllText($file, $updatedContent, [System.Text.Encoding]::UTF8)
            
            Write-Host "✅ $className" -ForegroundColor Green
            $count++
        }
    }
}

Write-Host "`nОбновлено: $count файлов" -ForegroundColor Cyan
