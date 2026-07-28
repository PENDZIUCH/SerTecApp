<?php

namespace App\Filament\Resources\WorkPartResource\Pages;

use App\Filament\Resources\WorkPartResource;
use Filament\Actions;
use Filament\Resources\Pages\ViewRecord;

class ViewWorkPart extends ViewRecord
{
    protected static string $resource = WorkPartResource::class;
    
    protected function getHeaderActions(): array
    {
        return [
            Actions\EditAction::make()
                ->label('Editar Parte'),
        ];
    }
}
