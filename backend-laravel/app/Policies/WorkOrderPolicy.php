<?php

namespace App\Policies;

use App\Models\User;
use App\Models\WorkOrder;
use Illuminate\Auth\Access\HandlesAuthorization;

class WorkOrderPolicy
{
    use HandlesAuthorization;

    // super_admin pasa siempre via Gate::before en AppServiceProvider.
    // administrador/supervisor tienen estos permisos via SyncShieldPermissionsSeeder.
    // tecnico los tiene salvo delete, y ademas queda restringido a lo suyo.

    private function isOwnTechnician(User $user, WorkOrder $workOrder): bool
    {
        return $user->hasAnyRole(['técnico', 'tecnico']) && $workOrder->assigned_tech_id === $user->id;
    }

    public function viewAny(User $user): bool
    {
        return $user->can('view_any_work::order');
    }

    public function view(User $user, WorkOrder $workOrder): bool
    {
        if (!$user->can('view_work::order')) return false;
        if ($user->hasAnyRole(['técnico', 'tecnico'])) return $this->isOwnTechnician($user, $workOrder);
        return true;
    }

    public function create(User $user): bool
    {
        return $user->can('create_work::order');
    }

    public function update(User $user, WorkOrder $workOrder): bool
    {
        if (!$user->can('update_work::order')) return false;
        if ($user->hasAnyRole(['técnico', 'tecnico'])) return $this->isOwnTechnician($user, $workOrder);
        return true;
    }

    public function delete(User $user, WorkOrder $workOrder): bool
    {
        return $user->can('delete_work::order');
    }

    public function deleteAny(User $user): bool { return $user->can('delete_any_work::order'); }
    public function forceDelete(User $user, WorkOrder $workOrder): bool { return $user->can('force_delete_work::order'); }
    public function forceDeleteAny(User $user): bool { return $user->can('force_delete_any_work::order'); }
    public function restore(User $user, WorkOrder $workOrder): bool { return $user->can('restore_work::order'); }
    public function restoreAny(User $user): bool { return $user->can('restore_any_work::order'); }
    public function replicate(User $user, WorkOrder $workOrder): bool { return $user->can('replicate_work::order'); }
    public function reorder(User $user): bool { return $user->can('reorder_work::order'); }
}
