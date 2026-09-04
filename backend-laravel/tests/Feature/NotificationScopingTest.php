<?php

namespace Tests\Feature;

use App\Models\User;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

/**
 * Hallazgo del barrido de seguridad del 2026-09-04: NotificationResource no
 * tenia ningun canAccess/canViewAny/scoping - cualquier usuario autenticado
 * que navegara a la URL directa podia intentar ver/crear notificaciones de
 * cualquier usuario. Se le agrego canCreate()/getEloquentQuery() acorde al
 * patron del resto del panel.
 *
 * OJO: al escribir estos tests se encontro que App\Models\Notification no
 * coincide con la tabla real 'notifications' (esquema estandar de Laravel -
 * notifiable_type/notifiable_id/data JSON - no las columnas user_id/title/
 * message que el modelo declara en $fillable). No hay ninguna otra
 * referencia a este modelo en el codebase - es codigo muerto que nunca
 * pudo funcionar (la campanita real de notificaciones usa el sistema nativo
 * de Filament sobre la tabla estandar). Por eso este test solo cubre
 * canCreate(), que no toca la tabla - no tiene sentido probar el scoping de
 * filas contra un modelo que no puede leer/escribir de verdad. Documentado
 * en CLAUDE.md como candidato a limpieza (borrar Resource+Model o arreglar
 * el esquema) - no se borro nada sin que Hugo lo confirme.
 */
class NotificationScopingTest extends TestCase
{
    public function test_only_admin_tier_can_create_notifications(): void
    {
        Role::create(['name' => 'técnico', 'guard_name' => 'web']);
        Role::create(['name' => 'super_admin', 'guard_name' => 'web']);

        $tecnico = User::factory()->create();
        $tecnico->assignRole('técnico');
        auth()->login($tecnico);
        $this->assertFalse(\App\Filament\Resources\NotificationResource::canCreate());

        $admin = User::factory()->create();
        $admin->assignRole('super_admin');
        auth()->login($admin);
        $this->assertTrue(\App\Filament\Resources\NotificationResource::canCreate());
    }
}
