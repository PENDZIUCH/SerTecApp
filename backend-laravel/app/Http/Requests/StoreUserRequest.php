<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreUserRequest extends FormRequest
{
    public function authorize(): bool
    {
        // super_admin pasa siempre via Gate::before; administrador/supervisor
        // tienen create_user via SyncShieldPermissionsSeeder.
        return $this->user()->can('create_user');
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'unique:users,email'],
            'password' => ['required', 'string', 'min:8'],
            'phone' => ['nullable', 'string', 'max:20'],
            'job_title' => ['nullable', 'string', 'max:100'],
            'is_active' => ['boolean'],
            'roles' => ['array'],
            'roles.*' => [
                'exists:roles,name',
                // Mismo hueco que se cerro en Filament UserResource: nadie que no sea
                // super_admin puede otorgar el rol super_admin, ni siquiera via API directa.
                function ($attribute, $value, $fail) {
                    if ($value === 'super_admin' && !$this->user()->hasRole('super_admin')) {
                        $fail('Solo un usuario con rol super_admin puede asignar el rol super_admin.');
                    }
                },
            ],
        ];
    }
}
