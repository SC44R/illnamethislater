<?php

namespace Tests\Unit;

use App\Support\DistractionPlanner;
use App\Support\SeededRandom;
use PHPUnit\Framework\TestCase;

class DistractionPlannerTest extends TestCase
{
    public function test_count_at_backlog_0_5_and_10(): void
    {
        $this->assertSame(4, DistractionPlanner::count(4, 0, 0));
        $this->assertSame(6, DistractionPlanner::count(4, 5, 0));    // 4 + floor(5/2)
        $this->assertSame(9, DistractionPlanner::count(4, 10, 0));   // 4 + 5
    }

    public function test_count_is_between_1_and_10(): void
    {
        $this->assertSame(1, DistractionPlanner::count(2, 0, -2));   // easy band on a small task
        $this->assertSame(10, DistractionPlanner::count(6, 10, 2));  // 6 + 5 + 2 = 13 -> 10
    }

    public function test_positions_stay_in_range_and_keep_the_minimum_gap(): void
    {
        foreach (range(2, 7) as $count) {
            for ($seed = 1; $seed <= 40; $seed++) {
                $p = DistractionPlanner::positions($count, 0.15, 0.90, 0.10, new SeededRandom($seed));
                $this->assertCount($count, $p);
                $this->assertGreaterThanOrEqual(0.15, $p[0]);
                $this->assertLessThanOrEqual(0.90, end($p));
                for ($i = 1; $i < $count; $i++) {
                    $this->assertGreaterThanOrEqual(0.0995, $p[$i] - $p[$i - 1], "count $count seed $seed");
                }
            }
        }
    }

    public function test_many_distractions_are_spread_as_evenly_as_possible(): void
    {
        $p = DistractionPlanner::positions(10, 0.15, 0.90, 0.10, new SeededRandom(3));
        $this->assertCount(10, $p);
        $this->assertLessThanOrEqual(0.90, end($p));
        $this->assertSame($p, array_values(array_unique($p)));   // all different
    }

    public function test_same_seed_gives_the_same_schedule(): void
    {
        $a = DistractionPlanner::positions(5, 0.15, 0.90, 0.10, new SeededRandom(42));
        $b = DistractionPlanner::positions(5, 0.15, 0.90, 0.10, new SeededRandom(42));
        $this->assertSame($a, $b);
    }

    public function test_window_grows_with_calm_and_is_capped(): void
    {
        $this->assertSame(3000, DistractionPlanner::window(3000, 0));
        $this->assertSame(3600, DistractionPlanner::window(3000, 3));
        $this->assertSame(5000, DistractionPlanner::window(3000, 50));   // extra capped at 2000
    }

    public function test_damage_is_never_below_one(): void
    {
        $this->assertSame(4, DistractionPlanner::damage(4, 0, 0));
        $this->assertSame(1, DistractionPlanner::damage(4, 0, 30));
        $this->assertSame(9, DistractionPlanner::damage(8, 4, 3));   // 8 + floor(4/2) - floor(3/3) = 9
    }

    public function test_max_tier_by_level(): void
    {
        $levels = [1 => 1, 2 => 2, 3 => 4];
        $this->assertSame(1, DistractionPlanner::maxTier(1, $levels));
        $this->assertSame(2, DistractionPlanner::maxTier(2, $levels));
        $this->assertSame(2, DistractionPlanner::maxTier(3, $levels));
        $this->assertSame(3, DistractionPlanner::maxTier(4, $levels));
    }
}
