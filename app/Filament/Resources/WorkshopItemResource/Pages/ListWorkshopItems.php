<?php

namespace App\Filament\Resources\WorkshopItemResource\Pages;

use App\Filament\Resources\WorkshopItemResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListWorkshopItems extends ListRecords
{
    protected static string $resource = WorkshopItemResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
