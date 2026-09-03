<?php

namespace App\Filament\Resources\NotificationResource\Pages;

use App\Filament\Resources\NotificationResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListNotifications extends ListRecords
{
    protected static string $resource = NotificationResource::class;
    protected ?string $heading = 'Notificaciones';

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make()->label('Nueva Notificación'),
        ];
    }
}
