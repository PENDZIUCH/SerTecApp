<?php

namespace App\Policies;

use App\Models\Booking;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class BookingPolicy
{
    use HandlesAuthorization;

    /**
     * Un tecnico solo puede tocar bookings donde el resource reservado es
     * el mismo (su propia agenda) — mismo criterio de "ownership" que
     * VisitPolicy::isOwnTechnician, adaptado al resource polimorfico.
     */
    private function isOwnResource(User $user, Booking $booking): bool
    {
        return $user->hasAnyRole(['técnico', 'tecnico'])
            && $booking->resource_type === User::class
            && (int) $booking->resource_id === (int) $user->id;
    }

    public function viewAny(User $user): bool
    {
        return $user->can('view_any_booking');
    }

    public function view(User $user, Booking $booking): bool
    {
        if (!$user->can('view_booking')) return false;
        if ($user->hasAnyRole(['técnico', 'tecnico'])) return $this->isOwnResource($user, $booking);
        return true;
    }

    public function create(User $user): bool
    {
        return $user->can('create_booking');
    }

    public function update(User $user, Booking $booking): bool
    {
        if (!$user->can('update_booking')) return false;
        if ($user->hasAnyRole(['técnico', 'tecnico'])) return $this->isOwnResource($user, $booking);
        return true;
    }

    public function delete(User $user, Booking $booking): bool
    {
        return $user->can('delete_booking');
    }

    public function deleteAny(User $user): bool { return $user->can('delete_any_booking'); }
    public function forceDelete(User $user, Booking $booking): bool { return $user->can('force_delete_booking'); }
    public function forceDeleteAny(User $user): bool { return $user->can('force_delete_any_booking'); }
    public function restore(User $user, Booking $booking): bool { return $user->can('restore_booking'); }
    public function restoreAny(User $user): bool { return $user->can('restore_any_booking'); }
    public function replicate(User $user, Booking $booking): bool { return $user->can('replicate_booking'); }
    public function reorder(User $user): bool { return $user->can('reorder_booking'); }
}
