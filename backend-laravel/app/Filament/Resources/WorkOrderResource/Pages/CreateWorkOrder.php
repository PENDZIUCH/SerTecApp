<?php

namespace App\Filament\Resources\WorkOrderResource\Pages;

use App\Filament\Resources\WorkOrderResource;
use App\Mail\OrdenCreadaMail;
use App\Services\WorkOrderNotifier;
use Filament\Actions;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Support\Facades\Mail;

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
        $record = $this->record->load(['customer', 'assignedTech']);

        // Email de contacto tal como quedó en el formulario (campo
        // "contact_email", no persiste en work_orders) - si difiere del que
        // tenía el cliente, ese pasa a ser el email vigente (el anterior
        // queda en secondary_email, ver Customer::updateEmailIfChanged). Ver
        // comentario en WorkOrderResource::form() sobre por qué existe este
        // campo.
        $contactEmail = trim((string) ($this->form->getState()['contact_email'] ?? ''));
        $record->customer->updateEmailIfChanged($contactEmail);

        if ($contactEmail) {
            try {
                Mail::to($contactEmail)->send(new OrdenCreadaMail($record));
            } catch (\Exception $e) {
                \Log::warning('Error enviando email de orden creada: ' . $e->getMessage());
            }
        } else {
            Notification::make()
                ->title('Orden creada sin aviso por email')
                ->body('El cliente no tiene email cargado, no se le envió ningún aviso.')
                ->warning()
                ->send();
        }

        // Notificar al técnico asignado (in-app + Web Push si el evento
        // 'orden_nueva_asignada' está activo — ver WorkOrderNotifier).
        WorkOrderNotifier::notifyAssignedTechnician($record);
    }
}
