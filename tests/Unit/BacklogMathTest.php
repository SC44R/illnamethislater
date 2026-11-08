<?php

namespace Tests\Unit;

use App\Support\BacklogMath;
use PHPUnit\Framework\TestCase;

class BacklogMathTest extends TestCase
{
    public function test_no_tasks_means_no_penalty(): void
    {
        $this->assertSame(0, BacklogMath::penalty(0, 0, 5));
    }

    public function test_calm_over_two_pending_tasks_cancels_the_penalty(): void
    {
        $this->assertSame(0, BacklogMath::penalty(2, 0, 4));   // 2 pending, Calm 4 -> 2 - 2
        $this->assertSame(1, BacklogMath::penalty(3, 0, 4));
    }

    public function test_overdue_counts_double(): void
    {
        $this->assertSame(2, BacklogMath::penalty(0, 1, 0));
        $this->assertSame(3, BacklogMath::penalty(1, 1, 0));
    }

    public function test_penalty_is_capped_at_ten(): void
    {
        $this->assertSame(10, BacklogMath::penalty(30, 0, 0));
        $this->assertSame(7, BacklogMath::penalty(30, 0, 6));
    }

    public function test_penalty_is_never_negative(): void
    {
        $this->assertSame(0, BacklogMath::penalty(1, 0, 10));
    }
}
