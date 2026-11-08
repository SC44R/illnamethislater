<?php

namespace App\Policies;

use App\Models\TaskSession;
use App\Models\User;

class TaskSessionPolicy
{
    /** A player can only touch their own sessions. */
    public function play(User $user, TaskSession $session): bool
    {
        return $session->user_id === $user->id;
    }
}
