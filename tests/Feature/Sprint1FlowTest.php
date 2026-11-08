<?php

namespace Tests\Feature;

use App\Models\Cause;
use App\Models\Task;
use App\Models\TaskSession;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Walks through Sprint 1 from the server side:
 * pick a type, add a task, place it in a block, start, roll, play, and check the rules.
 * (Run with: php artisan test)
 */
class Sprint1FlowTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed();
        $this->user = User::create(['name' => 'Tester', 'email' => 'tester@example.com', 'password' => 'secret123']);
        $this->user->forceFill(['game_date' => '2026-01-10'])->save();
    }

    private function makeTask(array $override = []): Task
    {
        $this->actingAs($this->user)->postJson('/tasks', $override + [
            'title' => 'Math set', 'type' => 'practice', 'size' => 'small', 'difficulty' => 1,
            'due_date' => '2026-01-14', 'cause_id' => Cause::first()->id,
        ])->assertOk();
        return Task::latest('id')->first();
    }

    public function test_guests_cannot_use_the_game_routes(): void
    {
        $this->postJson('/tasks', [])->assertStatus(401);
    }

    public function test_choosing_a_type_sets_the_stats_and_works_only_once(): void
    {
        $this->actingAs($this->user)->postJson('/character', ['type_id' => 1])->assertOk();
        $state = $this->getJson('/state')->assertOk()->json();
        $this->assertSame(80, $state['stats']['focus_max']);       // Avoider
        $this->actingAs($this->user)->postJson('/character', ['type_id' => 2])->assertStatus(422);
    }

    public function test_task_needs_every_field(): void
    {
        $this->actingAs($this->user)->postJson('/tasks', ['title' => 'x'])->assertStatus(422);
    }

    public function test_a_task_goes_in_one_block_only(): void
    {
        $task = $this->makeTask();
        $this->postJson('/planner/blocks', ['block_index' => 1, 'kind' => 'task', 'task_id' => $task->id])->assertOk();
        $this->postJson('/planner/blocks', ['block_index' => 3, 'kind' => 'task', 'task_id' => $task->id])
            ->assertStatus(422)->assertJson(['error' => 'task_already_in_a_block']);
    }

    public function test_end_day_makes_missed_tasks_pending_and_moves_the_calendar(): void
    {
        $this->user->forceFill(['procrastinator_type_id' => 1])->save();
        $task = $this->makeTask();
        $this->postJson('/planner/blocks', ['block_index' => 1, 'kind' => 'task', 'task_id' => $task->id])->assertOk();
        $this->postJson('/planner/end-day')->assertOk()->assertJson(['new_pending' => 1, 'game_date' => '2026-01-11']);
        $this->assertSame('pending', $task->refresh()->status);
    }

    public function test_a_task_past_its_due_date_becomes_overdue(): void
    {
        $this->user->forceFill(['procrastinator_type_id' => 1])->save();
        $task = $this->makeTask(['due_date' => '2026-01-10']);
        $this->postJson('/planner/end-day')->assertOk();
        $this->assertSame('overdue', $task->refresh()->status);
    }

    public function test_a_session_can_be_rolled_once_and_the_band_is_valid(): void
    {
        $this->user->forceFill(['procrastinator_type_id' => 1])->save();
        $task = $this->makeTask();
        $this->postJson('/planner/blocks', ['block_index' => 1, 'kind' => 'task', 'task_id' => $task->id])->assertOk();

        $id = $this->postJson('/blocks/1/start')->assertOk()->json('session_id');
        $this->postJson("/sessions/$id/roll")->assertOk();
        $this->postJson("/sessions/$id/roll")->assertStatus(422)->assertJson(['error' => 'already_rolled']);

        $session = TaskSession::find($id);
        $this->assertContains($session->roll->band, ['slip', 'hard', 'normal', 'easy', 'flow']);
    }

    public function test_another_player_cannot_touch_my_session(): void
    {
        $this->user->forceFill(['procrastinator_type_id' => 1])->save();
        $task = $this->makeTask();
        $this->postJson('/planner/blocks', ['block_index' => 1, 'kind' => 'task', 'task_id' => $task->id]);
        $id = $this->postJson('/blocks/1/start')->json('session_id');

        $other = User::create(['name' => 'Other', 'email' => 'other@example.com', 'password' => 'secret123']);
        $this->actingAs($other)->postJson("/sessions/$id/roll")->assertStatus(403);
    }

    public function test_playing_a_session_to_the_end_clears_the_task_and_gives_xp(): void
    {
        $this->user->forceFill(['procrastinator_type_id' => 1])->save();
        $task = $this->makeTask();
        $this->postJson('/planner/blocks', ['block_index' => 1, 'kind' => 'task', 'task_id' => $task->id]);

        // Roll until the session actually plays (not Flow or Slip). Each roll uses a fresh session.
        $session = null;
        for ($i = 0; $i < 30 && !$session; $i++) {
            $id = $this->postJson('/blocks/1/start')->json('session_id');
            $r = $this->postJson("/sessions/$id/roll")->json();
            if ($r['outcome']['type'] === 'play') {
                $session = TaskSession::find($id);
            } else {
                // Flow or Slip ended it. Put the task and block back to try again.
                $task->refresh()->update(['status' => 'open']);
                $session?->block?->update(['used' => false]);
                TaskSession::find($id)->block?->update(['used' => false]);
            }
        }
        $this->assertNotNull($session, 'no playable roll in 30 tries');

        for ($n = 0; $n < 40 && TaskSession::find($session->id)->state === 'playing'; $n++) {
            $this->postJson("/sessions/{$session->id}/round", ['result' => 'PERFECT'])->assertOk();
        }
        $session->refresh();
        $this->assertSame('cleared', $session->state);
        $this->assertSame('done', $task->refresh()->status);
        $this->assertSame(20, $this->user->refresh()->xp);    // flat XP for a small task
    }

    public function test_a_round_after_the_session_ended_is_rejected(): void
    {
        $this->user->forceFill(['procrastinator_type_id' => 1])->save();
        $task = $this->makeTask();
        $session = TaskSession::create([
            'user_id' => $this->user->id, 'task_id' => $task->id, 'minigame' => 'beat_click', 'seed' => 5,
            'game_date' => '2026-01-10', 'block_index' => 1, 'state' => 'cleared',
        ]);
        $this->actingAs($this->user)->postJson("/sessions/{$session->id}/round", ['result' => 'PERFECT'])
            ->assertStatus(422)->assertJson(['error' => 'session_not_playing']);
    }
}
