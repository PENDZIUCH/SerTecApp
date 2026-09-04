<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateWorkOrderRequest extends FormRequest
{
    public function authorize(): bool
    {
        // work_orders.edit nunca se asigno al rol real 'tecnico' (el seeder usa 'technician'
        // en ingles) - delegar a la Policy, que ya contempla ownership por assigned_tech_id.
        return $this->user()->can('update', $this->route('work_order'));
    }

    public function rules(): array
    {
        return [
            'customer_id' => ['sometimes', 'exists:customers,id'],
            'equipment_id' => ['nullable', 'exists:equipments,id'],
            'title' => ['sometimes', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'priority' => ['sometimes', 'in:low,medium,high,urgent'],
            'assigned_tech_id' => ['nullable', 'exists:users,id'],
            'scheduled_date' => ['nullable', 'date'],
            'scheduled_time' => ['nullable', 'date_format:H:i'],
            'labor_cost' => ['nullable', 'numeric', 'min:0'],
        ];
    }
}
