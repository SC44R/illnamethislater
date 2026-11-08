<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

/** Copies every number in config/game.php into the balance_config table. */
class BalanceConfigSeeder extends Seeder
{
    public function run(): void
    {
        foreach ($this->flatten(config('game')) as $key => $value) {
            DB::table('balance_config')->updateOrInsert(['config_key' => $key], [
                'config_value' => (string) $value, 'created_at' => now(), 'updated_at' => now(),
            ]);
        }
    }

    private function flatten(array $array, string $prefix = ''): array
    {
        $out = [];
        foreach ($array as $key => $value) {
            if (is_array($value)) {
                if (array_is_list($value)) {
                    continue; // lists such as available_minigames stay in config/game.php
                }
                $out += $this->flatten($value, $prefix . $key . '.');
            } else {
                $out[$prefix . $key] = $value;
            }
        }
        return $out;
    }
}
