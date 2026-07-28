<?php

namespace App\Filament\Resources\WorkshopItemResource\Pages;

use App\Filament\Resources\WorkshopItemResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditWorkshopItem extends EditRecord
{
    protected static string $resource = WorkshopItemResource::class;
    protected ?string $heading = 'Editar Ítem de Taller';

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make()->label('Eliminar'),
        ];
    }
}
