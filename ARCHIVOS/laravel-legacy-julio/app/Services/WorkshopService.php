<?php

namespace App\Services;

use App\Models\WorkshopItem;

class WorkshopService
{
    public function create(array $data)
    {
        $data['created_by'] = auth()->id();
        return WorkshopItem::create($data);
    }

    public function update(WorkshopItem $item, array $data)
    {
        $data['updated_by'] = auth()->id();
        $item->update($data);
        return $item->fresh();
    }

    public function delete(WorkshopItem $item)
    {
        return $item->delete();
    }

    public function changeStatus(WorkshopItem $item, string $status)
    {
        $item->update(['status' => $status]);

        if ($status === 'delivered') {
            $item->update(['exit_date' => now()]);
            
            if ($item->equipment) {
                $item->equipment->update(['status' => 'active']);
            }
        }

        return $item;
    }
}
