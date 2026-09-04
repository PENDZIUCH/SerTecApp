<?php

namespace App\Policies;

use App\Models\Budget;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class BudgetPolicy
{
    use HandlesAuthorization;

    public function viewAny(User $user): bool { return $user->can('view_any_budget'); }
    public function view(User $user, Budget $budget): bool { return $user->can('view_budget'); }
    public function create(User $user): bool { return $user->can('create_budget'); }
    public function update(User $user, Budget $budget): bool { return $user->can('update_budget'); }
    public function delete(User $user, Budget $budget): bool { return $user->can('delete_budget'); }
    public function deleteAny(User $user): bool { return $user->can('delete_any_budget'); }
    public function forceDelete(User $user, Budget $budget): bool { return $user->can('force_delete_budget'); }
    public function forceDeleteAny(User $user): bool { return $user->can('force_delete_any_budget'); }
    public function restore(User $user, Budget $budget): bool { return $user->can('restore_budget'); }
    public function restoreAny(User $user): bool { return $user->can('restore_any_budget'); }
    public function replicate(User $user, Budget $budget): bool { return $user->can('replicate_budget'); }
    public function reorder(User $user): bool { return $user->can('reorder_budget'); }
}
