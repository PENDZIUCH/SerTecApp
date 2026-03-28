<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class LoginRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'email' => ['required', 'email'],
            'password' => ['required', 'string', 'min:4'],
        ];
    }

    public function messages(): array
    {
        return [
            'email.required' => 'El email es obligatorio',
            'email.email' => 'Ingrese un email válido',
            'password.required' => 'El PIN es obligatorio',
            'password.min' => 'El PIN debe tener al menos 4 caracteres',
        ];
    }
}
