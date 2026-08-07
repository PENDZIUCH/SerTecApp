<?php

namespace App\Filament\Resources\WorkPartResource\Pages;

use App\Filament\Resources\WorkPartResource;
use App\Models\WorkPart;
use Filament\Actions;
use Filament\Forms;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ViewRecord;
use Illuminate\Support\Facades\DB;
use App\Mail\ParteRechazadoMail;
use Illuminate\Support\Facades\Mail;

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
                    DB::transaction(function () use ($data) {
                        $this->record->update([
                            'status'           => 'approved',
                            'approved_at'      => now(),
                            'supervisor_notes' => $data['supervisor_notes'] ?? null,
                        ]);
                        $this->record->workOrder->update([
                            'status'       => 'completed',
                            'completed_at' => now(),
                        ]);
                    });

                    try {
                        $tecnico = $this->record->technician;
                        if ($tecnico) {
                            \Filament\Notifications\Notification::make()
                                ->title('✅ Parte Aprobado')
                                ->body('Tu parte de la Orden #' . $this->record->work_order_id . ' fue aprobado.' .
                                    ($data['supervisor_notes'] ? ' Nota: ' . $data['supervisor_notes'] : ''))
                                ->success()
                                ->sendToDatabase($tecnico);
                        }
                    } catch (\Exception $e) {}

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
                    DB::transaction(function () use ($data) {
                        $this->record->update([
                            'status'           => 'rejected',
                            'supervisor_notes' => $data['supervisor_notes'],
                        ]);
                        $orderData = ['status' => 'pending', 'completed_at' => null];
                        if (!empty($data['nuevo_tecnico_id'])) {
                            $orderData['assigned_tech_id'] = $data['nuevo_tecnico_id'];
                        }
                        $this->record->workOrder->update($orderData);
                    });

                    try {
                        $tecnico = $this->record->technician;
                        if ($tecnico) {
                            \Filament\Notifications\Notification::make()
                                ->title('❌ Parte Rechazado')
                                ->body('Tu parte de la Orden #' . $this->record->work_order_id . ' fue rechazado. Motivo: ' . $data['supervisor_notes'])
                                ->danger()
                                ->sendToDatabase($tecnico);
                        }
                    } catch (\Exception $e) {}

                    // Email al cliente
                    try {
                        $customerEmail = $this->record->workOrder->customer->email ?? null;
                        if ($customerEmail) {
                            $nuevoTecnico = !empty($data['nuevo_tecnico_id'])
                                ? \App\Models\User::find($data['nuevo_tecnico_id'])?->name
                                : $this->record->workOrder->assignedTech?->name;
                            Mail::to($customerEmail)->send(new ParteRechazadoMail($this->record->workOrder, $nuevoTecnico));
                        }
                    } catch (\Exception $e) {}

                    Notification::make()->title('Parte Rechazado — Orden volvió a Pendiente')->warning()->send();
                    $this->redirect(WorkPartResource::getUrl('index'));
                }),

            Actions\EditAction::make()
                ->label('Editar Parte'),
        ];
    }
}
