<?php

namespace App\Http\Controllers;

use App\Http\Requests\DismissRequest;
use App\Http\Requests\RoundRequest;
use App\Models\TaskSession;
use App\Services\DiceService;
use App\Services\DismissService;
use App\Services\MinigameService;
use App\Services\OutcomeService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

/** Thin controller: it checks the input and the owner, then asks a service to do the work. */
class SessionController extends Controller
{
    public function __construct(
        private MinigameService $minigames,
        private DiceService $dice,
        private OutcomeService $outcomes,
        private DismissService $dismissals
    ) {
    }

    /** POST /blocks/{index}/start : create a session and show the odds. */
    public function start(Request $request, int $index): JsonResponse
    {
        return $this->guard(function () use ($request, $index) {
            $session = $this->minigames->startSession($request->user(), $index);
            return ['session_id' => $session->id, 'preview' => $this->dice->preview($request->user())];
        });
    }

    /** POST /sessions/{session}/roll : the server rolls the d20 and applies the outcome. */
    public function roll(TaskSession $session): JsonResponse
    {
        Gate::authorize('play', $session);

        return $this->guard(function () use ($session) {
            if ($session->state !== 'prep' || $session->roll()->exists()) {
                throw new \App\Exceptions\GameRuleException('already_rolled');
            }
            $roll = $this->dice->roll($session);
            $outcome = $this->outcomes->apply($session, $roll);

            return [
                'roll' => ['d20' => $roll['d20'], 'modifiers' => $roll['modifiers'], 'total' => $roll['total'], 'band' => $roll['band'], 'notes' => $roll['notes']],
                'outcome' => $outcome,
            ];
        });
    }

    /** POST /sessions/{session}/round : one Beat Click result. */
    public function round(RoundRequest $request, TaskSession $session): JsonResponse
    {
        Gate::authorize('play', $session);

        return $this->guard(fn () => $this->minigames->applyRound($session, $request->validated()['result']));
    }

    /** POST /sessions/{session}/dismiss : the player reacted to a distraction pop-up. */
    public function dismiss(DismissRequest $request, TaskSession $session): JsonResponse
    {
        Gate::authorize('play', $session);
        $d = $request->validated();

        return $this->guard(fn () => $this->dismissals->resolve($session, (int) $d['event_id'], (bool) $d['success'], (int) $d['elapsed_ms']));
    }

    /** POST /sessions/{session}/abandon : quit a session. */
    public function abandon(TaskSession $session): JsonResponse
    {
        Gate::authorize('play', $session);

        return $this->guard(function () use ($session) {
            $this->minigames->abandon($session);
            return ['ok' => true];
        });
    }
}
