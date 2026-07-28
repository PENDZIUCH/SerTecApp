<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class SubscriptionResource extends JsonResource
{
    public function toArray($request)
    {
        return [
            'id' => $this->id,
            'customer' => new CustomerResource($this->whenLoaded('customer')),
            'plan_name' => $this->plan_name,
            'visits_per_period' => $this->visits_per_period,
            'visits_used' => $this->visits_used,
            'billing_cycle' => $this->billing_cycle,
            'renewal_date' => $this->renewal_date,
            'status' => $this->status,
            'auto_renew' => $this->auto_renew,
            'is_expired' => $this->isExpired(),
            'has_visits_available' => $this->hasVisitsAvailable(),
            'created_at' => $this->created_at,
        ];
    }
}
