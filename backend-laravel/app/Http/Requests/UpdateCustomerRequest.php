<?php

namespace App\Http\Requests;

use App\Models\LookupValue;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateCustomerRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('update', $this->route('customer'));
    }

    public function rules(): array
    {
        // Tipos activos + el tipo actual del cliente, aunque ya no este
        // activo - si alguien desactiva un tipo desde el panel, los
        // clientes que ya lo tenian no deben romperse al editar otro campo
        // sin querer cambiarles el tipo.
        $tiposValidos = LookupValue::forCategory('customer_type')->active()->pluck('value');
        $tipoActual = $this->route('customer')?->customer_type;
        if ($tipoActual && ! $tiposValidos->contains($tipoActual)) {
            $tiposValidos->push($tipoActual);
        }

        return [
            'customer_type' => ['sometimes', Rule::in($tiposValidos)],
            'business_name' => ['sometimes', 'string', 'max:255'],
            'first_name' => ['sometimes', 'string', 'max:255'],
            'last_name' => ['sometimes', 'string', 'max:255'],
            'email' => ['nullable', 'email', 'max:255'],
            'phone' => ['nullable', 'string', 'max:20'],
            'tax_id' => ['nullable', 'string', 'max:50'],
            'address' => ['nullable', 'string'],
            'city' => ['nullable', 'string', 'max:100'],
            'state' => ['nullable', 'string', 'max:100'],
            'country' => ['nullable', 'string', 'max:100'],
            'postal_code' => ['nullable', 'string', 'max:20'],
            'is_active' => ['boolean'],
        ];
    }
}
