<?php

namespace App\Policies;

use App\Models\Subscription;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class SubscriptionPolicy
{
    use HandlesAuthorization;

    private function isAdminTier(User $user): bool
    {
        return $user->hasAnyRole(['super_admin', 'administrador', 'supervisor']);
    }

    // Facturacion/billing: no forma parte del trabajo de un tecnico, queda fuera de su alcance.
    public function viewAny(User $user): bool { return $this->isAdminTier($user); }
    public function view(User $user, Subscription $subscription): bool { return $this->isAdminTier($user); }
    public function create(User $user): bool { return $this->isAdminTier($user); }
    public function update(User $user, Subscription $subscription): bool { return $this->isAdminTier($user); }
    public function delete(User $user, Subscription $subscription): bool { return $this->isAdminTier($user); }
    public function deleteAny(User $user): bool { return $this->isAdminTier($user); }
    public function forceDelete(User $user, Subscription $subscription): bool { return $this->isAdminTier($user); }
    public function forceDeleteAny(User $user): bool { return $this->isAdminTier($user); }
    public function restore(User $user, Subscription $subscription): bool { return $this->isAdminTier($user); }
    public function restoreAny(User $user): bool { return $this->isAdminTier($user); }
    public function replicate(User $user, Subscription $subscription): bool { return $this->isAdminTier($user); }
    public function reorder(User $user): bool { return $this->isAdminTier($user); }
}
