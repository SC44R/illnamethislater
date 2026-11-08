<?php

namespace App\Services;

use App\Models\Cause;
use App\Models\ProcrastinatorType;
use App\Models\Task;
use App\Models\TaskSession;
use App\Models\User;

/** Builds the JSON the browser needs to draw the room. */
class GameStateService
{
    public function __construct(private StatsService $stats, private PlannerService $planner)
    {
    }

    public function forGuest(): array
    {
        return ['auth' => false];
    }

    public function forUser(User $user): array
    {
        $plan = $this->planner->today($user);
        $active = TaskSession::where('user_id', $user->id)->whereIn('state', ['prep', 'playing'])->latest('id')->first();

        return [
            'auth' => true,
            'user' => [
                'name' => $user->name,
                'level' => (int) $user->level,
                'xp' => (int) $user->xp,
                'coins' => (int) $user->coins,
                'type_id' => $user->procrastinator_type_id,
                'game_date' => $this->planner->gameDate($user)->toDateString(),
            ],
            'stats' => $user->procrastinator_type_id ? $this->stats->compute($user) : null,
            'types' => ProcrastinatorType::orderBy('id')->get(['id', 'code', 'name', 'icon', 'base_focus', 'base_willpower', 'base_calm', 'base_drive', 'description']),
            'causes' => Cause::orderBy('id')->get(['id', 'code', 'name', 'icon', 'description']),
            'tasks' => Task::where('user_id', $user->id)->orderByDesc('id')->limit(60)->get()->map(fn ($t) => [
                'id' => $t->id, 'title' => $t->title, 'type' => $t->type, 'size' => $t->size,
                'difficulty' => (int) $t->difficulty, 'due_date' => $t->due_date->toDateString(),
                'cause_id' => $t->cause_id, 'status' => $t->status, 'minigame' => $t->minigame,
            ])->values(),
            'plan' => [
                'date' => $plan->game_date->toDateString(),
                'blocks' => $plan->blocks->map(fn ($b) => [
                    'index' => $b->block_index, 'kind' => $b->kind, 'task_id' => $b->task_id, 'used' => (bool) $b->used,
                ])->values(),
            ],
            'active_session_id' => $active?->id,
        ];
    }
}
