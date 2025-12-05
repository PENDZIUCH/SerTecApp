<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class EquipmentResource extends JsonResource
{
    public function toArray($request)
    {
        return [
            'id' => $this->id,
            'customer' => new CustomerResource($this->whenLoaded('customer')),
            'brand' => $this->whenLoaded('brand', fn() => [
                'id' => $this->brand->id,
                'name' => $this->brand->name,
            ]),
            'model' => $this->whenLoaded('model', fn() => [
                'id' => $this->model->id,
                'name' => $this->model->name,
            ]),
            'serial_number' => $this->serial_number,
            'equipment_code' => $this->equipment_code,
            'purchase_date' => $this->purchase_date,
            'warranty_expiration' => $this->warranty_expiration,
            'next_service_date' => $this->next_service_date,
            'location' => $this->location,
            'status' => $this->status,
            'needs_service' => $this->needsService(),
            'warranty_valid' => $this->isWarrantyValid(),
            'created_at' => $this->created_at,
        ];
    }
}
