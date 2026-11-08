<?php

namespace App\Services;

use App\Exceptions\GameRuleException;
use App\Models\TaskSession;
use App\Support\Balance;

/** Handles the player's reaction to a distraction pop-up. */
class DismissService
{
    public function __construct(private MinigameService $minigames)
    {
    }

    /**
     * $success is what the browser reports (the reflex action was done in time).
     * The server also checks the time: slower than the window (plus a small grace) counts as a failure.
     */
    public function resolve(TaskSession $session, int $eventId, bool $success, int $elapsedMs): array
    {
        if ($session->state !== 'playing') {
            throw new GameRuleException('session_not_playing');
        }
        $event = $session->events()->where('id', $eventId)->first();
        if (!$event || $event->result !== 'pending') {
            throw new GameRuleException('event_not_available');
        }

        $grace = (int) Balance::get('distraction.grace_ms', 300);
        if ($success && $elapsedMs > $event->window_ms + $grace) {
            $success = false;
        }

        if ($success) {
            $event->update(['result' => 'dismissed', 'resolved_at' => now()]);
            return ['result' => 'dismissed', 'damage' => 0, 'effect' => null, 'session' => $this->minigames->summary($session)];
        }

        // Failed or timed out: Focus drops and the minigame gets a lingering effect.
        $focus = max(0, $session->focus_current - $event->damage);
        $event->update(['result' => 'failed', 'focus_lost' => $event->damage, 'resolved_at' => now()]);
        $session->update(['focus_current' => $focus]);

        $summary = $focus <= 0 ? $this->minigames->finish($session, 'failed') : $this->minigames->summary($session);

        return [
            'result' => 'failed',
            'damage' => (int) $event->damage,
            'effect' => $event->distraction->lingering_effect_code,
            'session' => $summary,
        ];
    }
}
