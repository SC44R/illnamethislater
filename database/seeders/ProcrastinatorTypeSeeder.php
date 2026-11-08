<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class ProcrastinatorTypeSeeder extends Seeder
{
    public function run(): void
    {
        $types = [
            ['avoider', 'Avoider', 'type_avoider.png', 80, 5, 5, 2, ['URGE'], 'Puts things off to feel better right now'],
            ['perfectionist', 'Perfectionist', 'type_perfectionist.png', 90, 6, 2, 4, ['PERFECTION'], 'Waits until it can be perfect'],
            ['crammer', 'Crammer', 'type_crammer.png', 70, 3, 3, 6, ['OVERWHELM', 'BODY'], 'Waits for the deadline to feel close'],
            ['daydreamer', 'Daydreamer', 'type_daydreamer.png', 80, 4, 6, 2, ['SCREEN', 'SPACE'], 'Gets pulled away by screens and clutter'],
        ];
        foreach ($types as [$code, $name, $icon, $focus, $will, $calm, $drive, $weak, $desc]) {
            DB::table('procrastinator_types')->updateOrInsert(['code' => $code], [
                'name' => $name, 'icon' => $icon, 'base_focus' => $focus, 'base_willpower' => $will,
                'base_calm' => $calm, 'base_drive' => $drive, 'weak_tags' => json_encode($weak),
                'description' => $desc, 'created_at' => now(), 'updated_at' => now(),
            ]);
        }
    }
}
