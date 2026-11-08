<?php

namespace App\Services;

use App\Models\User;
use App\Support\Balance;

/**
 * Works out the four stats of a player.
 * Gear and den bonuses are added here in later sprints (the hooks are the zeros below).
 */
class StatsService
{
    public function __construct(private BacklogService $backlog)
    {
    }

    public function compute(User $user): array
    {
        $type = $user->type;
        $base = [
            'focus' => (int) ($type?->base_focus ?? 0),
            'willpower' => (int) ($type?->base_willpower ?? 0),
            'calm' => (int) ($type?->base_calm ?? 0),
            'drive' => (int) ($type?->base_drive ?? 0),
        ];
        $penalty = $this->backlog->penalty($user, $base['calm']);

        return self::calculate($base, (int) $user->level, $penalty, [
            'per_level' => (int) Balance::get('focus.per_level', 5),
            'backlog_cost' => (int) Balance::get('focus.backlog_cost', 2),
            'min' => (int) Balance::get('focus.min', 30),
        ]);
    }

    /** Pure maths, so it can be tested without a database. */
    public static function calculate(array $base, int $level, int $backlogPenalty, array $c): array
    {
        $gearFocus = 0;   // Sprint 3
        $denFocus = 0;    // Sprint 4
        $focus = $base['focus'] + $c['per_level'] * ($level - 1) + $gearFocus + $denFocus - $c['backlog_cost'] * $backlogPenalty;

        return [
            'focus_max' => max($c['min'], $focus),
            'willpower' => max(0, $base['willpower']),
            'calm' => max(0, $base['calm']),
            'drive' => max(0, $base['drive']),
            'backlog_penalty' => $backlogPenalty,
        ];
    }
}
