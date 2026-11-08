<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class CauseSeeder extends Seeder
{
    public function run(): void
    {
        $causes = [
            ['C1', 'Hard to start', 'URGE', 'You do not know the first step, or the task feels unpleasant to begin'],
            ['C2', 'Overwhelmed by a big task', 'OVERWHELM', 'The task is large or vague and feels impossible'],
            ['C3', 'Perfectionism / fear of failure', 'PERFECTION', 'Putting off because the result might not be good enough'],
            ['C4', 'Mood avoidance (boring or unpleasant task)', 'URGE', 'Delaying to feel better right now'],
            ['C5', 'Deadline feels far away', 'URGE', 'Low urgency, so other things win today'],
            ['C6', 'Phone and social temptation', 'PHONE', 'Messages, notifications, checking the phone'],
            ['C7', 'Screen and entertainment temptation', 'SCREEN', 'Videos, episodes, doomscrolling'],
            ['C8', 'Mind wandering', 'URGE', 'Stray thoughts pull attention away'],
            ['C9', 'Low energy, hunger, tiredness', 'BODY', 'The body is not ready to work'],
            ['C10', 'Noisy or messy space', 'SPACE', 'The environment makes work hard or invites tidying'],
            ['C11', 'Busywork instead of the real task', 'SPACE', 'Doing easier or more pleasant tasks to feel productive'],
        ];
        foreach ($causes as [$code, $name, $tag, $desc]) {
            DB::table('causes')->updateOrInsert(['code' => $code], [
                'name' => $name, 'tag' => $tag, 'icon' => 'cause_' . strtolower($code) . '.png',
                'description' => $desc, 'created_at' => now(), 'updated_at' => now(),
            ]);
        }
    }
}
