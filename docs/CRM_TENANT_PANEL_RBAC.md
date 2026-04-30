# CRM & Tenant Panel RBAC Implementation

**Version:** 1.0  
**Date:** 2026-04-27  
**Status:** Production Ready

## Overview

This document describes the Role-Based Access Control (RBAC) implementation for the CRM system and Tenant Panel, integrating with the Staff vertical's 8-role hierarchy.

## Role-Based Data Access Matrix

### Tenant Panel - Staff Resource

| Role | View Resource | Create Staff | Edit Staff | Delete Staff | View Scope | Actions Available |
|------|--------------|--------------|------------|--------------|------------|-------------------|
| **Employee** | ❌ (own only) | ❌ | ✅ (own) | ❌ | Own record only | View own profile, check-in/out |
| **Object Admin** | ✅ | ❌ | ✅ (object) | ❌ | Object/tenant staff | Manage schedule, approve timeoff |
| **Accountant** | ✅ | ❌ | ✅ (limited) | ❌ | Tenant staff | View financial data |
| **Analyst** | ✅ | ❌ | ❌ | ❌ | Tenant staff | View analytics, export reports |
| **SMM Manager** | ✅ | ❌ | ✅ (limited) | ❌ | Tenant staff | Manage social, communication |
| **Manager** | ✅ | ✅ | ✅ | ✅ | Tenant staff | Full CRUD, manage roles |
| **Business Owner** | ✅ | ✅ | ✅ | ✅ | Tenant staff | Full access + settings |
| **Investor** | ✅ | ❌ | ❌ | ❌ | Tenant staff | View analytics, financial data |

### CRM System - Data Access by Role

#### Staff Data Access

| Role | Accessible Data | Scope | Permissions |
|------|----------------|-------|-------------|
| **Employee** | Own record only | `own` | `staff.view_own` |
| **Object Admin** | Object staff | `object` | `staff.view_object_staff` |
| **Accountant** | Tenant staff | `tenant` | `staff.view_all` |
| **Analyst** | Tenant staff | `tenant` | `staff.view_all` |
| **SMM Manager** | Tenant staff | `tenant` | `staff.view_all`, `staff.manage_all` |
| **Manager** | Tenant staff | `tenant` | `staff.view_all`, `staff.manage_all` |
| **Business Owner** | Tenant staff | `tenant` | `staff.view_all`, `staff.manage_all`, `staff.finance.manage_all` |
| **Investor** | Tenant staff | `tenant` | `staff.view_all`, `staff.analytics.view_all` |

#### Financial Data Access

| Role | Accessible Fields | Scope |
|------|-------------------|-------|
| **Employee** | None | - |
| **Object Admin** | None | - |
| **Accountant** | salaries, expenses, revenue, payments | Full financial access |
| **Analyst** | revenue, expenses, performance_metrics | Financial analytics |
| **SMM Manager** | None | - |
| **Manager** | revenue, performance_metrics, budget | Limited financial |
| **Business Owner** | salaries, expenses, revenue, payments, budget, roi, profit | Full financial |
| **Investor** | revenue, expenses, roi, profit | Investor metrics |

#### Analytics Access

| Role | Performance | Wellness | Learning | Financial | Forecasts | Team Analytics |
|------|-------------|----------|----------|-----------|-----------|----------------|
| **Employee** | ✅ (own) | ✅ (own) | ✅ (own) | ❌ | ❌ | ❌ |
| **Object Admin** | ✅ (own) | ✅ (own) | ✅ (own) | ❌ | ❌ | ✅ (object) |
| **Accountant** | ✅ (own) | ❌ | ❌ | ✅ | ❌ | ❌ |
| **Analyst** | ✅ (all) | ✅ (all) | ✅ (all) | ✅ | ✅ | ✅ |
| **SMM Manager** | ❌ | ❌ | ❌ | ❌ | ❌ | ✅ (social) |
| **Manager** | ✅ (all) | ✅ (all) | ✅ (all) | ❌ | ❌ | ✅ |
| **Business Owner** | ✅ (all) | ✅ (all) | ✅ (all) | ✅ | ✅ | ✅ |
| **Investor** | ✅ (all) | ❌ | ❌ | ✅ | ✅ | ✅ |

