<?php

namespace App\Services;

use App\Models\Distraction;
use App\Models\SessionDistractionEvent;
use App\Models\TaskSession;
use App\Support\Balance;
use App\Support\DistractionPlanner;
use App\Support\SeededRandom;

/** Decides how many distractions a session gets, when they appear and which ones. */
class DistractionScheduler
{
    /** Creates the events in the database and returns them as arrays for the browser. */
    public function build(TaskSession $session, array $stats): array
    {
        $task = $session->task;
        $user = $session->user;
        $band = $session->band;

        $count = DistractionPlanner::count(
            (int) Balance::get("size.{$task->size}.distractions"),
            $stats['backlog_penalty'],
            (int) Balance::get("band.$band.distractions", 0)
        );

        $rng = new SeededRandom(((int) $session->seed) ^ 0x0D157AC7);
        $positions = DistractionPlanner::positions(
            $count,
            (float) Balance::get('distraction.start', 0.15),
            (float) Balance::get('distraction.end', 0.90),
            (float) Balance::get('distraction.min_gap', 0.10),
            $rng
        );

        $maxTier = DistractionPlanner::maxTier((int) $user->level, [
            1 => (int) Balance::get('tier.1.min_level', 1),
            2 => (int) Balance::get('tier.2.min_level', 2),
            3 => (int) Balance::get('tier.3.min_level', 4),
        ]);

        // Only distractions that are enabled (built so far) and allowed for this level.
        $pool = Distraction::where('enabled', true)->where('tier', '<=', $maxTier)->orderBy('id')->get();
        if ($pool->isEmpty()) {
            $pool = Distraction::where('enabled', true)->orderBy('tier')->orderBy('id')->get();
        }

        // Weights: a type's weak tags come up more often, screens come up more in the evening blocks.
        $weak = $user->type?->weak_tags ?? [];
        $evening = $session->block_index >= 3;
        $weights = $pool->map(function ($d) use ($weak, $evening) {
            $w = 1.0;
            if (in_array($d->tag, $weak, true)) {
                $w *= 1.5;
            }
            if ($evening && $d->tag === 'SCREEN') {
                $w *= 1.3;
            }
            return $w;
        })->all();

        $events = [];
        foreach ($positions as $position) {
            $d = $pool[$rng->weightedIndex($weights)];
            $event = SessionDistractionEvent::create([
                'task_session_id' => $session->id,
                'distraction_id' => $d->id,
                'at_progress' => $position,
                'prompt' => $d->prompt,
                'prompt_count' => $d->prompt_count,
                'window_ms' => DistractionPlanner::window(
                    (int) Balance::get("tier.{$d->tier}.window"),
                    $stats['calm'],
                    (int) Balance::get('distraction.calm_ms', 200),
                    (int) Balance::get('distraction.calm_cap_ms', 2000)
                ),
                'damage' => DistractionPlanner::damage(
                    (int) Balance::get("tier.{$d->tier}.damage"),
                    $stats['backlog_penalty'],
                    $stats['willpower']
                ),
            ]);
            $events[] = [
                'id' => $event->id,
                'at_progress' => (float) $event->at_progress,
                'tag' => $d->tag,
                'tier' => $d->tier,
                'name' => $d->name,
                'icon' => $d->icon,
                'prompt' => $event->prompt,
                'prompt_count' => $event->prompt_count,
                'window_ms' => $event->window_ms,
                'damage' => $event->damage,
                'effect' => $d->lingering_effect_code,
            ];
        }
        return $events;
    }
}
