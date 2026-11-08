<?php

namespace App\Support;

/** Pure distraction maths (no database). */
class DistractionPlanner
{
    /** How many distractions a task gets (1 to 10). */
    public static function count(int $baseBySize, int $backlogPenalty, int $bandAdjust): int
    {
        return max(1, min(10, $baseBySize + intdiv($backlogPenalty, 2) + $bandAdjust));
    }

    /**
     * Spread $count positions between $start and $end (as fractions of progress).
     * Each position lives in its own slot and is nudged randomly inside it,
     * so two positions are never closer than $minGap (when there is room for that).
     */
    public static function positions(int $count, float $start, float $end, float $minGap, SeededRandom $rng): array
    {
        $step = ($end - $start) / $count;
        $gap = min($minGap + 0.001, $step);
        $jitter = max(0.0, $step - $gap);
        $out = [];
        for ($i = 0; $i < $count; $i++) {
            $out[] = round($start + $i * $step + $rng->nextFloat() * $jitter, 3);
        }
        return $out;
    }

    /** Time window in milliseconds: base window + 200 ms per Calm point (extra capped at 2000 ms). */
    public static function window(int $baseMs, int $calm, int $perCalmMs = 200, int $capMs = 2000): int
    {
        return $baseMs + min($capMs, $perCalmMs * $calm);
    }

    /** Focus damage of a failed dismissal (never below 1). */
    public static function damage(int $tierDamage, int $backlogPenalty, int $willpower): int
    {
        return max(1, $tierDamage + intdiv($backlogPenalty, 2) - intdiv($willpower, 3));
    }

    /** Highest distraction tier the player's level allows. $minLevels = [1 => 1, 2 => 2, 3 => 4]. */
    public static function maxTier(int $level, array $minLevels): int
    {
        $max = 1;
        foreach ($minLevels as $tier => $minLevel) {
            if ($level >= $minLevel) {
                $max = max($max, (int) $tier);
            }
        }
        return $max;
    }
}
