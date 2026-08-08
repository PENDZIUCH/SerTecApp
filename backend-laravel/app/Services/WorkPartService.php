<?php

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
    public function submit(array $data): WorkPart
    {
        $parte = null;
        DB::transaction(function () use ($data, &$parte) {
            $parte = WorkPart::create([
                'work_order_id' => $data['orden_id'],
                'technician_id' => $data['tecnico_id'],
                'diagnosis'     => $data['diagnostico'],
                'work_done'     => $data['trabajo_realizado'],
                'parts_used'    => $data['repuestos_usados'] ?? null,
                'signature'     => $data['firma_base64'],
                'photos'        => $data['fotos'] ?? null,
                'status'        => 'pending_approval',
                'latitude'      => $data['lat'] ?? null,
                'longitude'     => $data['lng'] ?? null,
            ]);
            WorkOrder::where('id', $data['orden_id'])->update([
                'status'       => 'completed',
                'completed_at' => now(),
            ]);
        });
        try {
            $parte->load(['workOrder.customer', 'technician']);
            $customerEmail = $parte->workOrder->customer->email ?? null;
            if ($customerEmail) {
                Mail::to($customerEmail)->send(new ParteCompletadoMail($parte));
            }
        } catch (\Exception $e) {
            Log::warning('WorkPartService::submit email: ' . $e->getMessage());
        }
        try {
            $tecnico     = User::find($data['tecnico_id']);
            $order       = WorkOrder::with('customer')->find($data['orden_id']);
            $supervisors = User::role(['supervisor', 'super_admin'])->get();
            foreach ($supervisors as $supervisor) {
                \Filament\Notifications\Notification::make()
                    ->title('Nuevo parte pendiente de aprobacion')
                    ->body("Orden #{$order->id} — {$order->customer->business_name} — {$tecnico->name}")
                    ->icon('heroicon-o-clipboard-document-check')
                    ->iconColor('warning')
                    ->sendToDatabase($supervisor);
            }
        } catch (\Exception $e) {
            Log::warning('WorkPartService::submit notif: ' . $e->getMessage());
        }
        return $parte;
    }

    public function approve(WorkPart $part, array $data): void
    {
        DB::transaction(function () use ($part, $data) {
            $part->update([
                'status'           => 'approved',
                'approved_at'      => now(),
                'supervisor_notes' => $data['supervisor_notes'] ?? null,
            ]);
            $part->workOrder->update(['status' => 'completed', 'completed_at' => now()]);
        });
        try {
            $tecnico = $part->technician;
            if ($tecnico) {
                \Filament\Notifications\Notification::make()
                    ->title('Parte Aprobado')
                    ->body('Tu parte de la Orden #' . $part->work_order_id . ' fue aprobado.' .
                        ($data['supervisor_notes'] ? ' Nota: ' . $data['supervisor_notes'] : ''))
                    ->success()
                    ->sendToDatabase($tecnico);
            }
        } catch (\Exception $e) {}
    }

    public function reject(WorkPart $part, array $data): void
    {
        DB::transaction(function () use ($part, $data) {
            $part->update(['status' => 'rejected', 'supervisor_notes' => $data['supervisor_notes']]);
            $orderData = ['status' => 'pending', 'completed_at' => null];
            if (!empty($data['nuevo_tecnico_id'])) {
                $orderData['assigned_tech_id'] = $data['nuevo_tecnico_id'];
            }
            $part->workOrder->update($orderData);
        });
        try {
            $tecnico = $part->technician;
            if ($tecnico) {
                \Filament\Notifications\Notification::make()
                    ->title('Parte Rechazado')
                    ->body('Tu parte de la Orden #' . $part->work_order_id . ' fue rechazado. Motivo: ' . $data['supervisor_notes'])
                    ->danger()
                    ->sendToDatabase($tecnico);
            }
        } catch (\Exception $e) {}
        try {
            $customerEmail = $part->workOrder->customer->email ?? null;
            if ($customerEmail) {
                $nuevoTecnico = !empty($data['nuevo_tecnico_id'])
                    ? User::find($data['nuevo_tecnico_id'])?->name
                    : $part->workOrder->assignedTech?->name;
                Mail::to($customerEmail)->send(new ParteRechazadoMail($part->workOrder, $nuevoTecnico));
            }
        } catch (\Exception $e) {}
    }
}