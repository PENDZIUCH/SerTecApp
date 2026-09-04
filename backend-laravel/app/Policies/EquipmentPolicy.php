<?php

namespace App\Policies;

use App\Models\Equipment;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class EquipmentPolicy
{
    use HandlesAuthorization;

    private function isAdminTier(User $user): bool
    {
        return $user->hasAnyRole(['super_admin', 'administrador', 'supervisor']);
    }

    // Un tecnico necesita ver el equipo del cliente para diagnosticar, pero no administrarlo.
    public function viewAny(User $user): bool
    {
        return $this->isAdminTier($user) || $user->hasAnyRole(['técnico', 'tecnico']);
    }

    public function view(User $user, Equipment $equipment): bool
    {
        return $this->isAdminTier($user) || $user->hasAnyRole(['técnico', 'tecnico']);
    }

    public function create(User $user): bool { return $this->isAdminTier($user); }
    public function update(User $user, Equipment $equipment): bool { return $this->isAdminTier($user); }
    public function delete(User $user, Equipment $equipment): bool { return $this->isAdminTier($user); }
    public function deleteAny(User $user): bool { return $this->isAdminTier($user); }
    public function forceDelete(User $user, Equipment $equipment): bool { return $this->isAdminTier($user); }
    public function forceDeleteAny(User $user): bool { return $this->isAdminTier($user); }
    public function restore(User $user, Equipment $equipment): bool { return $this->isAdminTier($user); }
    public function restoreAny(User $user): bool { return $this->isAdminTier($user); }
    public function replicate(User $user, Equipment $equipment): bool { return $this->isAdminTier($user); }
    public function reorder(User $user): bool { return $this->isAdminTier($user); }
}
