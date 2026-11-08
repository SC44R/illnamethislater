<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class DiceBandSeeder extends Seeder
{
    public function run(): void
    {
        $bands = [
            ['slip', null, ['minigame' => false, 'note' => 'auto lose, 25% effort XP, task stays pending']],
            ['hard', 2, ['difficulty' => 1, 'distractions' => 2, 'focus' => -10]],
            ['normal', 8, ['difficulty' => 0, 'distractions' => 0, 'focus' => 0]],
            ['easy', 15, ['difficulty' => -1, 'distractions' => -2, 'focus' => 10]],
            ['flow', 20, ['minigame' => false, 'note' => 'autocomplete: task cancelled, half XP, no stars']],
        ];
        foreach ($bands as [$band, $min, $effects]) {
            DB::table('dice_bands')->updateOrInsert(['band' => $band], [
                'min_total' => $min, 'effects' => json_encode($effects),
                'created_at' => now(), 'updated_at' => now(),
            ]);
        }
    }
}
