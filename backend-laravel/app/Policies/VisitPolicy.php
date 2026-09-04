<?php

namespace App\Policies;

use App\Models\User;
use App\Models\Visit;
use Illuminate\Auth\Access\HandlesAuthorization;

class VisitPolicy
{
    use HandlesAuthorization;

    private function isAdminTier(User $user): bool
    {
        return $user->hasAnyRole(['super_admin', 'administrador', 'supervisor']);
    }

    private function isOwnTechnician(User $user, Visit $visit): bool
    {
        return $user->hasAnyRole(['técnico', 'tecnico']) && $visit->assigned_tech_id === $user->id;
    }

    public function viewAny(User $user): bool
    {
        return $this->isAdminTier($user) || $user->hasAnyRole(['técnico', 'tecnico']);
    }

    public function view(User $user, Visit $visit): bool
    {
        return $this->isAdminTier($user) || $this->isOwnTechnician($user, $visit);
    }

    public function create(User $user): bool
    {
        return $this->isAdminTier($user) || $user->hasAnyRole(['técnico', 'tecnico']);
    }

    public function update(User $user, Visit $visit): bool
    {
        return $this->isAdminTier($user) || $this->isOwnTechnician($user, $visit);
    }

    public function delete(User $user, Visit $visit): bool
    {
        return $this->isAdminTier($user);
    }

    public function deleteAny(User $user): bool { return $this->isAdminTier($user); }
    public function forceDelete(User $user, Visit $visit): bool { return $this->isAdminTier($user); }
    public function forceDeleteAny(User $user): bool { return $this->isAdminTier($user); }
    public function restore(User $user, Visit $visit): bool { return $this->isAdminTier($user); }
    public function restoreAny(User $user): bool { return $this->isAdminTier($user); }
    public function replicate(User $user, Visit $visit): bool { return $this->isAdminTier($user); }
    public function reorder(User $user): bool { return $this->isAdminTier($user); }
}
