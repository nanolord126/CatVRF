<?php

declare(strict_types=1);

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;

/**
 * Medical Compliance Role Seeder
 *
 * Creates the MedicalComplianceRole with read-only access to:
 * - Medical records (view only)
 * - AI diagnostic logs (view only)
 * - Emergency logs (view only)
 * - Doctor profiles (view only)
 * - Clinic information (view only)
 *
 * This role is for compliance officers who need to audit medical data
 * without the ability to modify diagnoses or patient records.
 */
final readonly class MedicalComplianceRoleSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Create Medical Compliance Role
        $complianceRole = Role::firstOrCreate(
            ['name' => 'medical_compliance'],
            [
                'guard_name' => 'web',
                'description' => 'Medical Compliance Officer - Read-only access to medical records and AI logs',
            ]
        );

        // Define read-only permissions for medical resources
        $permissions = [
            // Medical Records - View Only
            'view medical records',
            'view any medical records',

            // AI Diagnostic Logs - View Only
            'view ai diagnostic logs',
            'view any ai diagnostic logs',

            // Emergency Logs - View Only
            'view emergency logs',
            'view any emergency logs',

            // Doctors - View Only
            'view doctors',
            'view any doctors',

            // Clinics - View Only
            'view clinics',
            'view any clinics',

            // Appointments - View Only
            'view appointments',
            'view any appointments',

            // Audit Logs - View Only
            'view audit logs',
            'view any audit logs',
        ];

        // Create permissions and assign to role
        foreach ($permissions as $permissionName) {
            $permission = Permission::firstOrCreate(
                ['name' => $permissionName],
                ['guard_name' => 'web']
            );

            $complianceRole->givePermissionTo($permission);
        }

        $this->command->info('Medical Compliance Role created with read-only permissions.');
    }
}