#### Export Permissions

| Role | Staff Data | Schedule | Financial | Analytics | All |
|------|------------|----------|-----------|-----------|-----|
| **Employee** | ❌ | ❌ | ❌ | ❌ | ❌ |
| **Object Admin** | ❌ | ✅ | ❌ | ❌ | ❌ |
| **Accountant** | ❌ | ❌ | ✅ | ✅ | ❌ |
| **Analyst** | ❌ | ❌ | ❌ | ✅ | ❌ |
| **SMM Manager** | ❌ | ❌ | ❌ | ✅ | ❌ |
| **Manager** | ✅ | ✅ | ❌ | ✅ | ❌ |
| **Business Owner** | ✅ | ✅ | ✅ | ✅ | ✅ |
| **Investor** | ❌ | ❌ | ✅ | ✅ | ❌ |

## Implementation Details

### Tenant Panel - StaffResource

**File:** `app/Filament/Tenant/Resources/StaffResource.php`

**RBAC Features:**
- `canViewAny()` - Only Manager+ can view the resource
- `canCreate()` - Only Manager+ can create staff
- `canView()` - Check view permission per record
- `canEdit()` - Check edit permission with hierarchy validation
- `canDelete()` - Check delete permission with hierarchy validation
- `getEloquentQuery()` - Filter results based on role:
  - Employee: sees only own record
  - Object Admin: sees tenant staff
  - Manager+: sees all tenant staff

**Role Selection:**
- Uses `StaffRole::options()` for all 8 roles
- Displays role badge with color from `StaffRole::color()`
- Shows localized label from `StaffRole::label()`

### CRM System - CRMStaffIntegrationService

**File:** `app/Services/CRM/CRMStaffIntegrationService.php`

**Methods:**

#### `getAccessibleStaff(?int $tenantId = null): array`
Returns staff data accessible based on current user's role.

**Response Structure:**
```php
[
    'data' => [...],           // Staff records
    'scope' => 'own|object|tenant',
    'permissions' => [...]     // Applicable permissions
]
```

#### `getAccessibleFinancialData(?int $tenantId = null): array`
Returns financial fields accessible based on role (cached for 2 hours).

**Response Structure:**
```php
[
    'accessible_fields' => ['salaries', 'revenue', ...],
    'tenant_id' => 123,
    'role' => 'accountant'
]
```

#### `getAccessibleAnalytics(?int $tenantId = null): array`
Returns analytics access flags based on role.

**Response Structure:**
```php
[
    'own_performance' => true,
    'team_analytics' => false,
    'financial_analytics' => true,
    // ... other flags
]
```

#### `canAccessCRMFeature(string $feature): bool`
Checks access to specific CRM features.

**Available Features:**
- `staff_management`
- `schedule_management`
- `financial_management`
- `analytics_dashboard`
- `social_features`
- `gamification`
- `learning_management`
- `communication`

#### `getExportPermissions(): array`
Returns export permissions for the current user's role.

## Usage Examples

### Tenant Panel

```php
// In Filament Resource
public static function canViewAny(): bool
{
    $staff = self::getCurrentStaff();
    $role = StaffRole::tryFrom($staff->role);
    return $role?->hasPermission('staff.view_all') ?? false;
}

// In table actions
Tables\Actions\EditAction::make()
    ->visible(fn (Staff $record): bool => self::canEdit($record));
```

### CRM System

```php
// Get accessible staff for CRM
$crmService = app(CRMStaffIntegrationService::class);
$accessibleStaff = $crmService->getAccessibleStaff($tenantId);

// Check financial data access
$financialData = $crmService->getAccessibleFinancialData($tenantId);
$canViewSalaries = in_array('salaries', $financialData['accessible_fields']);

// Check feature access
if ($crmService->canAccessCRMFeature('staff_management')) {
    // Show staff management features
}

// Get export permissions
$exportPerms = $crmService->getExportPermissions();
if (in_array('financial', $exportPerms)) {
    // Allow financial export
}
```

### API Endpoints

