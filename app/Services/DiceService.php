<?php

namespace App\Services;

use App\Models\TaskSession;
use App\Models\User;
use App\Support\Balance;
use App\Support\FateDice;
use App\Support\SeededRandom;

/**
 * The Fate Roll. This is the ONE place that holds the roll formula:
 *
 *   total = d20 + cards (cap +8) + floor(Drive / 3) + gear luck + buffs + pity - floor(backlog / 2) - IOU
 *
 * Cards, gear luck, buffs and the IOU are zero until the sprints that add them.
 */
class DiceService
{
    public function __construct(private StatsService $stats)
    {
    }

    public function thresholds(): array
    {
        return [
            'hard' => (int) Balance::get('dice.hard_min', 2),
            'normal' => (int) Balance::get('dice.normal_min', 8),
            'easy' => (int) Balance::get('dice.easy_min', 15),
            'flow' => (int) Balance::get('dice.flow_min', 20),
        ];
    }

    /** The list of modifiers, each with a code and a value. */
    public function modifiers(User $user, array $stats, int $cardModifier = 0): array
    {
        $pity = $user->slip_streak >= (int) Balance::get('dice.pity_after', 2) ? (int) Balance::get('dice.pity_bonus', 4) : 0;

        return [
            ['code' => 'drive', 'value' => intdiv($stats['drive'], 3)],
            ['code' => 'cards', 'value' => FateDice::capCards($cardModifier, (int) Balance::get('dice.card_cap', 8))],
            ['code' => 'gear', 'value' => 0],
            ['code' => 'buff', 'value' => 0],
            ['code' => 'pity', 'value' => $pity],
            ['code' => 'backlog', 'value' => -intdiv($stats['backlog_penalty'], 2)],
            ['code' => 'iou', 'value' => 0],
        ];
    }

    /** Odds shown before the roll. */
    public function preview(User $user): array
    {
        $mods = $this->modifiers($user, $this->stats->compute($user));
        $total = array_sum(array_column($mods, 'value'));

        return ['modifiers' => $mods, 'total_modifier' => $total, 'odds' => FateDice::odds($total, $this->thresholds())];
    }

    /** Rolls the d20 from the session seed, so the roll can be replayed and the browser cannot choose it. */
    public function roll(TaskSession $session): array
    {
        $user = $session->user;
        $mods = $this->modifiers($user, $this->stats->compute($user));
        $modTotal = array_sum(array_column($mods, 'value'));

        $rng = new SeededRandom(((int) $session->seed) ^ 0x0D20D20);
        $d20 = $rng->int(1, 20);
        $total = $d20 + $modTotal;
        $band = FateDice::bandFor($d20, $total, $this->thresholds());
        $notes = [];

        // Only one Flow per game day: a second Flow becomes Easy.
        if ($band === 'flow' && $user->last_flow_date && $user->game_date && $user->last_flow_date->isSameDay($user->game_date)) {
            $band = 'easy';
            $notes[] = 'flow_capped';
        }

        // Optional rule: the first roll of the day cannot be a natural-1 Slip.
        if ($band === 'slip' && $d20 === 1 && (int) Balance::get('dice.safe_first_roll', 0) === 1) {
            $rolledToday = TaskSession::where('user_id', $user->id)->whereDate('game_date', $session->game_date)->has('roll')->exists();
            if (!$rolledToday) {
                $band = 'hard';
                $notes[] = 'safe_first_roll';
            }
        }

        return ['d20' => $d20, 'modifiers' => $mods, 'modifier_total' => $modTotal, 'total' => $total, 'band' => $band, 'notes' => $notes];
    }
}
