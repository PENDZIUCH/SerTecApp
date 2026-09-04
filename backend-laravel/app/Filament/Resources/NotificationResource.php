<?php

namespace App\Filament\Resources;

use App\Filament\Resources\NotificationResource\Pages;
use App\Models\Notification;
use Filament\Resources\Resource;
use Illuminate\Database\Eloquent\Builder;

class NotificationResource extends Resource
{
    protected static ?string $model = Notification::class;
    protected static ?string $navigationIcon = 'heroicon-o-bell';
    protected static bool $shouldRegisterNavigation = false; // Oculto del menú, se usa campanita

    private static function isAdminTier(): bool
    {
        return auth()->check() && auth()->user()->hasAnyRole(['super_admin', 'administrador', 'supervisor']);
    }

    /**
     * Sin esto, cualquier usuario autenticado que navegue a la URL directa
     * veia las notificaciones de TODOS los usuarios (sin scoping por user_id).
     */
    public static function getEloquentQuery(): Builder
    {
        $query = parent::getEloquentQuery();
        if (!static::isAdminTier()) {
            $query->where('user_id', auth()->id());
        }
        return $query;
    }

    public static function canCreate(): bool
    {
        // Crear notificaciones a nombre de otro usuario queda solo para admin-tier.
        return static::isAdminTier();
    }

    public static function canDelete($record): bool
    {
        return static::isAdminTier() || $record->user_id === auth()->id();
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListNotifications::route('/'),
        ];
    }
}
