<?php

namespace App\Services;

use App\Models\SystemSetting;

class ModuleManager
{
    // Modulos disponibles y sus defaults
    // true = activo por defecto (no rompe instancias existentes)
    protected static array $defaults = [
        'customers'    => true,
        'work_orders'  => true,
        'visits'       => true,
        'budgets'      => true,
        'parts'        => true,
        'workshop'     => true,
        'subscriptions' => true,
        'equipment'    => true,  // activo por default — Fitness Company lo usa
    ];

    public static function isActive(string $module): bool
    {
        try {
            $active = SystemSetting::get('active_modules', null);
            if (!$active) {
                return static::$defaults[$module] ?? true;
            }
            $modules = is_array($active) ? $active : json_decode($active, true);
            return $modules[$module] ?? static::$defaults[$module] ?? true;
        } catch (\Exception $e) {
            // Si algo falla, todos los modulos activos — nunca romper instancia existente
            return true;
        }
    }

    public static function all(): array
    {
        try {
            $saved = SystemSetting::get('active_modules', null);
            if (!$saved) return static::$defaults;
            $modules = is_array($saved) ? $saved : json_decode($saved, true);
            return array_merge(static::$defaults, $modules ?? []);
        } catch (\Exception $e) {
            return static::$defaults;
        }
    }

    public static function activate(string $module): void
    {
        $modules = static::all();
        $modules[$module] = true;
        SystemSetting::set('active_modules', json_encode($modules));
    }

    public static function deactivate(string $module): void
    {
        $modules = static::all();
        $modules[$module] = false;
        SystemSetting::set('active_modules', json_encode($modules));
    }
}
