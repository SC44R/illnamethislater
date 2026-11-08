<?php

namespace App\Support;

/**
 * A small seeded random number generator (the "mulberry32" algorithm).
 * The same seed always gives the same numbers, on every PHP version.
 * We use it so a session's layout, distraction schedule and dice roll can be replayed in tests.
 */
class SeededRandom
{
    private int $state;

    public function __construct(int $seed)
    {
        $this->state = $seed & 0xFFFFFFFF;
    }

    /** 32-bit integer multiply (PHP would overflow into floats otherwise). */
    private static function imul(int $a, int $b): int
    {
        $a &= 0xFFFFFFFF;
        $b &= 0xFFFFFFFF;
        return ((($a & 0xFFFF) * $b) + (((($a >> 16) & 0xFFFF) * ($b & 0xFFFF)) << 16)) & 0xFFFFFFFF;
    }

    /** A float from 0 (included) to 1 (not included). */
    public function nextFloat(): float
    {
        $this->state = ($this->state + 0x6D2B79F5) & 0xFFFFFFFF;
        $t = $this->state;
        $t = self::imul($t ^ ($t >> 15), $t | 1);
        $t = ($t ^ (($t + self::imul($t ^ ($t >> 7), $t | 61)) & 0xFFFFFFFF)) & 0xFFFFFFFF;
        return (($t ^ ($t >> 14)) & 0xFFFFFFFF) / 4294967296;
    }

    /** A whole number from $min to $max, both included. */
    public function int(int $min, int $max): int
    {
        return $min + (int) floor($this->nextFloat() * ($max - $min + 1));
    }

    /** A decimal number from $min to $max. */
    public function float(float $min, float $max): float
    {
        return $min + $this->nextFloat() * ($max - $min);
    }

    /** Picks an index from a list of weights, for example [1, 1.5, 1] */
    public function weightedIndex(array $weights): int
    {
        $total = array_sum($weights);
        $r = $this->nextFloat() * $total;
        foreach (array_values($weights) as $i => $w) {
            $r -= $w;
            if ($r < 0) {
                return $i;
            }
        }
        return count($weights) - 1;
    }
}
