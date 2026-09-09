<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreWorkOrderRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('create', \App\Models\WorkOrder::class);
    }

    // Toda orden nueva requiere firma del cliente, sin excepcion (decision de
    // Hugo, 2026-09-09) - se fuerza aca antes de validar, sin importar lo que
    // mande el cliente (checkbox de la PWA, o cualquier otro caller futuro),
    // asi ningun camino de creacion puede dejarla en false.
    protected function prepareForValidation(): void
    {
        $this->merge(['requires_signature' => true]);
    }

    public function rules(): array
    {
        return [
            'customer_id' => ['required', 'exists:customers,id'],
            'equipment_id' => ['nullable', 'exists:equipments,id'],
            'title' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'priority' => ['in:low,medium,high,urgent'],
            // Requerido (antes nullable) - se detectaron 7 ordenes huerfanas
            // sin tecnico (datos viejos de enero 2026), y el validador era el
            // unico de los 3 caminos de creacion (Filament, PWA, API directa)
            // que todavia lo permitia vacio. Filament y la PWA ya lo exigen
            // en el formulario; esto cierra el agujero de raiz.
            'assigned_tech_id' => ['required', 'exists:users,id'],
            'scheduled_date' => ['nullable', 'date'],
            'scheduled_time' => ['nullable', 'date_format:H:i'],
            'requires_signature' => ['boolean'],
        ];
    }
}
