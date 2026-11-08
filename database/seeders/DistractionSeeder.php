<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class DistractionSeeder extends Seeder
{
    /** Which reflex prompt each tag uses. */
    private const PROMPTS = [
        'PHONE' => 'swipe', 'SOCIAL' => 'swipe', 'URGE' => 'hold', 'SCREEN' => 'hold',
        'PERFECTION' => 'triple_tap', 'OVERWHELM' => 'triple_tap', 'BODY' => 'tap', 'SPACE' => 'tap',
    ];

    public function run(): void
    {
        // code, name, tag, tier, enabled in Sprint 1, tip
        $rows = [
            ['phone_buzz', 'Phone Buzz', 'PHONE', 1, true, 'Put your phone out of reach, not just on silent. Making it harder to grab works better than willpower.'],
            ['just_one_episode', 'Just One Episode', 'SCREEN', 3, true, 'Decide your stopping point before you start. Use an if-then plan: if I finish this section, then one episode.'],
            ['later', 'I will Do It Later', 'URGE', 2, true, 'Try the 5-minute rule: start for just five minutes and see how it feels.'],
            ['noisy_room', 'Noisy Room', 'SPACE', 1, true, 'Use headphones or move to a quieter spot.'],
            ['group_chat', 'Group Chat', 'SOCIAL', 2, false, 'Check messages at set times instead of all the time. Mute the chat during a task.'],
            ['doomscroll', 'Doomscroll', 'SCREEN', 2, false, 'Close the tab or app. A website blocker adds friction.'],
            ['perfection_loop', 'Perfection Loop', 'PERFECTION', 3, false, 'Write down what good enough means before you start. Aim for a first draft.'],
            ['hungry_gremlin', 'Hungry Gremlin', 'BODY', 1, false, 'Eat and drink water before you start so hunger does not interrupt you.'],
            ['tidy_instead', 'Tidy-Instead', 'SPACE', 2, false, 'Clean for two minutes after the task, not before it.'],
            ['overwhelm_freeze', 'Overwhelm Freeze', 'OVERWHELM', 3, false, 'Pick one tiny first step. Break the task into pieces you can finish in minutes.'],
            ['sleepy_slump', 'Sleepy Slump', 'BODY', 2, false, 'Take a short walk or a brief break instead of forcing through it.'],
            ['daydream_drift', 'Daydream Drift', 'URGE', 1, false, 'Keep a parking-lot note: write the stray thought down and return to the task.'],
        ];
        foreach ($rows as [$code, $name, $tag, $tier, $enabled, $tip]) {
            DB::table('distractions')->updateOrInsert(['code' => $code], [
                'name' => $name, 'tag' => $tag, 'tier' => $tier, 'prompt' => self::PROMPTS[$tag],
                'prompt_count' => $tier === 3 ? 2 : 1,
                // the effect code is the tag, so each tag has one lingering effect in the browser
                'lingering_effect_code' => $tag, 'tip' => $tip,
                'icon' => 'creature_' . $code . '.png', 'enabled' => $enabled,
                'created_at' => now(), 'updated_at' => now(),
            ]);
        }
    }
}
