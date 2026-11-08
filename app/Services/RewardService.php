<?php

namespace App\Services;

use App\Models\User;

class RewardService
{
    public function grant(User $user, int $xp, int $coins): void
    {
        if ($xp > 0) {
            $user->increment('xp', $xp);
        }
        if ($coins > 0) {
            $user->increment('coins', $coins);
        }
    }
}
