<?php

namespace App\Filament\Resources\WorkOrderResource\Pages;

use App\Filament\Resources\WorkOrderResource;
use App\Mail\OrdenCreadaMail;
use App\Services\WorkOrderNotifier;
use Filament\Actions;
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

        // Email al cliente
        try {
            $email = $record->customer->email ?? null;
            if ($email) {
                Mail::to($email)->send(new OrdenCreadaMail($record));
            }
        } catch (\Exception $e) {
            \Log::warning('Error enviando email de orden creada: ' . $e->getMessage());
        }

        // Notificar al técnico asignado (in-app + Web Push si el evento
        // 'orden_nueva_asignada' está activo — ver WorkOrderNotifier).
        WorkOrderNotifier::notifyAssignedTechnician($record);
    }
}
