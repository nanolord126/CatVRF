<?php

/**
 * Скрипт для обновления всех Policy файлов на BaseSecurityPolicy
 * Usage: php update_all_policies.php
 */

$policiesDir = __DIR__ . '/app/Policies';
$files = glob($policiesDir . '/*.php');

$modelNameMap = [
    'Achievement' => 'Achievement',
    'AdCampaign' => 'AdCampaign',
    'Alert' => 'Alert',
    'Analytics' => 'Analytics',
    'Attendance' => 'Attendance',
    'Automotive' => 'Automotive',
    'B2BPartner' => 'B2BPartner',
    'Boardinghouse' => 'Boardinghouse',
    'BusinessGroup' => 'BusinessGroup',
    'Contract' => 'Contract',
    'CountryEstate' => 'CountryEstate',
    'Coupon' => 'Coupon',
    'Course' => 'Course',
    'CustomerAccount' => 'CustomerAccount',
    'CustomerAddress' => 'CustomerAddress',
    'Customer' => 'Customer',
    'CustomerReview' => 'CustomerReview',
    'CustomerWishlist' => 'CustomerWishlist',
    'DailyApartment' => 'DailyApartment',
    'DeliveryOrder' => 'DeliveryOrder',
    'Discount' => 'Discount',
    'Domain' => 'Domain',
    'Employee' => 'Employee',
    'Event' => 'Event',
    'FoodOrder' => 'FoodOrder',
    'GeoZone' => 'GeoZone',
    'HotelBooking' => 'HotelBooking',
    'HRExchangeTask' => 'HRExchangeTask',
    'HRJobVacancy' => 'HRJobVacancy',
    'InsurancePolicy' => 'InsurancePolicy',
    'InventoryItem' => 'InventoryItem',
    'Inventory' => 'Inventory',
    'Invoice' => 'Invoice',
    'LeaveRequest' => 'LeaveRequest',
    'MarketplaceProduct' => 'MarketplaceProduct',
    'MarketplaceService' => 'MarketplaceService',
    'MedicalCard' => 'MedicalCard',
    'Message' => 'Message',
    'Newsletter' => 'Newsletter',
    'Notification' => 'Notification',
    'Order' => 'Order',
    'PaymentTransaction' => 'PaymentTransaction',
    'Payroll' => 'Payroll',
    'PayrollRun' => 'PayrollRun',
    'Product' => 'Product',
    'Property' => 'Property',
    'Report' => 'Report',
    'RestaurantMenuItem' => 'RestaurantMenuItem',
    'Salon' => 'Salon',
    'SportsMembership' => 'SportsMembership',
    'Supplier' => 'Supplier',
    'TaxiRide' => 'TaxiRide',
    'Team' => 'Team',
    'Trigger' => 'Trigger',
    'Wallet' => 'Wallet',
];

$updated = 0;
$failed = 0;

foreach ($files as $file) {
    $filename = basename($file);
    
    // Skip BasePolicy и BaseSecurityPolicy
    if (strpos($filename, 'Base') !== false) {
        continue;
    }
    
    // Skip Marketplace folder
    if (is_dir($file)) {
        continue;
    }
    
    echo "Processing: $filename ... ";
    
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

namespace App\\Policies;

use App\\Models\\User;
use App\\Models\\$policyClassName;

/**
 * {$policyClassName}Policy - Авторизация для $policyClassName
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
