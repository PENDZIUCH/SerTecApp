<?php

namespace App\Policies;

use App\Models\User;
use App\Models\WorkshopItem;
use Illuminate\Auth\Access\HandlesAuthorization;

class WorkshopItemPolicy
{
    use HandlesAuthorization;

    private function isAdminTier(User $user): bool
    {
        return $user->hasAnyRole(['super_admin', 'administrador', 'supervisor']);
    }

    private function isOwnTechnician(User $user, WorkshopItem $item): bool
    {
        return $user->hasAnyRole(['técnico', 'tecnico']) && $item->assigned_tech_id === $user->id;
    }

    public function viewAny(User $user): bool
    {
        return $this->isAdminTier($user) || $user->hasAnyRole(['técnico', 'tecnico']);
    }

    public function view(User $user, WorkshopItem $item): bool
    {
        return $this->isAdminTier($user) || $this->isOwnTechnician($user, $item);
    }

    public function create(User $user): bool
    {
        return $this->isAdminTier($user) || $user->hasAnyRole(['técnico', 'tecnico']);
    }

    public function update(User $user, WorkshopItem $item): bool
    {
        return $this->isAdminTier($user) || $this->isOwnTechnician($user, $item);
    }

    public function delete(User $user, WorkshopItem $item): bool
    {
        return $this->isAdminTier($user);
    }

    public function deleteAny(User $user): bool { return $this->isAdminTier($user); }
    public function forceDelete(User $user, WorkshopItem $item): bool { return $this->isAdminTier($user); }
    public function forceDeleteAny(User $user): bool { return $this->isAdminTier($user); }
    public function restore(User $user, WorkshopItem $item): bool { return $this->isAdminTier($user); }
    public function restoreAny(User $user): bool { return $this->isAdminTier($user); }
    public function replicate(User $user, WorkshopItem $item): bool { return $this->isAdminTier($user); }
    public function reorder(User $user): bool { return $this->isAdminTier($user); }
}
