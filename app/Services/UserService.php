<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Support\Facades\Hash;

class UserService
{
    public function create(array $data)
    {
        $data['password'] = Hash::make($data['password']);
        
        $user = User::create($data);

        if (isset($data['roles'])) {
            $user->syncRoles($data['roles']);
        }

        return $user;
    }

    public function update(User $user, array $data)
    {
        if (isset($data['password'])) {
            $data['password'] = Hash::make($data['password']);
        }

        $user->update($data);

        if (isset($data['roles'])) {
            $user->syncRoles($data['roles']);
        }

        return $user->fresh();
    }

    public function delete(User $user)
    {
        return $user->delete();
    }

    public function assignRole(User $user, string $role)
    {
        $user->assignRole($role);
        return $user->fresh('roles');
    }

    public function syncPermissions(User $user, array $permissions)
    {
        $user->syncPermissions($permissions);
        return $user->fresh('permissions');
    }
}
