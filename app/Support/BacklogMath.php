<?php

namespace App\Support;

class BacklogMath
{
    /**
     * pending weight = pending tasks + 2 x overdue tasks (overdue counts double)
     * penalty = max(0, min(cap, weight) - floor(Calm / 2))
     */
    public static function penalty(int $pending, int $overdue, int $calm, int $cap = 10): int
    {
        $weight = $pending + 2 * $overdue;
        return max(0, min($cap, $weight) - intdiv($calm, 2));
    }
}
