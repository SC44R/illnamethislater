<?php

namespace Tests\Unit;

use App\Support\BeatClickRules;
use PHPUnit\Framework\TestCase;

class BeatClickRulesTest extends TestCase
{
    private array $c = ['perfect' => 10, 'good' => 6, 'combo_bonus' => 2, 'max_extra_rounds' => 3, 'points_per_round' => 10];

    private function fresh(int $target = 60): array
    {
        return ['points' => 0, 'target' => $target, 'combo' => 0, 'extra' => 0, 'bonus' => false];
    }

    public function test_perfect_and_good_give_points(): void
    {
        $this->assertSame(10, BeatClickRules::applyRound($this->fresh(), 'PERFECT', $this->c)['points']);
        $this->assertSame(6, BeatClickRules::applyRound($this->fresh(), 'GOOD', $this->c)['points']);
    }

    public function test_miss_gives_nothing_resets_combo_and_adds_a_round(): void
    {
        $s = $this->fresh();
        $s['combo'] = 2;
        $r = BeatClickRules::applyRound($s, 'MISS', $this->c);
        $this->assertSame(0, $r['points']);
        $this->assertSame(0, $r['combo']);
        $this->assertSame(70, $r['target']);
        $this->assertSame(1, $r['extra']);
    }

    public function test_extra_rounds_stop_at_three(): void
    {
        $s = $this->fresh();
        for ($i = 0; $i < 6; $i++) {
            $s = BeatClickRules::applyRound($s, 'MISS', $this->c);
        }
        $this->assertSame(3, $s['extra']);
        $this->assertSame(90, $s['target']);
    }

    public function test_three_perfects_in_a_row_give_a_bonus_on_the_next_hit(): void
    {
        $s = $this->fresh();
        foreach (['PERFECT', 'PERFECT', 'PERFECT'] as $r) {
            $s = BeatClickRules::applyRound($s, $r, $this->c);
        }
        $this->assertSame(30, $s['points']);
        $this->assertTrue($s['bonus']);
        $s = BeatClickRules::applyRound($s, 'GOOD', $this->c);
        $this->assertSame(38, $s['points']);   // 30 + 6 + 2 bonus
        $this->assertFalse($s['bonus']);
    }

    public function test_a_miss_cancels_the_waiting_bonus(): void
    {
        $s = $this->fresh();
        for ($i = 0; $i < 3; $i++) {
            $s = BeatClickRules::applyRound($s, 'PERFECT', $this->c);
        }
        $s = BeatClickRules::applyRound($s, 'MISS', $this->c);
        $this->assertFalse($s['bonus']);
    }

    public function test_cleared_when_points_reach_the_target(): void
    {
        $s = $this->fresh(20);
        $s = BeatClickRules::applyRound($s, 'PERFECT', $this->c);
        $this->assertFalse(BeatClickRules::isCleared($s));
        $s = BeatClickRules::applyRound($s, 'PERFECT', $this->c);
        $this->assertTrue(BeatClickRules::isCleared($s));
    }
}
