<?php

namespace App\Services;

use App\Filament\Resources\WorkOrderResource;
use App\Models\User;
use App\Models\WorkOrder;
use Filament\Notifications\Actions\Action as FilamentAction;
use Filament\Notifications\Notification as FilamentNotification;
use Illuminate\Support\Facades\Log;

// Notifica al tecnico asignado cuando se crea o reasigna una orden de
// trabajo. Antes de esta feature (2026-09-18) esto solo pasaba para
// ordenes creadas desde Filament (CreateWorkOrder::afterCreate) - ni
// siquiera in-app - y nunca para reasignaciones (EditWorkOrder) ni para
// ordenes creadas/reasignadas via la API (WorkOrderController ->
// WorkOrderService, que es el camino que usa la seccion admin de la PWA,
// ver app/admin/orden/[id]/_client.tsx en sertecapp-tecnicos). Centralizado
// aca para que los 3 entry points (Filament create, Filament edit, API)
// llamen lo mismo en vez de triplicar la logica.
class WorkOrderNotifier
{
    public static function notifyAssignedTechnician(WorkOrder $workOrder): void
    {
        $technicianId = $workOrder->assigned_tech_id;

        if (! $technicianId) {
            return;
        }

        $technician = $workOrder->relationLoaded('assignedTech')
            ? $workOrder->assignedTech
            : User::find($technicianId);

        if (! $technician) {
            return;
        }

        $customerName = $workOrder->relationLoaded('customer')
            ? ($workOrder->customer->business_name ?? null)
            : ($workOrder->customer?->business_name ?? null);

        $body = "Se te asignó la Orden #{$workOrder->id}" . ($customerName ? " — {$customerName}" : '');
        $url = WorkOrderResource::getUrl('edit', ['record' => $workOrder->id]);

        // Notificacion in-app de Filament - siempre se manda, no depende
        // del toggle de push (ver App\Services\PushNotificationDispatcher).
        try {
            FilamentNotification::make()
                ->title('Nueva orden asignada')
                ->body($body)
                ->icon('heroicon-o-clipboard-document-list')
                ->iconColor('warning')
                ->actions([
                    FilamentAction::make('ver')
                        ->label('Ver Orden')
                        ->url($url)
                        ->markAsRead(),
                ])
                ->sendToDatabase($technician);
        } catch (\Exception $e) {
            Log::warning('Error enviando notificación de orden asignada: ' . $e->getMessage());
        }

        // Web Push, solo si el evento esta activo (lookup_values,
        // category='push_notification_events', value='orden_nueva_asignada').
        PushNotificationDispatcher::send(
            'orden_nueva_asignada',
            $technician,
            'Nueva orden asignada',
            $body,
            $url,
        );
    }
}
