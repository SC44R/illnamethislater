<?php

namespace Tests\Unit;

use App\Support\FateDice;
use PHPUnit\Framework\TestCase;

class FateDiceTest extends TestCase
{
    private array $t = ['hard' => 2, 'normal' => 8, 'easy' => 15, 'flow' => 20];

    /** @dataProvider edges */
    public function test_band_edges(int $d20, int $total, string $band): void
    {
        $this->assertSame($band, FateDice::bandFor($d20, $total, $this->t));
    }

    public static function edges(): array
    {
        // d20 is a middle value (not 1 or 20) so only the total decides.
        return [
            'total 0'  => [5, 0, 'slip'],
            'total 1'  => [5, 1, 'slip'],
            'total 2'  => [5, 2, 'hard'],
            'total 7'  => [5, 7, 'hard'],
            'total 8'  => [5, 8, 'normal'],
            'total 14' => [5, 14, 'normal'],
            'total 15' => [5, 15, 'easy'],
            'total 19' => [5, 19, 'easy'],
            'total 20' => [5, 20, 'flow'],
            'total 30' => [5, 30, 'flow'],
            'natural 1 beats a huge total'  => [1, 40, 'slip'],
            'natural 20 beats a tiny total' => [20, -5, 'flow'],
        ];
    }

    public function test_odds_with_no_modifier(): void
    {
        $this->assertSame(['slip' => 5, 'hard' => 30, 'normal' => 35, 'easy' => 25, 'flow' => 5], FateDice::odds(0, $this->t));
    }

    public function test_odds_match_the_design_document_table(): void
    {
        $this->assertSame(['slip' => 20, 'hard' => 30, 'normal' => 35, 'easy' => 10, 'flow' => 5], FateDice::odds(-3, $this->t));
        $this->assertSame(['slip' => 5, 'hard' => 15, 'normal' => 35, 'easy' => 25, 'flow' => 20], FateDice::odds(3, $this->t));
        $this->assertSame(['slip' => 5, 'hard' => 0, 'normal' => 35, 'easy' => 25, 'flow' => 35], FateDice::odds(6, $this->t));
    }

    public function test_odds_always_add_up_to_100(): void
    {
        for ($m = -10; $m <= 15; $m++) {
            $this->assertSame(100, array_sum(FateDice::odds($m, $this->t)));
        }
    }

    public function test_cards_are_capped_at_plus_8(): void
    {
        $this->assertSame(8, FateDice::capCards(12, 8));
        $this->assertSame(8, FateDice::capCards(8, 8));
        $this->assertSame(5, FateDice::capCards(5, 8));
        $this->assertSame(-4, FateDice::capCards(-4, 8));
    }
}
