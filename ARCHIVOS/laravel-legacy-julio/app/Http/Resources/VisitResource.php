<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class VisitResource extends JsonResource
{
    public function toArray($request)
    {
        return [
            'id' => $this->id,
            'work_order' => new WorkOrderResource($this->whenLoaded('workOrder')),
            'assigned_tech' => new UserResource($this->whenLoaded('assignedTech')),
            'visit_date' => $this->visit_date,
            'scheduled_time' => $this->scheduled_time,
            'check_in' => $this->check_in,
            'check_out' => $this->check_out,
            'duration_minutes' => $this->duration_minutes,
            'status' => $this->status,
            'latitude' => $this->latitude,
            'longitude' => $this->longitude,
            'created_at' => $this->created_at,
        ];
    }
}
