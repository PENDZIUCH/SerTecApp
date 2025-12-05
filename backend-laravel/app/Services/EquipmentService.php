<?php

namespace App\Services;

use App\Models\Equipment;

class EquipmentService
{
    public function create(array $data)
    {
        return Equipment::create($data);
    }

    public function update(Equipment $equipment, array $data)
    {
        $equipment->update($data);
        return $equipment->fresh();
    }

    public function delete(Equipment $equipment)
    {
        return $equipment->delete();
    }

    public function addHistory(Equipment $equipment, array $data)
    {
        $data['created_by'] = auth()->id();
        return $equipment->history()->create($data);
    }

    public function uploadFile(Equipment $equipment, array $data)
    {
        $data['uploaded_by'] = auth()->id();
        return $equipment->files()->create($data);
    }

    public function changeStatus(Equipment $equipment, string $status, string $description = null)
    {
        $previousStatus = $equipment->status;
        $equipment->update(['status' => $status]);

        $this->addHistory($equipment, [
            'event_type' => 'status_change',
            'description' => $description ?? "Status changed from {$previousStatus} to {$status}",
            'previous_status' => $previousStatus,
            'new_status' => $status,
        ]);

        return $equipment;
    }
}
