<?php

namespace App\Services;

use App\Mail\ParteRechazadoMail;
use App\Models\User;
use App\Models\WorkPart;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;

class WorkPartService
{
    public function approve(WorkPart $part, array $data): void
    {
        DB::transaction(function () use ($part, $data) {
            $part->update([
                'status'           => 'approved',
                'approved_at'      => now(),
                'supervisor_notes' => $data['supervisor_notes'] ?? null,
            ]);
            $part->workOrder->update([
                'status'       => 'completed',
                'completed_at' => now(),
            ]);
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
            $part->update([
                'status'           => 'rejected',
                'supervisor_notes' => $data['supervisor_notes'],
            ]);
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
