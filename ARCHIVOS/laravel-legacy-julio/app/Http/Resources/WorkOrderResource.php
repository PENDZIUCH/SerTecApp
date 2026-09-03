<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class WorkOrderResource extends JsonResource
{
    public function toArray($request)
    {
        return [
            'id' => $this->id,
            'wo_number' => $this->wo_number,
            'customer' => new CustomerResource($this->whenLoaded('customer')),
            'equipment' => new EquipmentResource($this->whenLoaded('equipment')),
            'assigned_tech' => new UserResource($this->whenLoaded('assignedTech')),
            'title' => $this->title,
            'description' => $this->description,
            'priority' => $this->priority,
            'status' => $this->status,
            'scheduled_date' => $this->scheduled_date,
            'started_at' => $this->started_at,
            'completed_at' => $this->completed_at,
            'labor_cost' => $this->labor_cost,
            'parts_cost' => $this->parts_cost,
            'total_cost' => $this->total_cost,
            'is_overdue' => $this->isOverdue(),
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
