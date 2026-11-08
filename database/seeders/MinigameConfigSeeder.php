<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class MinigameConfigSeeder extends Seeder
{
    public function run(): void
    {
        // Beat Click difficulty table. Widths are fractions of the track, speed is track-lengths per second.
        $beatClick = [
            1 => ['zoneWidth' => 0.20, 'perfectWidth' => 0.06,  'speed' => 0.8, 'shift' => false, 'drift' => 0.0,  'fakeZones' => 0],
            2 => ['zoneWidth' => 0.15, 'perfectWidth' => 0.045, 'speed' => 1.0, 'shift' => true,  'drift' => 0.0,  'fakeZones' => 0],
            3 => ['zoneWidth' => 0.10, 'perfectWidth' => 0.03,  'speed' => 1.2, 'shift' => true,  'drift' => 0.04, 'fakeZones' => 1],
        ];
        foreach ($beatClick as $difficulty => $params) {
            DB::table('minigame_configs')->updateOrInsert(
                ['minigame' => 'beat_click', 'difficulty' => $difficulty],
                ['params' => json_encode($params), 'created_at' => now(), 'updated_at' => now()]
            );
        }
    }
}
