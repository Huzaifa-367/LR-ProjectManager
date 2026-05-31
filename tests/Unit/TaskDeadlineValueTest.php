<?php

namespace Tests\Unit;

use App\Support\TaskDeadlineValue;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class TaskDeadlineValueTest extends TestCase
{
    #[Test]
    public function it_treats_null_like_strings_as_null(): void
    {
        $this->assertNull(TaskDeadlineValue::normalize(null));
        $this->assertNull(TaskDeadlineValue::normalize(''));
        $this->assertNull(TaskDeadlineValue::normalize('null'));
        $this->assertNull(TaskDeadlineValue::normalize('NULL'));
        $this->assertNull(TaskDeadlineValue::normalize(' none '));
    }

    #[Test]
    public function it_normalizes_valid_datetime_strings(): void
    {
        $normalized = TaskDeadlineValue::normalize('2026-06-15T17:00');

        $this->assertSame('2026-06-15 17:00:00', $normalized);
    }

    #[Test]
    public function it_returns_null_for_unparseable_values(): void
    {
        $this->assertNull(TaskDeadlineValue::normalize('not-a-date'));
    }
}
