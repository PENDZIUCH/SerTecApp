<?php

namespace App\Policies;

use App\Models\User;
use App\Models\WorkOrder;
use Illuminate\Auth\Access\HandlesAuthorization;

class WorkOrderPolicy
{
    use HandlesAuthorization;

    private function isAdminTier(User $user): bool
    {
        return $user->hasAnyRole(['super_admin', 'administrador', 'supervisor']);
    }

    private function isOwnTechnician(User $user, WorkOrder $workOrder): bool
    {
        return $user->hasAnyRole(['técnico', 'tecnico']) && $workOrder->assigned_tech_id === $user->id;
    }

    public function viewAny(User $user): bool
    {
        return $this->isAdminTier($user) || $user->hasAnyRole(['técnico', 'tecnico']);
    }

    public function view(User $user, WorkOrder $workOrder): bool
    {
        return $this->isAdminTier($user) || $this->isOwnTechnician($user, $workOrder);
    }

    public function create(User $user): bool
    {
        return $this->isAdminTier($user) || $user->hasAnyRole(['técnico', 'tecnico']);
    }

    public function update(User $user, WorkOrder $workOrder): bool
    {
        return $this->isAdminTier($user) || $this->isOwnTechnician($user, $workOrder);
    }

    public function delete(User $user, WorkOrder $workOrder): bool
    {
        // Un tecnico nunca borra ordenes via API, solo staff admin-tier.
        return $this->isAdminTier($user);
    }

    public function deleteAny(User $user): bool { return $this->isAdminTier($user); }
    public function forceDelete(User $user, WorkOrder $workOrder): bool { return $this->isAdminTier($user); }
    public function forceDeleteAny(User $user): bool { return $this->isAdminTier($user); }
    public function restore(User $user, WorkOrder $workOrder): bool { return $this->isAdminTier($user); }
    public function restoreAny(User $user): bool { return $this->isAdminTier($user); }
    public function replicate(User $user, WorkOrder $workOrder): bool { return $this->isAdminTier($user); }
    public function reorder(User $user): bool { return $this->isAdminTier($user); }
}
