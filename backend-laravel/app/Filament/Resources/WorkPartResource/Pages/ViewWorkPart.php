<?php

namespace App\Filament\Resources\WorkPartResource\Pages;

use App\Filament\Resources\WorkPartResource;
use Filament\Actions;
use Filament\Resources\Pages\ViewRecord;

class ViewWorkPart extends ViewRecord
{
    protected static string $resource = WorkPartResource::class;
    protected ?string $heading = 'Ver Parte de Trabajo';

    protected function getHeaderActions(): array
    {
        return [
            Actions\Action::make('volver')
                ->label('← Volver')
                ->color('gray')
                ->url(WorkPartResource::getUrl('index')),
            Actions\EditAction::make()
                ->label('Editar Parte'),
        ];
    }
}
