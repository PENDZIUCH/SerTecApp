<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

/**
 * Unificacion Roles<->Permisos del 2026-09-04. SOLO agrega permisos
 * (givePermissionTo), nunca usa syncPermissions - no pisa nada de lo que
 * ya este asignado a mano en producción (ej. supervisor ya tenia 120
 * permisos Shield configurados manualmente antes de este seeder).
 *
 * Mapea el mismo acceso que hoy dan los hasAnyRole() hardcodeados en las
 * Policies (ver auditoria 2026-09-04) a permisos reales de Filament Shield,
 * para que el panel de Roles controle el acceso de verdad.
 */
class SyncShieldPermissionsSeeder extends Seeder
{
    private const ADMIN_TIER_RESOURCES = [
        'budget', 'customer', 'equipment', 'part', 'subscription',
        'visit', 'work::order', 'work::part', 'workshop::item', 'user',
    ];

    private const CRUD_SUFFIXES = [
        'view', 'view_any', 'create', 'update', 'delete', 'delete_any',
        'restore', 'restore_any', 'replicate', 'reorder',
        'force_delete', 'force_delete_any',
    ];

    public function run(): void
    {
        $this->giveAdminTierFullAccess('administrador');
        $this->giveAdminTierFullAccess('supervisor');
        $this->giveTechnicianAccess();

        $this->command?->info('✅ SyncShieldPermissionsSeeder: permisos Shield sincronizados (administrador, supervisor, técnico)');
    }

    /**
     * administrador/supervisor: CRUD completo en los 9 recursos de dominio +
     * Usuarios, igual al acceso "admin-tier" que antes daban los
     * hasAnyRole() hardcodeados en las Policies y en UserResource.
     */
    private function giveAdminTierFullAccess(string $roleName): void
    {
        $role = Role::where('name', $roleName)->first();
        if (!$role) {
            $this->command?->warn("Rol '$roleName' no existe, se salteó.");
            return;
        }

        foreach (self::ADMIN_TIER_RESOURCES as $resource) {
            foreach (self::CRUD_SUFFIXES as $suffix) {
                $permName = "{$suffix}_{$resource}";
                $permission = Permission::firstOrCreate(['name' => $permName, 'guard_name' => 'web']);
                if (!$role->hasPermissionTo($permission)) {
                    $role->givePermissionTo($permission);
                }
            }
        }
    }

    /**
     * técnico: view+create+update (sin delete) en lo que ya puede tocar
     * (ordenes/partes/visitas/taller), solo view en lo que ya puede ver
     * (clientes/repuestos/equipos/presupuestos). Nada en subscription/user -
     * mapeo exacto del acceso que ya tiene hoy via las permisos dot-notation.
     */
    private function giveTechnicianAccess(): void
    {
        $role = Role::where('name', 'técnico')->first();
        if (!$role) {
            $this->command?->warn("Rol 'técnico' no existe, se salteó.");
            return;
        }

        $readWrite = ['work::order', 'work::part', 'visit', 'workshop::item'];
        $readOnly = ['customer', 'part', 'equipment', 'budget'];

        foreach ($readWrite as $resource) {
            foreach (['view', 'view_any', 'create', 'update'] as $suffix) {
                $permission = Permission::firstOrCreate(['name' => "{$suffix}_{$resource}", 'guard_name' => 'web']);
                if (!$role->hasPermissionTo($permission)) {
                    $role->givePermissionTo($permission);
                }
            }
        }

        foreach ($readOnly as $resource) {
            foreach (['view', 'view_any'] as $suffix) {
                $permission = Permission::firstOrCreate(['name' => "{$suffix}_{$resource}", 'guard_name' => 'web']);
                if (!$role->hasPermissionTo($permission)) {
                    $role->givePermissionTo($permission);
                }
            }
        }
    }
}
