<?php

namespace App\Services;

use App\Models\MinigameConfig;
use App\Models\TaskSession;
use App\Support\Balance;
use App\Support\SeededRandom;

/** Builds the minigame configuration of a session from its seed, the task and the roll band. */
class MinigameFactory
{
    public function __construct(private StatsService $stats, private DistractionScheduler $scheduler)
    {
    }

    /** Fills the session (state becomes "playing") and returns the config the browser needs. */
    public function build(TaskSession $session, string $band): array
    {
        $task = $session->task;
        $stats = $this->stats->compute($session->user);

        // Easy = one step easier, Hard = one step harder (never below 1 or above 3).
        $delta = (int) Balance::get("band.$band.difficulty", 0);
        $difficulty = max(1, min(3, (int) $task->difficulty + $delta));

        $params = MinigameConfig::where('minigame', $session->minigame)->where('difficulty', $difficulty)->firstOrFail()->params;

        $rounds = (int) Balance::get("size.{$task->size}.rounds");
        $pointsPerRound = (int) Balance::get('beat_click.points_per_round', 10);
        $extra = (int) Balance::get('beat_click.max_extra_rounds', 3);

        $focusMax = max(1, $stats['focus_max'] + (int) Balance::get("band.$band.focus", 0));
        $startCost = max(2, (int) Balance::get("size.{$task->size}.start_cost") - $stats['drive']);
        $focusNow = max(1, $focusMax - $startCost);

        $session->update([
            'band' => $band,
            'difficulty' => $difficulty,
            'state' => 'playing',
            'focus_max' => $focusMax,
            'focus_current' => $focusNow,
            'target_points' => $rounds * $pointsPerRound,
            'started_at' => now(),
        ]);

        $config = [
            'sessionId' => $session->id,
            'minigame' => $session->minigame,
            'seed' => (int) $session->seed,
            'band' => $band,
            'difficulty' => $difficulty,
            'rounds' => $rounds,
            'target_points' => $rounds * $pointsPerRound,
            'params' => $params,
            'layout' => $this->beatClickLayout($session->seed, $rounds + $extra, $params),
            'focus_max' => $focusMax,
            'focus_current' => $focusNow,
            'start_cost' => $startCost,
        ];
        $config['distractions'] = $this->scheduler->build($session->refresh(), $stats);

        $session->update(['config' => $config]);
        return $config;
    }

    /** One entry per round: where the green zone sits (and a fake red zone on difficulty 3). */
    private function beatClickLayout(int $seed, int $roundCount, array $params): array
    {
        $rng = new SeededRandom(((int) $seed) ^ 0x0BEA7C11);
        $layout = [];
        for ($i = 0; $i < $roundCount; $i++) {
            $center = $params['shift'] ? round($rng->float(0.2, 0.8), 3) : 0.5;
            $fake = null;
            if (($params['fakeZones'] ?? 0) > 0) {
                for ($try = 0; $try < 10; $try++) {
                    $candidate = round($rng->float(0.1, 0.9), 3);
                    if (abs($candidate - $center) >= 0.25) {
                        $fake = $candidate;
                        break;
                    }
                }
                $fake ??= $center > 0.5 ? 0.15 : 0.85;
            }
            $layout[] = ['center' => $center, 'fake' => $fake];
        }
        return $layout;
    }
}
