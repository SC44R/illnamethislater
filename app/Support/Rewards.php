<?php

namespace App\Support;

class Rewards
{
    /** Stars from the Focus left: 70% or more = 3, 40% or more = 2, above 0 = 1. */
    public static function stars(int $focus, int $focusMax, float $star3 = 0.7, float $star2 = 0.4): int
    {
        if ($focus <= 0 || $focusMax <= 0) {
            return 0;
        }
        $ratio = $focus / $focusMax;
        return $ratio >= $star3 ? 3 : ($ratio >= $star2 ? 2 : 1);
    }

    /** Coins are half of the XP. */
    public static function coins(int $xp): int
    {
        return (int) round($xp / 2);
    }
}
