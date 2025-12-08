<?php

namespace App\Filament\Resources;

use App\Filament\Resources\NotificationResource\Pages;
use App\Models\Notification;
use Filament\Resources\Resource;

class NotificationResource extends Resource
{
    protected static ?string $model = Notification::class;
    protected static ?string $navigationIcon = 'heroicon-o-bell';
    protected static bool $shouldRegisterNavigation = false; // Oculto del menú, se usa campanita

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListNotifications::route('/'),
        ];
    }
}
