<?php

namespace App\Services;

use App\Exceptions\GameRuleException;
use App\Models\DayPlan;
use App\Models\Task;
use App\Models\TimeBlock;
use App\Models\User;
use App\Support\Balance;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

/** Tasks and the 4 time blocks of a day. */
class PlannerService
{
    public function gameDate(User $user): Carbon
    {
        return $user->game_date ? $user->game_date->copy()->startOfDay() : now()->startOfDay();
    }

    /** Today's plan (in game days). It is created with 4 blocks the first time it is needed. */
    public function today(User $user): DayPlan
    {
        $plan = DayPlan::firstOrCreate(['user_id' => $user->id, 'game_date' => $this->gameDate($user)->toDateString()]);
        if ($plan->blocks()->count() === 0) {
            foreach ([1 => 'task', 2 => 'free', 3 => 'task', 4 => 'free'] as $index => $kind) {
                TimeBlock::create(['day_plan_id' => $plan->id, 'block_index' => $index, 'kind' => $kind]);
            }
        }
        return $plan->load('blocks.task');
    }

    /** Beat Click is the only minigame in Sprint 1, so every task type falls back to it. */
    public function suggestMinigame(string $taskType): string
    {
        $wanted = ['practice' => 'beat_click', 'deep' => 'focus_tuner', 'memorize' => 'memory_match'][$taskType] ?? 'beat_click';
        $available = config('game.available_minigames', ['beat_click']);
        return in_array($wanted, $available, true) ? $wanted : $available[0];
    }

    public function createTask(User $user, array $data): Task
    {
        $overdue = Carbon::parse($data['due_date'])->startOfDay()->lt($this->gameDate($user));

        return Task::create([
            'user_id' => $user->id,
            'title' => $data['title'],
            'type' => $data['type'],
            'size' => $data['size'],
            'difficulty' => $data['difficulty'],
            'due_date' => $data['due_date'],
            'cause_id' => $data['cause_id'],
            'status' => $overdue ? 'overdue' : 'open',
            'minigame' => $this->suggestMinigame($data['type']),
        ]);
    }

    /** Sets a block to task or free, and puts a task in it. A task can sit in one block per day only. */
    public function setBlock(User $user, int $blockIndex, string $kind, ?int $taskId): TimeBlock
    {
        $plan = $this->today($user);
        $block = $plan->blocks->firstWhere('block_index', $blockIndex);
        if (!$block) {
            throw new GameRuleException('block_not_found');
        }
        if ($block->used) {
            throw new GameRuleException('block_used');
        }

        if ($kind === 'free' || $taskId === null) {
            $block->update(['kind' => $kind, 'task_id' => null]);
            return $block->refresh();
        }

        $task = Task::where('user_id', $user->id)->whereIn('status', ['open', 'pending', 'overdue'])->find($taskId);
        if (!$task) {
            throw new GameRuleException('task_not_available');
        }
        $already = TimeBlock::where('day_plan_id', $plan->id)->where('task_id', $task->id)->where('id', '!=', $block->id)->exists();
        if ($already) {
            throw new GameRuleException('task_already_in_a_block');
        }

        $block->update(['kind' => 'task', 'task_id' => $task->id]);
        return $block->refresh();
    }

    /**
     * End the day:
     *  - scheduled tasks that were not finished become pending
     *  - the game calendar moves one day forward
     *  - tasks past their due date become overdue
     */
    public function endDay(User $user): array
    {
        return DB::transaction(function () use ($user) {
            $plan = $this->today($user);
            $newPending = 0;
            foreach ($plan->blocks as $block) {
                if ($block->kind === 'task' && $block->task && $block->task->status === 'open') {
                    $block->task->update(['status' => 'pending']);
                    $newPending++;
                }
            }
            $plan->update(['ended' => true]);

            $newDate = $this->gameDate($user)->copy()->addDay();
            $user->forceFill(['game_date' => $newDate->toDateString()])->save();

            $newOverdue = Task::where('user_id', $user->id)
                ->whereIn('status', ['open', 'pending'])
                ->whereDate('due_date', '<', $newDate->toDateString())
                ->update(['status' => 'overdue']);

            $this->today($user->refresh());   // create the next day's plan

            return ['new_pending' => $newPending, 'new_overdue' => $newOverdue, 'game_date' => $newDate->toDateString()];
        });
    }
}
