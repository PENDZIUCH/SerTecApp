<?php

namespace App\Http\Requests;

use App\Models\LookupValue;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreCustomerRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('create', \App\Models\Customer::class);
    }

    public function rules(): array
    {
        return [
            // Tipos validos = lookup_values activos de la categoria
            // 'customer_type' (administrable por admin/supervisor en
            // Filament, ver LookupValueResource) - no hardcodeado aca.
            'customer_type' => [
                'required',
                Rule::in(LookupValue::forCategory('customer_type')->active()->pluck('value')),
            ],
            // 'individual' pide nombre/apellido; cualquier otro tipo (los
            // que existan hoy o los que se agreguen a futuro desde el
            // panel) pide razon social - no se enumera cada slug a mano.
            'business_name' => ['required_unless:customer_type,individual', 'string', 'max:255'],
            'first_name' => ['required_if:customer_type,individual', 'string', 'max:255'],
            'last_name' => ['required_if:customer_type,individual', 'string', 'max:255'],
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
