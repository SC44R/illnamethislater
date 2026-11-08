<?php

namespace App\Services;

use App\Models\Task;
use App\Models\User;
use App\Support\Balance;
use App\Support\BacklogMath;

/** Counts pending and overdue tasks and turns them into the backlog penalty (see BacklogMath). */
class BacklogService
{
    public function counts(User $user): array
    {
        $rows = Task::where('user_id', $user->id)
            ->whereIn('status', ['pending', 'overdue'])
            ->selectRaw('status, count(*) as total')
            ->groupBy('status')
            ->pluck('total', 'status');

        return ['pending' => (int) ($rows['pending'] ?? 0), 'overdue' => (int) ($rows['overdue'] ?? 0)];
    }

    public function penalty(User $user, ?int $calm = null): int
    {
        $calm ??= (int) ($user->type?->base_calm ?? 0);
        $c = $this->counts($user);
        return BacklogMath::penalty($c['pending'], $c['overdue'], $calm, (int) Balance::get('backlog.cap', 10));
    }
}
