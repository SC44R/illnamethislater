<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $this->call([
            ProcrastinatorTypeSeeder::class,
            CauseSeeder::class,
            DistractionSeeder::class,
            DiceBandSeeder::class,
            MinigameConfigSeeder::class,
            BalanceConfigSeeder::class,
        ]);
    }
}
