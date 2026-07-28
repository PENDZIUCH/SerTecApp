<?php

namespace App\Filament\Resources\WorkPartResource\Pages;

use App\Filament\Resources\WorkPartResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditWorkPart extends EditRecord
{
    protected static string $resource = WorkPartResource::class;
    protected ?string $heading = 'Editar Parte de Trabajo';

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make()->label('Eliminar'),
        ];
    }
}
