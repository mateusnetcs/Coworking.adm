<?php

namespace App\Policies;

use App\Models\Reservation;
use App\Models\Role;
use App\Models\User;

class ReservationPolicy
{
    public function create(User $user): bool
    {
        return $user->roles()->whereIn('name', [Role::STUDENT, Role::ADMIN])->exists();
    }

    public function deleteAsOwner(User $user, Reservation $reservation): bool
    {
        return $user->id === $reservation->user_id;
    }

    public function deleteAsAdministrator(User $user, Reservation $reservation): bool
    {
        return $user->isAdministrator();
    }
}
