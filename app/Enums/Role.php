<?php declare(strict_types=1);

namespace App\Enums;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

final class Role extends Model
{
    use HasFactory;

    // TODO: Проверить и восстановить содержимое класса, если оно было утеряно
    // Platform-level roles
        case SuperAdmin = 'super_admin';      // Full platform access
        case SupportAgent = 'support_agent';  // Can help users

        // Tenant-level roles
        case Owner = 'owner';                 // Full tenant access (financial decisions, team)
        case Manager = 'manager';             // Can manage operations, view analytics
        case Employee = 'employee';           // Limited operations (can't view finance)
        case Accountant = 'accountant';       // Can view financial reports only

        // User-level roles
        case Customer = 'customer';           // Regular user (can't be assigned to business)

        public function label(): string
        {
            return match($this) {
                self::SuperAdmin => 'Супер-администратор',
                self::SupportAgent => 'Агент поддержки',
                self::Owner => 'Владелец',
                self::Manager => 'Менеджер',
                self::Employee => 'Сотрудник',
                self::Accountant => 'Бухгалтер',
                self::Customer => 'Клиент',
            };
        }

        public function isPlatformAdmin(): bool
        {
            return match($this) {
                self::SuperAdmin, self::SupportAgent => true,
                default => false,
            };
        }

        public function isTenantAdmin(): bool
        {
            return match($this) {
                self::Owner, self::Manager => true,
                default => false,
            };
        }

        public function isEmployee(): bool
        {
            return match($this) {
                self::Employee, self::Accountant => true,
                default => false,
            };
        }

        public function isBusiness(): bool
        {
            return match($this) {
                self::Owner, self::Manager, self::Employee, self::Accountant => true,
                default => false,
            };
        }

        /**
         * Get all roles that can be assigned to tenant users
         */
        public static function businessRoles(): array
        {
            return [
                self::Owner,
                self::Manager,
                self::Employee,
                self::Accountant,
            ];
        }

        /**
         * Get all platform admin roles
         */
        public static function platformAdminRoles(): array
        {
            return [
                self::SuperAdmin,
                self::SupportAgent,
            ];
        }

        /**
         * Get all roles
         */
        public static function all(): array
        {
            return [
                self::SuperAdmin,
                self::SupportAgent,
                self::Owner,
                self::Manager,
                self::Employee,
                self::Accountant,
                self::Customer,
            ];
        }
}
