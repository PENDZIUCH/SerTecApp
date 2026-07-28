<?php

namespace App\Filament\Resources\WorkPartResource\Pages;

use App\Filament\Resources\WorkPartResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListWorkParts extends ListRecords
{
    protected static string $resource = WorkPartResource::class;
    protected ?string $heading = 'Partes de Trabajo';

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make()->label('Nuevo Parte'),
        ];
    }
}
