<?php

namespace App\Filament\Resources\WorkOrderResource\Pages;

use App\Filament\Resources\WorkOrderResource;
use Filament\Actions;
use Filament\Resources\Pages\CreateRecord;

class CreateWorkOrder extends CreateRecord
{
    protected static string $resource = WorkOrderResource::class;
    protected ?string $heading = 'Crear Orden de Trabajo';

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }

    protected function afterCreate(): void
    {
        $record = $this->record;

        // Notificar al técnico asignado
        if ($record->assigned_tech_id) {
            $technician = \App\Models\User::find($record->assigned_tech_id);
            if ($technician) {
                try {
                    \Filament\Notifications\Notification::make()
                        ->title('Nueva orden asignada')
                        ->body("Se te asignó la Orden #{$record->id} — {$record->customer->business_name}")
                        ->icon('heroicon-o-clipboard-document-list')
                        ->iconColor('warning')
                        ->actions([
                            \Filament\Notifications\Actions\Action::make('ver')
                                ->label('Ver Orden')
                                ->url(WorkOrderResource::getUrl('edit', ['record' => $record->id]))
                                ->markAsRead(),
                        ])
                        ->sendToDatabase($technician);
                } catch (\Exception $e) {
                    \Log::warning('Error enviando notificación al técnico: ' . $e->getMessage());
                }
            }
        }
    }
}
