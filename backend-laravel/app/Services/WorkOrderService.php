<?php

namespace App\Services;

use App\Models\Booking;
use App\Models\User;
use App\Models\WorkOrder;

class WorkOrderService
{
    public function create(array $data)
    {
        $data['wo_number'] = $this->generateWoNumber();
        $data['created_by'] = auth()->id();

        $estimatedDuration = $data['estimated_duration_minutes'] ?? null;
        $workOrder = WorkOrder::create($data);

        $this->syncBooking($workOrder, $estimatedDuration);

        return $workOrder;
    }

    public function update(WorkOrder $workOrder, array $data)
    {
        $data['updated_by'] = auth()->id();
        $estimatedDuration = $data['estimated_duration_minutes'] ?? null;
        $workOrder->update($data);

        $this->syncBooking($workOrder->fresh(), $estimatedDuration);

        return $workOrder->fresh();
    }

    /**
     * Primer consumidor real del motor de agenda generico (App\Models\Booking):
     * cuando una orden de trabajo tiene tecnico asignado + fecha programada,
     * mantiene sincronizado un Booking donde resource=tecnico (App\Models\User)
     * y subject=la orden (App\Models\WorkOrder). No toca ni migra Visit -
     * ambos sistemas conviven en paralelo por ahora.
     */
    private function syncBooking(WorkOrder $workOrder, ?int $estimatedDurationMinutes = null): void
    {
        $existing = Booking::where('subject_type', WorkOrder::class)
            ->where('subject_id', $workOrder->id)
            ->first();

        if (!$workOrder->assigned_tech_id || !$workOrder->scheduled_date) {
            // Sin tecnico o sin fecha no hay nada que agendar; si ya existia
            // un booking (p.ej. se desasigno el tecnico), se elimina para no
            // dejar agenda huerfana.
            $existing?->delete();
            return;
        }

        $startsAt = \Illuminate\Support\Carbon::parse($workOrder->scheduled_date->toDateString())
            ->setTimeFromTimeString($workOrder->scheduled_time ?? '00:00');

        $duration = $estimatedDurationMinutes ?? 60;
        $endsAt = $startsAt->copy()->addMinutes($duration);

        Booking::updateOrCreate(
            ['subject_type' => WorkOrder::class, 'subject_id' => $workOrder->id],
            [
                'resource_type' => User::class,
                'resource_id' => $workOrder->assigned_tech_id,
                'starts_at' => $startsAt,
                'ends_at' => $endsAt,
                'status' => $existing->status ?? 'scheduled',
                'created_by' => $existing->created_by ?? auth()->id(),
            ]
        );
    }

    public function delete(WorkOrder $workOrder)
    {
        return $workOrder->delete();
    }

    public function changeStatus(WorkOrder $workOrder, string $status)
    {
        $previousStatus = $workOrder->status;
        $workOrder->update(['status' => $status]);

        $this->addLog($workOrder, 'status_change', "Status changed from {$previousStatus} to {$status}");

        if ($status === 'in_progress' && !$workOrder->started_at) {
            $workOrder->update(['started_at' => now()]);
        }

        if ($status === 'completed' && !$workOrder->completed_at) {
            $workOrder->update(['completed_at' => now()]);
        }

        return $workOrder;
    }

    public function addLog(WorkOrder $workOrder, string $type, string $message)
    {
        return $workOrder->logs()->create([
            'log_type' => $type,
            'message' => $message,
            'created_by' => auth()->id(),
            'created_at' => now(),
        ]);
    }

    public function addPart(WorkOrder $workOrder, int $partId, int $quantity, float $unitCost)
    {
        $part = $workOrder->partsUsed()->create([
            'part_id' => $partId,
            'quantity' => $quantity,
            'unit_cost' => $unitCost,
        ]);

        $this->recalculateCosts($workOrder);

        return $part;
    }

    public function recalculateCosts(WorkOrder $workOrder)
    {
        $partsCost = $workOrder->partsUsed()->sum('total_cost');
        $workOrder->update([
            'parts_cost' => $partsCost,
            'total_cost' => $workOrder->labor_cost + $partsCost,
        ]);
    }

    private function generateWoNumber()
    {
        $prefix = 'WO';
        $date = now()->format('Ymd');
        $last = WorkOrder::whereDate('created_at', today())->count() + 1;
        
        return sprintf('%s-%s-%04d', $prefix, $date, $last);
    }
}
