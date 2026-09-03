<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreEquipmentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('equipments.create');
    }

    public function rules(): array
    {
        return [
            'customer_id' => ['required', 'exists:customers,id'],
            'brand_id' => ['nullable', 'exists:equipment_brands,id'],
            'model_id' => ['nullable', 'exists:equipment_models,id'],
            'serial_number' => ['nullable', 'string', 'max:100'],
            'equipment_code' => ['nullable', 'string', 'max:50'],
            'purchase_date' => ['nullable', 'date'],
            'installation_date' => ['nullable', 'date'],
            'warranty_expiration' => ['nullable', 'date'],
            'next_service_date' => ['nullable', 'date'],
            'location' => ['nullable', 'string', 'max:255'],
            'status' => ['in:active,inactive,in_workshop,decommissioned'],
            'notes' => ['nullable', 'string'],
        ];
    }
}
