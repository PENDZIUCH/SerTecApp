<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateUserRequest extends FormRequest
{
    public function authorize(): bool
    {
        $target = $this->route('user');
        $actorEsAdminTier = $this->user()->hasAnyRole(['administrador', 'admin', 'super_admin']);
        if ($target && $target->hasRole('super_admin') && !$this->user()->hasRole('super_admin')) {
            return false; // un no-super_admin no edita (ni resetea password de) una cuenta super_admin - no negociable
        }
        if ($target && $target->hasAnyRole(['administrador', 'admin']) && !$actorEsAdminTier) {
            return false; // un supervisor no puede editar cuentas de administrador
        }
        return $this->user()->can('update_user');
    }

    public function rules(): array
    {
        return [
            'name' => ['sometimes', 'string', 'max:255'],
            'email' => ['sometimes', 'email', Rule::unique('users')->ignore($this->route('user'))],
            'password' => ['sometimes', 'string', 'min:8'],
            'phone' => ['nullable', 'string', 'max:20'],
            'job_title' => ['nullable', 'string', 'max:100'],
            'is_active' => ['boolean'],
            'roles' => ['array'],
            'roles.*' => [
                'exists:roles,name',
                function ($attribute, $value, $fail) {
                    if ($value === 'super_admin' && !$this->user()->hasRole('super_admin')) {
                        $fail('Solo un usuario con rol super_admin puede asignar el rol super_admin.');
                        return;
                    }
                    $actorEsAdminTier = $this->user()->hasAnyRole(['administrador', 'admin', 'super_admin']);
                    if (!$actorEsAdminTier && !in_array($value, ['técnico', 'tecnico'], true)) {
                        $fail('Un supervisor solo puede asignar el rol técnico.');
                    }
                },
            ],
        ];
    }
}
