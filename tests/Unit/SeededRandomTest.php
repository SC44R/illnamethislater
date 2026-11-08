<?php

namespace Tests\Unit;

use App\Support\SeededRandom;
use PHPUnit\Framework\TestCase;

class SeededRandomTest extends TestCase
{
    public function test_same_seed_gives_same_numbers(): void
    {
        $a = new SeededRandom(912733);
        $b = new SeededRandom(912733);
        for ($i = 0; $i < 20; $i++) {
            $this->assertSame($a->nextFloat(), $b->nextFloat());
        }
    }

    public function test_different_seeds_give_different_numbers(): void
    {
        $this->assertNotSame((new SeededRandom(1))->nextFloat(), (new SeededRandom(2))->nextFloat());
    }

    public function test_matches_the_reference_mulberry32_values(): void
    {
        // Values produced by the reference JavaScript implementation of mulberry32.
        $r = new SeededRandom(1);
        $this->assertEqualsWithDelta(0.62707394, $r->nextFloat(), 0.00000001);
        $this->assertEqualsWithDelta(0.00273572, $r->nextFloat(), 0.00000001);
        $r = new SeededRandom(912733);
        $this->assertEqualsWithDelta(0.95640966, $r->nextFloat(), 0.00000001);
    }

    public function test_int_stays_inside_the_range(): void
    {
        $r = new SeededRandom(7);
        for ($i = 0; $i < 500; $i++) {
            $n = $r->int(1, 20);
            $this->assertGreaterThanOrEqual(1, $n);
            $this->assertLessThanOrEqual(20, $n);
        }
    }

    public function test_d20_reaches_every_face(): void
    {
        $r = new SeededRandom(99);
        $seen = [];
        for ($i = 0; $i < 3000; $i++) {
            $seen[$r->int(1, 20)] = true;
        }
        $this->assertCount(20, $seen);
    }

    public function test_weighted_index_prefers_heavier_weights(): void
    {
        $r = new SeededRandom(5);
        $counts = [0, 0];
        for ($i = 0; $i < 2000; $i++) {
            $counts[$r->weightedIndex([1, 3])]++;
        }
        $this->assertGreaterThan($counts[0] * 2, $counts[1]);
    }
}
