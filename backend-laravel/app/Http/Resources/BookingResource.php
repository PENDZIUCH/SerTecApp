<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class BookingResource extends JsonResource
{
    public function toArray($request)
    {
        return [
            'id' => $this->id,
            'resource_type' => $this->resource_type,
            'resource_id' => $this->resource_id,
            'resource' => $this->whenLoaded('resource'),
            'subject_type' => $this->subject_type,
            'subject_id' => $this->subject_id,
            'subject' => $this->whenLoaded('subject'),
            'starts_at' => $this->starts_at,
            'ends_at' => $this->ends_at,
            'status' => $this->status,
            'check_in' => $this->check_in,
            'check_out' => $this->check_out,
            'duration_minutes' => $this->calculateDuration(),
            'latitude' => $this->latitude,
            'longitude' => $this->longitude,
            'metadata' => $this->metadata,
            'notes' => $this->notes,
            'created_at' => $this->created_at,
        ];
    }
}
