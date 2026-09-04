<?php

namespace App\Policies;

use App\Models\User;
use App\Models\WorkshopItem;
use Illuminate\Auth\Access\HandlesAuthorization;

class WorkshopItemPolicy
{
    use HandlesAuthorization;

    private function isOwnTechnician(User $user, WorkshopItem $item): bool
    {
        return $user->hasAnyRole(['técnico', 'tecnico']) && $item->assigned_tech_id === $user->id;
    }

    public function viewAny(User $user): bool
    {
        return $user->can('view_any_workshop::item');
    }

    public function view(User $user, WorkshopItem $item): bool
    {
        if (!$user->can('view_workshop::item')) return false;
        if ($user->hasAnyRole(['técnico', 'tecnico'])) return $this->isOwnTechnician($user, $item);
        return true;
    }

    public function create(User $user): bool
    {
        return $user->can('create_workshop::item');
    }

    public function update(User $user, WorkshopItem $item): bool
    {
        if (!$user->can('update_workshop::item')) return false;
        if ($user->hasAnyRole(['técnico', 'tecnico'])) return $this->isOwnTechnician($user, $item);
        return true;
    }

    public function delete(User $user, WorkshopItem $item): bool
    {
        return $user->can('delete_workshop::item');
    }

    public function deleteAny(User $user): bool { return $user->can('delete_any_workshop::item'); }
    public function forceDelete(User $user, WorkshopItem $item): bool { return $user->can('force_delete_workshop::item'); }
    public function forceDeleteAny(User $user): bool { return $user->can('force_delete_any_workshop::item'); }
    public function restore(User $user, WorkshopItem $item): bool { return $user->can('restore_workshop::item'); }
    public function restoreAny(User $user): bool { return $user->can('restore_any_workshop::item'); }
    public function replicate(User $user, WorkshopItem $item): bool { return $user->can('replicate_workshop::item'); }
    public function reorder(User $user): bool { return $user->can('reorder_workshop::item'); }
}
