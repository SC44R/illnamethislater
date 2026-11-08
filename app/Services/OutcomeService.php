<?php

namespace App\Services;

use App\Models\FateRoll;
use App\Models\TaskSession;
use App\Support\Balance;
use Illuminate\Support\Facades\DB;

/** Applies the result of a Fate Roll: Flow, Slip, or start the minigame. */
class OutcomeService
{
    public function __construct(
        private MinigameFactory $factory,
        private MinigameService $minigames,
        private RewardService $rewards
    ) {
    }

    public function apply(TaskSession $session, array $roll): array
    {
        return DB::transaction(function () use ($session, $roll) {
            $user = $session->user;
            $task = $session->task;
            $band = $roll['band'];
            $base = (int) Balance::get("size.{$task->size}.xp");

            $fate = FateRoll::create([
                'task_session_id' => $session->id, 'd20' => $roll['d20'], 'modifiers' => $roll['modifiers'],
                'total' => $roll['total'], 'band' => $band,
            ]);

            if ($band === 'flow') {
                // Autocomplete. In Sprint 1 the story is always "Task cancelled". "Friend completed it" arrives with the buddy feature.
                $xp = (int) round($base * (float) Balance::get('reward.flow_mult', 0.5));
                $coins = (int) round($xp / 2);
                $task->update(['status' => 'cancelled']);
                $user->forceFill(['last_flow_date' => $user->game_date, 'slip_streak' => 0])->save();
                $this->finishWithoutMinigame($session, 'flow', 'task_cancelled', $xp, $coins);
                $fate->update(['outcome_code' => 'task_cancelled']);
                return ['type' => 'flow', 'scene' => 'task_cancelled', 'summary' => $this->minigames->summary($session->refresh())];
            }

            if ($band === 'slip') {
                // Auto lose. The task stays pending and the player gets 25% effort XP.
                $xp = (int) round($base * (float) Balance::get('reward.slip_mult', 0.25));
                if ($task->status === 'open') {
                    $task->update(['status' => 'pending']);
                }
                $user->forceFill(['slip_streak' => $user->slip_streak + 1])->save();
                $this->finishWithoutMinigame($session, 'slip', 'slip_generic', $xp, 0);
                $fate->update(['outcome_code' => 'slip_generic']);
                return ['type' => 'slip', 'scene' => 'slip_generic', 'summary' => $this->minigames->summary($session->refresh())];
            }

            // Easy, Normal or Hard: the minigame plays.
            $user->forceFill(['slip_streak' => 0])->save();
            $fate->update(['outcome_code' => 'play']);
            return ['type' => 'play', 'config' => $this->factory->build($session, $band)];
        });
    }

    private function finishWithoutMinigame(TaskSession $session, string $state, string $outcome, int $xp, int $coins): void
    {
        $session->update([
            'band' => $session->band ?: $state, 'state' => $state, 'outcome_code' => $outcome,
            'xp_awarded' => $xp, 'coins_awarded' => $coins, 'stars' => 0, 'ended_at' => now(),
        ]);
        $session->block?->update(['used' => true]);
        $this->rewards->grant($session->user, $xp, $coins);
    }
}
