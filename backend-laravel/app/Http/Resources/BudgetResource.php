<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class BudgetResource extends JsonResource
{
    public function toArray($request)
    {
        return [
            'id' => $this->id,
            'customer' => new CustomerResource($this->whenLoaded('customer')),
            'title' => $this->title,
            'status' => $this->status,
            'valid_until' => $this->valid_until,
            'tax_percent' => $this->tax_percent,
            'discount_type' => $this->discount_type,
            'discount_value' => $this->discount_value,
            'subtotal_services' => $this->subtotal_services,
            'subtotal_parts' => $this->subtotal_parts,
            'discount_amount' => $this->discount_amount,
            'tax_amount' => $this->tax_amount,
            'total_amount' => $this->total_amount,
            'is_expired' => $this->isExpired(),
            'items' => BudgetItemResource::collection($this->whenLoaded('items')),
            'created_at' => $this->created_at,
        ];
    }
}
