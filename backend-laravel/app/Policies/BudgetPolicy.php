<?php

namespace App\Policies;

use App\Models\Budget;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class BudgetPolicy
{
    use HandlesAuthorization;

    private function isAdminTier(User $user): bool
    {
        return $user->hasAnyRole(['super_admin', 'administrador', 'supervisor']);
    }

    // Un tecnico puede ver presupuestos (los necesita para su trabajo) pero no crearlos/editarlos/aprobarlos.
    public function viewAny(User $user): bool
    {
        return $this->isAdminTier($user) || $user->hasAnyRole(['técnico', 'tecnico']);
    }

    public function view(User $user, Budget $budget): bool
    {
        return $this->isAdminTier($user) || $user->hasAnyRole(['técnico', 'tecnico']);
    }

    public function create(User $user): bool { return $this->isAdminTier($user); }
    public function update(User $user, Budget $budget): bool { return $this->isAdminTier($user); }
    public function delete(User $user, Budget $budget): bool { return $this->isAdminTier($user); }
    public function deleteAny(User $user): bool { return $this->isAdminTier($user); }
    public function forceDelete(User $user, Budget $budget): bool { return $this->isAdminTier($user); }
    public function forceDeleteAny(User $user): bool { return $this->isAdminTier($user); }
    public function restore(User $user, Budget $budget): bool { return $this->isAdminTier($user); }
    public function restoreAny(User $user): bool { return $this->isAdminTier($user); }
    public function replicate(User $user, Budget $budget): bool { return $this->isAdminTier($user); }
    public function reorder(User $user): bool { return $this->isAdminTier($user); }
}
