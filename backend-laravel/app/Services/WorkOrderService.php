<?php

namespace App\Services;

use App\Models\WorkOrder;

class WorkOrderService
{
    public function create(array $data)
    {
        $data['wo_number'] = $this->generateWoNumber();
        $data['created_by'] = auth()->id();
        
        return WorkOrder::create($data);
    }

    public function update(WorkOrder $workOrder, array $data)
    {
        $data['updated_by'] = auth()->id();
        $workOrder->update($data);
        return $workOrder->fresh();
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
