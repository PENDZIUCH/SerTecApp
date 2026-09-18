<?php

namespace App\Filament\Resources\WorkOrderResource\Pages;

use App\Filament\Resources\WorkOrderResource;
use App\Services\WorkOrderNotifier;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditWorkOrder extends EditRecord
{
    protected static string $resource = WorkOrderResource::class;
    protected ?string $heading = 'Editar Orden de Trabajo';

    // Capturado en beforeSave() (antes de que el record se actualice) para
    // poder detectar una reasignacion de tecnico en afterSave() - antes de
    // esta feature (2026-09-18) reasignar el tecnico desde "Editar Orden"
    // no disparaba ningun aviso, ni in-app ni push (ver WorkOrderNotifier).
    private ?int $previousAssignedTechId = null;

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }

    protected function beforeSave(): void
    {
        $this->previousAssignedTechId = $this->record->assigned_tech_id;
    }

    protected function afterSave(): void
    {
        $newTechId = $this->record->assigned_tech_id;

        if ($newTechId && $newTechId !== $this->previousAssignedTechId) {
            WorkOrderNotifier::notifyAssignedTechnician($this->record->fresh(['customer', 'assignedTech']));
        }
    }

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make()->label('Eliminar'),
        ];
    }
}
