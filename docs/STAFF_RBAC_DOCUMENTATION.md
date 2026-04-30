# Staff RBAC System Documentation

**Version:** 1.0  
**Date:** 2026-04-27  
**Vertical:** Staff  
**Status:** Production Ready

## Overview

The Staff RBAC (Role-Based Access Control) system provides granular access control for the Staff vertical with 8 distinct roles, hierarchical permissions, and comprehensive audit logging.

## Roles

### 1. Employee (Сотрудник)
**Access Level:** 1  
**Color:** Success (Green)  
**Icon:** User

**Permissions:**
- `staff.view_own` - View own profile
- `staff.view_own_schedule` - View own schedule
- `staff.check_in` - Check in for shifts
- `staff.check_out` - Check out from shifts
- `staff.view_own_performance` - View own performance metrics
- `staff.view_own_wellness` - View own wellness data
- `staff.view_own_learning` - View own learning progress
- `staff.social.view` - View social feed
- `staff.social.post` - Create social posts
- `staff.social.like` - Like posts
- `staff.communication.send_messages` - Send messages

### 2. Object Administrator (Администратор объекта)
**Access Level:** 2  
**Color:** Info (Blue)  
**Icon:** Building Office

**Permissions:**
- All Employee permissions, plus:
- `staff.view_object_staff` - View staff at their object
- `staff.manage_object_schedule` - Manage schedule at their object
- `staff.approve_timeoff` - Approve time-off requests
- `staff.view_object_analytics` - View object-level analytics

### 3. Accountant (Бухгалтер)
**Access Level:** 3  
**Color:** Primary  
**Icon:** Calculator

**Permissions:**
- `staff.view_own` - View own profile
- `staff.view_own_performance` - View own performance
- `staff.finance.view_all` - View all financial data
- `staff.finance.view_salaries` - View salary information
- `staff.finance.view_expenses` - View expenses
- `staff.finance.view_revenue` - View revenue
- `staff.finance.export_reports` - Export financial reports
- `staff.finance.manage_payments` - Manage payments
- `staff.view_all` - View all staff
- `staff.analytics.view_financial` - View financial analytics

### 4. Analyst (Аналитик)
**Access Level:** 4  
**Color:** Warning  
**Icon:** Chart Bar

**Permissions:**
- `staff.view_own` - View own profile
- `staff.view_all` - View all staff
- `staff.analytics.view_all` - View all analytics
- `staff.analytics.view_performance` - View performance analytics
- `staff.analytics.view_wellness` - View wellness analytics
- `staff.analytics.view_learning` - View learning analytics
- `staff.analytics.forecast` - View forecasts
- `staff.analytics.export_reports` - Export analytics reports
- `staff.analytics.view_team_dynamics` - View team dynamics
- `staff.analytics.view_kpi` - View KPI

### 5. SMM Manager (SMM менеджер)
**Access Level:** 5  
**Color:** Purple  
**Icon:** Device Phone Mobile

**Permissions:**
- `staff.view_own` - View own profile
- `staff.view_all` - View all staff
- `staff.social.manage_posts` - Manage social posts
- `staff.social.manage_announcements` - Manage announcements
- `staff.social.view_analytics` - View social analytics
- `staff.communication.manage_channels` - Manage communication channels
- `staff.communication.send_broadcasts` - Send broadcast messages
- `staff.gamification.manage_challenges` - Manage challenges
- `staff.gamification.view_leaderboard` - View leaderboard

### 6. Manager (Управляющий)
**Access Level:** 6  
**Color:** Orange  
**Icon:** Briefcase

**Permissions:**
- `staff.view_own` - View own profile
- `staff.view_all` - View all staff
- `staff.manage_all` - Manage all staff
- `staff.create` - Create staff records
- `staff.update` - Update staff records
- `staff.delete` - Delete staff records
- `staff.manage_schedule` - Manage schedules
- `staff.approve_timeoff` - Approve time-off requests
- `staff.approve_shift_swap` - Approve shift swaps
- `staff.view_all_performance` - View all performance data
- `staff.manage_roles` - Manage roles
- `staff.analytics.view_all` - View all analytics
- `staff.communication.manage_all` - Manage all communication
- `staff.gamification.manage_all` - Manage all gamification
- `staff.learning.manage_all` - Manage all learning
- `staff.wellness.view_all` - View all wellness data

### 7. Business Owner (Владелец бизнеса)
**Access Level:** 7  
**Color:** Danger (Red)  
**Icon:** Crown

**Permissions:**
- All Manager permissions, plus:
- `staff.analytics.view_financial` - View financial analytics
- `staff.finance.manage_all` - Manage all financial operations
- `staff.settings.manage` - Manage system settings
- `staff.integrations.manage` - Manage integrations

### 8. Investor (Инвестор)
**Access Level:** 8  
**Color:** Gray  
**Icon:** Banknotes

**Permissions:**
- `staff.view_all` - View all staff
- `staff.analytics.view_all` - View all analytics
- `staff.analytics.view_financial` - View financial analytics
- `staff.analytics.view_performance` - View performance analytics
- `staff.analytics.forecast` - View forecasts
- `staff.analytics.export_reports` - Export analytics reports
- `staff.finance.view_revenue` - View revenue
- `staff.finance.view_expenses` - View expenses
- `staff.finance.view_roi` - View ROI

## Access Hierarchy

