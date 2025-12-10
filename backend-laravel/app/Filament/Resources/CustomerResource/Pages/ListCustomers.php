<?php

namespace App\Filament\Resources\CustomerResource\Pages;

use App\Filament\Resources\CustomerResource;
use App\Filament\Imports\CustomerExcelImporter;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListCustomers extends ListRecords
{
    protected static string $resource = CustomerResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\ImportAction::make()
                ->label('Importar Excel')
                ->importer(CustomerExcelImporter::class)
                ->color('success')
                ->icon('heroicon-o-arrow-up-tray'),
            Actions\CreateAction::make(),
            Actions\Action::make('deleteAll')
                ->label('Eliminar Todos')
                ->icon('heroicon-o-trash')
                ->color('danger')
                ->requiresConfirmation()
                ->modalHeading('¿Eliminar todos los clientes?')
                ->modalDescription('Esta acción NO se puede deshacer. Se eliminarán TODOS los clientes de la base de datos.')
                ->modalSubmitActionLabel('Sí, eliminar todos')
                ->action(function () {
                    \App\Models\Customer::query()->delete();
                    \Filament\Notifications\Notification::make()
                        ->title('Clientes eliminados')
                        ->success()
                        ->send();
                }),
        ];
    }
}
