<?php

namespace App\Policies;

use App\Models\Equipment;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class EquipmentPolicy
{
    use HandlesAuthorization;

    public function viewAny(User $user): bool { return $user->can('view_any_equipment'); }
    public function view(User $user, Equipment $equipment): bool { return $user->can('view_equipment'); }
    public function create(User $user): bool { return $user->can('create_equipment'); }
    public function update(User $user, Equipment $equipment): bool { return $user->can('update_equipment'); }
    public function delete(User $user, Equipment $equipment): bool { return $user->can('delete_equipment'); }
    public function deleteAny(User $user): bool { return $user->can('delete_any_equipment'); }
    public function forceDelete(User $user, Equipment $equipment): bool { return $user->can('force_delete_equipment'); }
    public function forceDeleteAny(User $user): bool { return $user->can('force_delete_any_equipment'); }
    public function restore(User $user, Equipment $equipment): bool { return $user->can('restore_equipment'); }
    public function restoreAny(User $user): bool { return $user->can('restore_any_equipment'); }
    public function replicate(User $user, Equipment $equipment): bool { return $user->can('replicate_equipment'); }
    public function reorder(User $user): bool { return $user->can('reorder_equipment'); }
}
