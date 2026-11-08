<?php

namespace Tests\Unit;

use App\Services\StatsService;
use App\Support\Rewards;
use PHPUnit\Framework\TestCase;

class RewardsAndStatsTest extends TestCase
{
    public function test_stars_follow_the_focus_left(): void
    {
        $this->assertSame(3, Rewards::stars(70, 100));
        $this->assertSame(2, Rewards::stars(69, 100));
        $this->assertSame(2, Rewards::stars(40, 100));
        $this->assertSame(1, Rewards::stars(39, 100));
        $this->assertSame(1, Rewards::stars(1, 100));
        $this->assertSame(0, Rewards::stars(0, 100));
    }

    public function test_coins_are_half_the_xp(): void
    {
        $this->assertSame(10, Rewards::coins(20));
        $this->assertSame(18, Rewards::coins(35));   // 17.5 rounds up
    }

    private array $c = ['per_level' => 5, 'backlog_cost' => 2, 'min' => 30];

    public function test_starting_stats_of_the_avoider(): void
    {
        $s = StatsService::calculate(['focus' => 80, 'willpower' => 5, 'calm' => 5, 'drive' => 2], 1, 0, $this->c);
        $this->assertSame(80, $s['focus_max']);
        $this->assertSame(5, $s['willpower']);
        $this->assertSame(5, $s['calm']);
        $this->assertSame(2, $s['drive']);
    }

    public function test_focus_grows_with_level_and_shrinks_with_backlog(): void
    {
        $base = ['focus' => 80, 'willpower' => 5, 'calm' => 5, 'drive' => 2];
        $this->assertSame(125, StatsService::calculate($base, 10, 0, $this->c)['focus_max']);   // 80 + 45
        $this->assertSame(70, StatsService::calculate($base, 1, 5, $this->c)['focus_max']);     // 80 - 10
    }

    public function test_focus_never_drops_below_30(): void
    {
        $base = ['focus' => 70, 'willpower' => 3, 'calm' => 3, 'drive' => 6];
        $this->assertSame(50, StatsService::calculate($base, 1, 10, $this->c)['focus_max']);
        $tiny = ['focus' => 0, 'willpower' => 0, 'calm' => 0, 'drive' => 0];
        $this->assertSame(30, StatsService::calculate($tiny, 1, 10, $this->c)['focus_max']);
    }
}
