<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class EquipmentModelResource extends JsonResource
{
    public function toArray($request): array
    {
        return [
            'id' => $this->id,
            'brand' => new EquipmentBrandResource($this->whenLoaded('brand')),
            'name' => $this->name,
            'model_code' => $this->model_code,
            'category' => $this->category,
            'description' => $this->description,
        ];
    }
}
