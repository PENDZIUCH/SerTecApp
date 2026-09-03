<?php

namespace App\Filament\Resources\SystemLogResource\Pages;

use App\Filament\Resources\SystemLogResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListSystemLogs extends ListRecords
{
    protected static string $resource = SystemLogResource::class;
    protected ?string $heading = 'Logs del Sistema';

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make()->label('Nuevo Log'),
        ];
    }
}
