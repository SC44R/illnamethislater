<?php

namespace App\Services;

use App\Exceptions\GameRuleException;
use App\Models\TaskSession;
use App\Models\TimeBlock;
use App\Models\User;
use App\Support\Balance;
use App\Support\BeatClickRules;
use App\Support\Rewards;
use Illuminate\Support\Facades\DB;

/** Creates sessions, applies round results and finishes sessions. */
class MinigameService
{
    public function __construct(private PlannerService $planner, private RewardService $rewards)
    {
    }

    /** Starts a session for the task in block $blockIndex of today's plan (state "prep"). */
    public function startSession(User $user, int $blockIndex): TaskSession
    {
        if (TaskSession::where('user_id', $user->id)->whereIn('state', ['prep', 'playing'])->exists()) {
            throw new GameRuleException('session_already_active');
        }

        $plan = $this->planner->today($user);
        $block = $plan->blocks->firstWhere('block_index', $blockIndex);
        if (!$block || $block->kind !== 'task' || !$block->task) {
            throw new GameRuleException('block_has_no_task');
        }
        if ($block->used) {
            throw new GameRuleException('block_used');
        }
        if (!in_array($block->task->status, ['open', 'pending', 'overdue'], true)) {
            throw new GameRuleException('task_not_available');
        }

        return TaskSession::create([
            'user_id' => $user->id,
            'task_id' => $block->task->id,
            'time_block_id' => $block->id,
            'minigame' => $block->task->minigame,
            'seed' => random_int(1, 2147483647),
            'game_date' => $this->planner->gameDate($user)->toDateString(),
            'block_index' => $blockIndex,
            'state' => 'prep',
        ]);
    }

    /** One Beat Click result from the browser: PERFECT, GOOD or MISS. */
    public function applyRound(TaskSession $session, string $result): array
    {
        if ($session->state !== 'playing') {
            throw new GameRuleException('session_not_playing');
        }
        $result = strtoupper($result);
        if (!in_array($result, ['PERFECT', 'GOOD', 'MISS'], true)) {
            throw new GameRuleException('bad_result');
        }

        $rules = [
            'perfect' => (int) Balance::get('beat_click.perfect', 10),
            'good' => (int) Balance::get('beat_click.good', 6),
            'combo_bonus' => (int) Balance::get('beat_click.combo_bonus', 2),
            'max_extra_rounds' => (int) Balance::get('beat_click.max_extra_rounds', 3),
            'points_per_round' => (int) Balance::get('beat_click.points_per_round', 10),
        ];
        $new = BeatClickRules::applyRound([
            'points' => $session->points, 'target' => $session->target_points,
            'combo' => $session->combo, 'extra' => $session->extra_rounds, 'bonus' => $session->bonus_ready,
        ], $result, $rules);

        $session->update([
            'points' => $new['points'], 'target_points' => $new['target'], 'combo' => $new['combo'],
            'extra_rounds' => $new['extra'], 'bonus_ready' => $new['bonus'],
        ]);

        return BeatClickRules::isCleared($new) ? $this->finish($session, 'cleared') : $this->summary($session);
    }

    /**
     * Finishes a session as "cleared" or "failed".
     * Sprint 1 pays a flat XP by task size (the full XP system comes in Sprint 2).
     * A failed session pays 25% effort XP, unless the player quit ($effort = false).
     */
    public function finish(TaskSession $session, string $state, bool $effort = true): array
    {
        return DB::transaction(function () use ($session, $state, $effort) {
            $task = $session->task;
            $base = (int) Balance::get("size.{$task->size}.xp");

            if ($state === 'cleared') {
                $stars = Rewards::stars($session->focus_current, $session->focus_max,
                    (float) Balance::get('reward.star3', 0.7), (float) Balance::get('reward.star2', 0.4));
                $xp = $base;
                $coins = Rewards::coins($xp);
                $task->update(['status' => 'done', 'completed_at' => now()]);
            } else {
                $stars = 0;
                $xp = $effort ? (int) round($base * (float) Balance::get('reward.fail_mult', 0.25)) : 0;
                $coins = 0;
                if ($task->status === 'open') {
                    $task->update(['status' => 'pending']);
                }
            }

            $session->update([
                'state' => $state, 'stars' => $stars, 'xp_awarded' => $xp, 'coins_awarded' => $coins,
                'outcome_code' => $state, 'ended_at' => now(),
            ]);
            $session->block?->update(['used' => true]);
            $this->rewards->grant($session->user, $xp, $coins);

            return $this->summary($session->refresh());
        });
    }

    /** Quit a session. A session that was never rolled is simply deleted. */
    public function abandon(TaskSession $session): void
    {
        if ($session->state === 'prep') {
            $session->delete();
        } elseif ($session->state === 'playing') {
            $this->finish($session, 'failed', false);
        }
    }

    public function summary(TaskSession $session): array
    {
        $out = [
            'state' => $session->state,
            'finished' => $session->isFinished(),
            'focus_current' => (int) $session->focus_current,
            'focus_max' => (int) $session->focus_max,
            'points' => (int) $session->points,
            'target' => (int) $session->target_points,
            'combo' => (int) $session->combo,
            'extra_rounds' => (int) $session->extra_rounds,
        ];
        if ($session->isFinished()) {
            $out['results'] = [
                'state' => $session->state,
                'band' => $session->band,
                'outcome' => $session->outcome_code,
                'stars' => (int) $session->stars,
                'xp' => (int) $session->xp_awarded,
                'coins' => (int) $session->coins_awarded,
                'focus_left' => (int) $session->focus_current,
                'focus_max' => (int) $session->focus_max,
            ];
        }
        return $out;
    }
}
