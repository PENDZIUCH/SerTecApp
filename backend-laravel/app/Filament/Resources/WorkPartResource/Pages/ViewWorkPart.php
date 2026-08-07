<?php

namespace App\Filament\Resources\WorkPartResource\Pages;

use App\Filament\Resources\WorkPartResource;
use App\Services\WorkPartService;
use Filament\Actions;
use Filament\Forms;
use Filament\Notifications\Notification;
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

            Actions\Action::make('approve')
                ->label('Aprobar')
                ->icon('heroicon-o-check-circle')
                ->color('success')
                ->visible(fn () => $this->record->status === 'pending_approval')
                ->form([
                    Forms\Components\Textarea::make('supervisor_notes')
                        ->label('Notas para el técnico (opcional)')
                        ->placeholder('Trabajo bien realizado, todo correcto...')
                        ->rows(3),
                ])
                ->action(function (array $data) {
                    app(WorkPartService::class)->approve($this->record, $data);
                    Notification::make()->title('Parte Aprobado')->success()->send();
                    $this->redirect(WorkPartResource::getUrl('index'));
                }),

            Actions\Action::make('reject')
                ->label('Rechazar')
                ->icon('heroicon-o-x-circle')
                ->color('danger')
                ->visible(fn () => $this->record->status === 'pending_approval')
                ->form([
                    Forms\Components\Textarea::make('supervisor_notes')
                        ->label('Motivo del rechazo (obligatorio)')
                        ->required()
                        ->placeholder('Indicar qué debe corregir el técnico...')
                        ->rows(3),
                    Forms\Components\Select::make('nuevo_tecnico_id')
                        ->label('Técnico asignado')
                        ->options(fn () => \App\Models\User::role('técnico')->pluck('name', 'id'))
                        ->default(fn () => $this->record->workOrder->assigned_tech_id)
                        ->searchable()
                        ->required(),
                ])
                ->action(function (array $data) {
                    app(WorkPartService::class)->reject($this->record, $data);
                    Notification::make()->title('Parte Rechazado — Orden volvió a Pendiente')->warning()->send();
                    $this->redirect(WorkPartResource::getUrl('index'));
                }),

            Actions\EditAction::make()
                ->label('Editar Parte'),
        ];
    }
}
