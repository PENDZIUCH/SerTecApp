<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateEquipmentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('equipments.edit');
    }

    public function rules(): array
    {
        return [
            'customer_id' => ['sometimes', 'exists:customers,id'],
            'brand_id' => ['nullable', 'exists:equipment_brands,id'],
            'model_id' => ['nullable', 'exists:equipment_models,id'],
            'serial_number' => ['nullable', 'string', 'max:100'],
            'equipment_code' => ['nullable', 'string', 'max:50'],
            'purchase_date' => ['nullable', 'date'],
            'installation_date' => ['nullable', 'date'],
            'warranty_expiration' => ['nullable', 'date'],
            'next_service_date' => ['nullable', 'date'],
            'location' => ['nullable', 'string', 'max:255'],
            'status' => ['sometimes', 'in:active,inactive,in_workshop,decommissioned'],
            'notes' => ['nullable', 'string'],
        ];
    }
}