```php
// Using middleware
Route::middleware(['auth', 'staff.role:staff.view_all'])
    ->get('/api/staff', [StaffController::class, 'index']);

// Using policy
public function update(Request $request, Staff $staff)
{
    $this->authorize('update', $staff);
    // Update logic
}

// Using service
$roleService = app(StaffRoleService::class);
if ($roleService->hasPermission($staff, 'staff.manage_all')) {
    // Allow management operations
}
```

## Security Features

1. **Hierarchical Validation**: Higher roles can manage lower roles only
2. **Tenant Isolation**: All data access respects tenant boundaries
3. **Audit Logging**: All access changes logged with correlation IDs
4. **Fraud Detection**: Role assignments checked by FraudControlService
5. **Caching**: Permission checks cached for performance with proper invalidation
6. **Scope Limiting**: Query results filtered based on user's role

## Migration Guide

### From Old 4-Role System

1. **Update Staff Table:**
```sql
UPDATE staff SET role = 'manager' WHERE role = 'admin';
UPDATE staff SET role = 'business_owner' WHERE role = 'owner';
-- employee and accountant remain the same
```

2. **Update Filament Resources:**
- Replace role options with `StaffRole::options()`
- Update role column color to use `StaffRole::color()`
- Add RBAC visibility checks to actions

3. **Update CRM Integration:**
- Inject `CRMStaffIntegrationService`
- Use `getAccessibleStaff()` instead of direct queries
- Apply field filtering based on role

4. **Update Controllers:**
- Use StaffPolicy for authorization
- Apply middleware to routes
- Use `StaffRoleService` for permission checks

## Testing

### Unit Tests

```php
test('employee can only view own record in Tenant Panel', function () {
    $employee = createStaffWithRole(StaffRole::EMPLOYEE);
    actingAs($employee->user);
    
    expect(StaffResource::canViewAny())->toBeFalse();
});

test('manager can view all staff in Tenant Panel', function () {
    $manager = createStaffWithRole(StaffRole::MANAGER);
    actingAs($manager->user);
    
    expect(StaffResource::canViewAny())->toBeTrue();
});

test('accountant can access financial data in CRM', function () {
    $accountant = createStaffWithRole(StaffRole::ACCOUNTANT);
    $service = app(CRMStaffIntegrationService::class);
    
    $data = $service->getAccessibleFinancialData($accountant->tenant_id);
    
    expect($data['accessible_fields'])->toContain('salaries', 'expenses');
});
```

### Integration Tests

```php
test('CRM respects role-based data filtering', function () {
    $manager = createStaffWithRole(StaffRole::MANAGER);
    $employee = createStaffWithRole(StaffRole::EMPLOYEE);
    
    actingAs($manager->user);
    $managerData = app(CRMStaffIntegrationService::class)->getAccessibleStaff();
    
    actingAs($employee->user);
    $employeeData = app(CRMStaffIntegrationService::class)->getAccessibleStaff();
    
    expect(count($managerData['data']))->toBeGreaterThan(1);
    expect(count($employeeData['data']))->toBe(1);
});
```

## Performance Considerations

1. **Caching Strategy:**
   - Staff permissions: 6 hours
   - Role statistics: 12 hours
   - Financial data: 2 hours
   - Cache tags for invalidation: `['staff_roles', 'staff:' . $staffId]`

2. **Query Optimization:**
   - Eager loading of relationships
   - Tenant-scoped queries
   - Index on `role` and `tenant_id` columns

3. **Lazy Loading:**
   - Role checks performed only when needed
   - Data filtered at query level, not application level

## Compliance

- ✅ Fraud checks on all role assignments
- ✅ Audit logging with correlation IDs
- ✅ Strict type checking
- ✅ Production-ready error handling
- ✅ Cache invalidation on role changes
- ✅ Hierarchical validation
- ✅ Tenant isolation

## Support

For issues or questions:
- StaffRole enum: `app/Domains/Staff/Domain/Enums/StaffRole.php`
- StaffRoleService: `app/Domains/Staff/Services/StaffRoleService.php`
- StaffPolicy: `app/Policies/StaffPolicy.php`
- CRMStaffIntegrationService: `app/Services/CRM/CRMStaffIntegrationService.php`
- Tenant Panel Resource: `app/Filament/Tenant/Resources/StaffResource.php`
