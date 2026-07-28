<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class PartResource extends JsonResource
{
    public function toArray($request)
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'sku' => $this->sku,
            'description' => $this->description,
            'unit_cost' => $this->unit_cost,
            'stock_qty' => $this->stock_qty,
            'min_stock_level' => $this->min_stock_level,
            'is_low_stock' => $this->isLowStock(),
            'total_value' => $this->calculateTotalValue(),
            'is_active' => $this->is_active,
            'created_at' => $this->created_at,
        ];
    }
}
