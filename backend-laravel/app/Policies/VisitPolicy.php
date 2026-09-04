<?php

namespace App\Policies;

use App\Models\User;
use App\Models\Visit;
use Illuminate\Auth\Access\HandlesAuthorization;

class VisitPolicy
{
    use HandlesAuthorization;

    private function isOwnTechnician(User $user, Visit $visit): bool
    {
        return $user->hasAnyRole(['técnico', 'tecnico']) && $visit->assigned_tech_id === $user->id;
    }

    public function viewAny(User $user): bool
    {
        return $user->can('view_any_visit');
    }

    public function view(User $user, Visit $visit): bool
    {
        if (!$user->can('view_visit')) return false;
        if ($user->hasAnyRole(['técnico', 'tecnico'])) return $this->isOwnTechnician($user, $visit);
        return true;
    }

    public function create(User $user): bool
    {
        return $user->can('create_visit');
    }

    public function update(User $user, Visit $visit): bool
    {
        if (!$user->can('update_visit')) return false;
        if ($user->hasAnyRole(['técnico', 'tecnico'])) return $this->isOwnTechnician($user, $visit);
        return true;
    }

    public function delete(User $user, Visit $visit): bool
    {
        return $user->can('delete_visit');
    }

    public function deleteAny(User $user): bool { return $user->can('delete_any_visit'); }
    public function forceDelete(User $user, Visit $visit): bool { return $user->can('force_delete_visit'); }
    public function forceDeleteAny(User $user): bool { return $user->can('force_delete_any_visit'); }
    public function restore(User $user, Visit $visit): bool { return $user->can('restore_visit'); }
    public function restoreAny(User $user): bool { return $user->can('restore_any_visit'); }
    public function replicate(User $user, Visit $visit): bool { return $user->can('replicate_visit'); }
    public function reorder(User $user): bool { return $user->can('reorder_visit'); }
}