```
Level 8: Investor (read-only analytics & finance)
Level 7: Business Owner (full access)
Level 6: Manager (operational management)
Level 5: SMM Manager (social & communication)
Level 4: Analyst (analytics & reporting)
Level 3: Accountant (financial operations)
Level 2: Object Administrator (object-level management)
Level 1: Employee (self-service)
```

## Usage

### Assigning a Role

```php
use App\Domains\Staff\Domain\Entities\Staff;
use App\Domains\Staff\Domain\Enums\StaffRole;
use App\Domains\Staff\Services\StaffRoleService;

$staff = Staff::find($staffId);
$roleService = app(StaffRoleService::class);

$staff = $roleService->assignRole(
    $staff,
    StaffRole::MANAGER,
    'Promoted due to excellent performance'
);
```

### Checking Permissions

```php
$roleService = app(StaffRoleService::class);

// Check single permission
if ($roleService->hasPermission($staff, 'staff.manage_all')) {
    // User can manage all staff
}

// Check multiple permissions (any)
if ($roleService->hasAnyPermission($staff, ['staff.create', 'staff.update'])) {
    // User can create or update
}

// Check multiple permissions (all)
if ($roleService->hasAllPermissions($staff, ['staff.create', 'staff.update'])) {
    // User can both create and update
}
```

### Using Middleware

```php
// In routes/api.php
Route::middleware(['auth', 'staff.role:staff.view_all'])
    ->group(function () {
        Route::get('/staff', [StaffController::class, 'index']);
    });

// With specific role requirement
Route::middleware(['auth', 'staff.role:,,manager'])
    ->group(function () {
        Route::post('/staff', [StaffController::class, 'store']);
    });

// With both role and permission
Route::middleware(['auth', 'staff.role:staff.manage_all,manager'])
    ->group(function () {
        Route::delete('/staff/{id}', [StaffController::class, 'destroy']);
    });
```

### Using Policy

```php
use App\Policies\StaffPolicy;
use Illuminate\Support\Facades\Gate;

Gate::policy(Staff::class, StaffPolicy::class);

// In controller
public function update(Request $request, Staff $staff)
{
    $this->authorize('update', $staff);
    
    // Update logic
}

// In blade
@can('update', $staff)
    <button>Edit Staff</button>
@endcan
```

## Service Methods

### StaffRoleService

#### `assignRole(Staff $staff, StaffRole $role, ?string $reason = null): Staff`
Assigns a role to a staff member with fraud check and audit logging.

#### `hasPermission(Staff $staff, string $permission): bool`
Checks if a staff member has a specific permission.

#### `hasAnyPermission(Staff $staff, array $permissions): bool`
Checks if a staff member has any of the specified permissions.

#### `hasAllPermissions(Staff $staff, array $permissions): bool`
Checks if a staff member has all of the specified permissions.

#### `getStaffPermissions(Staff $staff): array`
Returns all permissions for a staff member (cached for 6 hours).

#### `getCurrentUserRole(): ?StaffRole`
Returns the role of the currently authenticated user.

#### `getCurrentStaff(): ?Staff`
Returns the Staff model for the currently authenticated user.

#### `canManageStaff(Staff $targetStaff): bool`
Checks if the current user can manage the target staff member.

#### `getStaffByRole(StaffRole $role, ?int $tenantId = null): Collection`
Returns all staff members with a specific role.

#### `getRoleStats(?int $tenantId = null): array`
Returns statistics about staff distribution by role (cached for 12 hours).

#### `getPermissionsMatrix(): array`
Returns the complete permissions matrix for all roles.

## Security Features

1. **Fraud Detection**: All role assignments are checked by FraudControlService
2. **Audit Logging**: All role changes are logged with correlation IDs
3. **Hierarchical Validation**: Higher-level roles can only be assigned by users with sufficient access level
4. **Caching**: Permission checks are cached for performance
5. **Tenant Isolation**: All operations respect tenant boundaries

## Compliance

- ✅ Fraud checks on all role assignments
- ✅ Audit logging for all role changes
- ✅ Strict type checking
- ✅ Production-ready error handling
- ✅ Cache invalidation on role changes
- ✅ Correlation IDs for traceability

## Testing

```php
use App\Domains\Staff\Domain\Enums\StaffRole;
use App\Domains\Staff\Services\StaffRoleService;

test('employee cannot assign manager role', function () {
    $employee = createStaffWithRole(StaffRole::EMPLOYEE);
    $target = createStaff();
    
    $service = app(StaffRoleService::class);
    
    expect(fn() => $service->assignRole($target, StaffRole::MANAGER))
        ->toThrow(\RuntimeException::class);
});

test('manager can assign employee role', function () {
    $manager = createStaffWithRole(StaffRole::MANAGER);
    $target = createStaff();
    
    $service = app(StaffRoleService::class);
    
    $service->assignRole($target, StaffRole::EMPLOYEE);
    
    expect($target->role)->toBe(StaffRole::EMPLOYEE->value);
});
```

## Migration Guide

To migrate from the old 4-role system to the new 8-role system:

1. Update the `staff` table to use new role values
2. Map old roles to new roles:
   - `admin` → `manager` or `business_owner`
   - `manager` → `manager`
   - `employee` → `employee`
   - `accountant` → `accountant`
3. Run migration script to update existing staff records
4. Update all references to old role values
5. Update Filament resources to use new enum

## Support

For issues or questions about the RBAC system, contact the development team or refer to:
- StaffRole enum: `app/Domains/Staff/Domain/Enums/StaffRole.php`
- StaffRoleService: `app/Domains/Staff/Services/StaffRoleService.php`
- StaffPolicy: `app/Policies/StaffPolicy.php`
