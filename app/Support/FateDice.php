<?php

namespace App\Support;

/** Pure Fate Roll maths (no database), so it is easy to test. */
class FateDice
{
    /**
     * Turns a d20 value and a total into a band.
     * $t holds the minimum totals: ['hard' => 2, 'normal' => 8, 'easy' => 15, 'flow' => 20].
     * A natural 1 is always Slip and a natural 20 is always Flow.
     */
    public static function bandFor(int $d20, int $total, array $t): string
    {
        if ($d20 === 1) {
            return 'slip';
        }
        if ($d20 === 20) {
            return 'flow';
        }
        if ($total >= $t['flow']) {
            return 'flow';
        }
        if ($total >= $t['easy']) {
            return 'easy';
        }
        if ($total >= $t['normal']) {
            return 'normal';
        }
        if ($total >= $t['hard']) {
            return 'hard';
        }
        return 'slip';
    }

    /** Chance of each band (in percent) for a total modifier. Every d20 face has a 5% chance. */
    public static function odds(int $modifier, array $t): array
    {
        $odds = ['slip' => 0, 'hard' => 0, 'normal' => 0, 'easy' => 0, 'flow' => 0];
        for ($d20 = 1; $d20 <= 20; $d20++) {
            $odds[self::bandFor($d20, $d20 + $modifier, $t)] += 5;
        }
        return $odds;
    }

    /** Cards can add at most $cap in total (they can be negative). */
    public static function capCards(int $rawCardTotal, int $cap): int
    {
        return min($cap, $rawCardTotal);
    }
}
