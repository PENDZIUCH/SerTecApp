<?php

namespace App\Policies;

use App\Models\Part;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class PartPolicy
{
    use HandlesAuthorization;

    public function viewAny(User $user): bool { return $user->can('view_any_part'); }
    public function view(User $user, Part $part): bool { return $user->can('view_part'); }
    public function create(User $user): bool { return $user->can('create_part'); }
    public function update(User $user, Part $part): bool { return $user->can('update_part'); }
    public function delete(User $user, Part $part): bool { return $user->can('delete_part'); }
    public function deleteAny(User $user): bool { return $user->can('delete_any_part'); }
    public function forceDelete(User $user, Part $part): bool { return $user->can('force_delete_part'); }
    public function forceDeleteAny(User $user): bool { return $user->can('force_delete_any_part'); }
    public function restore(User $user, Part $part): bool { return $user->can('restore_part'); }
    public function restoreAny(User $user): bool { return $user->can('restore_any_part'); }
    public function replicate(User $user, Part $part): bool { return $user->can('replicate_part'); }
    public function reorder(User $user): bool { return $user->can('reorder_part'); }
}
