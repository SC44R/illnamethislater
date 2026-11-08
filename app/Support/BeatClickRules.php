<?php

namespace App\Support;

/**
 * Scoring rules for Beat Click (pure maths, so the server can check what the browser reports).
 *  PERFECT = +10 points, GOOD = +6, MISS = 0 and the combo resets.
 *  Every 3 PERFECT in a row: the next hit gets +2 bonus points.
 *  A MISS adds one extra round (+10 target points), up to 3 extra rounds in total.
 */
class BeatClickRules
{
    public static function applyRound(array $s, string $result, array $c): array
    {
        // $s = points, target, combo, extra, bonus (bool)
        $result = strtoupper($result);

        if ($result === 'MISS') {
            $s['combo'] = 0;
            $s['bonus'] = false;
            if ($s['extra'] < $c['max_extra_rounds']) {
                $s['extra']++;
                $s['target'] += $c['points_per_round'];
            }
            return $s;
        }

        $gain = $result === 'PERFECT' ? $c['perfect'] : $c['good'];
        if ($s['bonus']) {
            $gain += $c['combo_bonus'];
            $s['bonus'] = false;
        }
        if ($result === 'PERFECT') {
            $s['combo']++;
            if ($s['combo'] % 3 === 0) {
                $s['bonus'] = true;
            }
        }
        $s['points'] += $gain;
        return $s;
    }

    public static function isCleared(array $s): bool
    {
        return $s['points'] >= $s['target'];
    }
}
