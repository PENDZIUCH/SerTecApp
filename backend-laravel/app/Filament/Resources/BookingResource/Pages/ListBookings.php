<?php

namespace App\Filament\Resources\BookingResource\Pages;

use App\Filament\Resources\BookingResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListBookings extends ListRecords
{
    protected static string $resource = BookingResource::class;
    protected ?string $heading = 'Agenda';

    protected function getHeaderActions(): array
    {
        return [
            Actions\Action::make('armar-recorrido')
                ->label('Armar Recorrido')
                ->icon('heroicon-o-map')
                ->color('primary')
                ->url(fn () => BookingResource::getUrl('armar-recorrido')),
            Actions\CreateAction::make()->label('Nueva Visita'),
        ];
    }
}
