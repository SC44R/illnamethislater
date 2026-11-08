<?php

/*
 | Default game numbers. The seeder copies these into the balance_config table,
 | and the game reads the database first (App\Support\Balance), so you can tune
 | the game by editing rows, without changing code.
 */
return [
    'dice' => [
        'hard_min' => 2, 'normal_min' => 8, 'easy_min' => 15, 'flow_min' => 20,
        'card_cap' => 8,          // maximum total from cards (cards arrive in Sprint 2)
        'pity_after' => 2,        // Slips in a row before the pity bonus
        'pity_bonus' => 4,
        'flow_per_day' => 1,
        'safe_first_roll' => 0,   // 1 = the first roll of the day cannot be a natural-1 Slip
    ],
    'size' => [
        'small'  => ['rounds' => 6,  'distractions' => 2, 'xp' => 20, 'start_cost' => 10],
        'medium' => ['rounds' => 10, 'distractions' => 4, 'xp' => 35, 'start_cost' => 15],
        'large'  => ['rounds' => 15, 'distractions' => 6, 'xp' => 60, 'start_cost' => 20],
    ],
    'tier' => [
        1 => ['window' => 3000, 'damage' => 4,  'min_level' => 1],
        2 => ['window' => 2500, 'damage' => 8,  'min_level' => 2],
        3 => ['window' => 2000, 'damage' => 12, 'min_level' => 4],
    ],
    'band' => [
        'easy'   => ['difficulty' => -1, 'distractions' => -2, 'focus' => 10],
        'normal' => ['difficulty' => 0,  'distractions' => 0,  'focus' => 0],
        'hard'   => ['difficulty' => 1,  'distractions' => 2,  'focus' => -10],
    ],
    'focus' => ['per_level' => 5, 'backlog_cost' => 2, 'min' => 30],
    'backlog' => ['cap' => 10],
    'reward' => ['flow_mult' => 0.5, 'slip_mult' => 0.25, 'fail_mult' => 0.25, 'star3' => 0.7, 'star2' => 0.4],
    'distraction' => ['start' => 0.15, 'end' => 0.90, 'min_gap' => 0.10, 'calm_ms' => 200, 'calm_cap_ms' => 2000, 'grace_ms' => 300],
    'beat_click' => ['perfect' => 10, 'good' => 6, 'combo_bonus' => 2, 'max_extra_rounds' => 3, 'points_per_round' => 10],
    'available_minigames' => ['beat_click'],   // Focus Tuner and Memory Match arrive in Sprint 2
];
