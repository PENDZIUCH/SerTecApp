<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class PermissionsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Define all permissions by module
        $permissions = [
            // Users
            'users.view',
            'users.create',
            'users.edit',
            'users.delete',
            
            // Customers
            'customers.view',
            'customers.create',
            'customers.edit',
            'customers.delete',
            
            // Equipments
            'equipments.view',
            'equipments.create',
            'equipments.edit',
            'equipments.delete',
            
            // Work Orders
            'work_orders.view',
            'work_orders.create',
            'work_orders.edit',
            'work_orders.delete',
            'work_orders.approve',
            'work_orders.assign',
            
            // Parts
            'parts.view',
            'parts.create',
            'parts.edit',
            'parts.delete',
            'parts.adjust_stock',
            
            // Workshop
            'workshop.view',
            'workshop.create',
            'workshop.edit',
            'workshop.delete',
            
            // Subscriptions
            'subscriptions.view',
            'subscriptions.create',
            'subscriptions.edit',
            'subscriptions.delete',
            
            // Visits
            'visits.view',
            'visits.create',
            'visits.edit',
            'visits.delete',
            'visits.check_in',
            'visits.check_out',
            
            // Budgets
            'budgets.view',
            'budgets.create',
            'budgets.edit',
            'budgets.delete',
            'budgets.approve',
            'budgets.reject',
            
            // Files
            'files.view',
            'files.upload',
            'files.delete',
            
            // Reports
            'reports.view',
            'reports.export',
            
            // Settings
            'settings.view',
            'settings.edit',
        ];

        // Create all permissions
        foreach ($permissions as $permission) {
            Permission::firstOrCreate(
                ['name' => $permission, 'guard_name' => 'web']
            );
        }

        $this->command->info('✅ Permissions created successfully');

        // Assign permissions to roles
        $this->assignPermissionsToRoles();
    }

    /**
     * Assign permissions to roles
     */
    private function assignPermissionsToRoles(): void
    {
        // Admin - All permissions
        $adminRole = Role::where('name', 'admin')->first();
        if ($adminRole) {
            $adminRole->syncPermissions(Permission::all());
            $this->command->info('✅ Admin role: all permissions assigned');
        }

        // Technician - Limited permissions
        $technicianRole = Role::where('name', 'technician')->first();
        if ($technicianRole) {
            $technicianPermissions = [
                'customers.view',
                'equipments.view',
                'work_orders.view',
                'work_orders.create',
                'work_orders.edit',
                'parts.view',
                'workshop.view',
                'workshop.create',
                'workshop.edit',
                'visits.view',
                'visits.create',
                'visits.edit',
                'visits.check_in',
                'visits.check_out',
                'budgets.view',
                'files.view',
                'files.upload',
                'reports.view',
            ];
            $technicianRole->syncPermissions($technicianPermissions);
            $this->command->info('✅ Technician role: permissions assigned');
        }

        // Supervisor - Read permissions + some management
        $supervisorRole = Role::where('name', 'supervisor')->first();
        if ($supervisorRole) {
            $supervisorPermissions = [
                'users.view',
                'customers.view',
                'equipments.view',
                'work_orders.view',
                'work_orders.approve',
                'work_orders.assign',
                'parts.view',
                'workshop.view',
                'subscriptions.view',
                'visits.view',
                'budgets.view',
                'budgets.approve',
                'budgets.reject',
                'files.view',
                'reports.view',
                'reports.export',
                'settings.view',
            ];
            $supervisorRole->syncPermissions($supervisorPermissions);
            $this->command->info('✅ Supervisor role: permissions assigned');
        }

        // Customer Viewer - Only read permissions
        $customerViewerRole = Role::where('name', 'customer_viewer')->first();
        if ($customerViewerRole) {
            $customerViewerPermissions = [
                'customers.view',
                'equipments.view',
                'work_orders.view',
                'subscriptions.view',
                'visits.view',
                'budgets.view',
                'files.view',
            ];
            $customerViewerRole->syncPermissions($customerViewerPermissions);
            $this->command->info('✅ Customer Viewer role: permissions assigned');
        }
    }
}
