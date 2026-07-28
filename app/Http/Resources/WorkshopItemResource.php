<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class WorkshopItemResource extends JsonResource
{
    public function toArray($request)
    {
        return [
            'id' => $this->id,
            'equipment' => new EquipmentResource($this->whenLoaded('equipment')),
            'customer' => new CustomerResource($this->whenLoaded('customer')),
            'status' => $this->status,
            'entry_date' => $this->entry_date,
            'estimated_completion_date' => $this->estimated_completion_date,
            'exit_date' => $this->exit_date,
            'days_in_workshop' => $this->daysInWorkshop,
            'is_overdue' => $this->isOverdue(),
            'assigned_tech' => new UserResource($this->whenLoaded('assignedTech')),
            'created_at' => $this->created_at,
        ];
    }
}
