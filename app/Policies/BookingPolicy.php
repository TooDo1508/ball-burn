<?php

namespace App\Policies;

use App\Models\Booking;
use App\Models\User;

class BookingPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->canAccessAdmin();
    }

    public function view(User $user, Booking $booking): bool
    {
        return $user->canAccessAdmin();
    }

    public function updateStatus(User $user, Booking $booking): bool
    {
        return $user->canAccessAdmin();
    }

    public function delete(User $user, Booking $booking): bool
    {
        return $user->canManageBookingDestructively();
    }

    public function create(User $user): bool
    {
        return $user->canAccessAdmin();
    }
}
