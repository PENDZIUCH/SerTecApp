content = '''<?php

namespace App\Services;

use App\Mail\ParteCompletadoMail;
use App\Mail\ParteRechazadoMail;
use App\Models\User;
use App\Models\WorkOrder;
use App\Models\WorkPart;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class WorkPartService
{
    /**
     * Tecnico envia el parte desde la PWA.
     * Crea el WorkPart, actualiza la orden, notifica supervisores y envia email al cliente.
     */
    public function submit(array ): WorkPart
    {
         = null;

        DB::transaction(function () use (, &) {
             = WorkPart::create([
                'work_order_id' => ['orden_id'],
                'technician_id' => ['tecnico_id'],
                'diagnosis'     => ['diagnostico'],
                'work_done'     => ['trabajo_realizado'],
                'parts_used'    => ['repuestos_usados'] ?? null,
                'signature'     => ['firma_base64'],
                'photos'        => ['fotos'] ?? null,
                'status'        => 'pending_approval',
                'latitude'      => ['lat'] ?? null,
                'longitude'     => ['lng'] ?? null,
            ]);

            WorkOrder::where('id', ['orden_id'])->update([
                'status'       => 'completed',
                'completed_at' => now(),
            ]);
        });

        // Email al cliente
        try {
            ->load(['workOrder.customer', 'technician']);
             = ->workOrder->customer->email ?? null;
            if () {
                Mail::to()->send(new ParteCompletadoMail());
            }
        } catch (\Exception ) {
            Log::warning('WorkPartService::submit — email error: ' . ->getMessage());
        }

        // Notificaciones a supervisores
        try {
                = User::find(['tecnico_id']);
                  = WorkOrder::with('customer')->find(['orden_id']);
             = User::role(['supervisor', 'super_admin'])->get();

            foreach ( as ) {
                \Filament\Notifications\Notification::make()
                    ->title('Nuevo parte pendiente de aprobacion')
                    ->body("Orden #{->id} - {->customer->business_name} completada por {->name}")
                    ->icon('heroicon-o-clipboard-document-check')
                    ->iconColor('warning')
                    ->actions([
                        \Filament\Notifications\Actions\Action::make('ver')
                            ->label('Ver Parte')
                            ->url(\App\Filament\Resources\WorkPartResource::getUrl('view', ['record' => ->id]))
                            ->markAsRead(),
                    ])
                    ->sendToDatabase();
            }
        } catch (\Exception ) {
            Log::warning('WorkPartService::submit — notif error: ' . ->getMessage());
        }

        return ;
    }

    /**
     * Supervisor aprueba el parte.
     */
    public function approve(WorkPart , array ): void
    {
        DB::transaction(function () use (, ) {
            ->update([
                'status'           => 'approved',
                'approved_at'      => now(),
                'supervisor_notes' => ['supervisor_notes'] ?? null,
            ]);
            ->workOrder->update([
                'status'       => 'completed',
                'completed_at' => now(),
            ]);
        });

        try {
             = ->technician;
            if () {
                \Filament\Notifications\Notification::make()
                    ->title('Parte Aprobado')
                    ->body('Tu parte de la Orden #' . ->work_order_id . ' fue aprobado.' .
                        (['supervisor_notes'] ? ' Nota: ' . ['supervisor_notes'] : ''))
                    ->success()
                    ->sendToDatabase();
            }
        } catch (\Exception ) {}
    }

    /**
     * Supervisor rechaza el parte y opcionalmente reasigna el tecnico.
     */
    public function reject(WorkPart , array ): void
    {
        DB::transaction(function () use (, ) {
            ->update([
                'status'           => 'rejected',
                'supervisor_notes' => ['supervisor_notes'],
            ]);
             = ['status' => 'pending', 'completed_at' => null];
            if (!empty(['nuevo_tecnico_id'])) {
                ['assigned_tech_id'] = ['nuevo_tecnico_id'];
            }
            ->workOrder->update();
        });

        try {
             = ->technician;
            if () {
                \Filament\Notifications\Notification::make()
                    ->title('Parte Rechazado')
                    ->body('Tu parte de la Orden #' . ->work_order_id . ' fue rechazado. Motivo: ' . ['supervisor_notes'])
                    ->danger()
                    ->sendToDatabase();
            }
        } catch (\Exception ) {}

        try {
             = ->workOrder->customer->email ?? null;
            if () {
                 = !empty(['nuevo_tecnico_id'])
                    ? User::find(['nuevo_tecnico_id'])?->name
                    : ->workOrder->assignedTech?->name;
                Mail::to()->send(new ParteRechazadoMail(->workOrder, ));
            }
        } catch (\Exception ) {}
    }
}
'''
open('app/Services/WorkPartService.php', 'w', encoding='utf-8').write(content)
print('OK WorkPartService')