<?php

namespace App\Support;

use Illuminate\Support\Facades\DB;

/**
 * Reads a game number. The database table balance_config wins, config/game.php is the fallback.
 * Keys use dots, for example "dice.hard_min" or "size.small.rounds".
 */
class Balance
{
    private static ?array $cache = null;

    public static function get(string $key, $default = null)
    {
        if (self::$cache === null) {
            try {
                self::$cache = DB::table('balance_config')->pluck('config_value', 'config_key')->all();
            } catch (\Throwable $e) {
                self::$cache = [];
            }
        }
        $value = self::$cache[$key] ?? config('game.' . $key, $default);
        return is_string($value) && is_numeric($value) ? $value + 0 : $value;
    }

    public static function flush(): void
    {
        self::$cache = null;
    }
}
