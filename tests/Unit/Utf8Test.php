<?php

namespace Tests\Unit;

use App\Support\Utf8;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class Utf8Test extends TestCase
{
    #[Test]
    public function it_truncates_multibyte_strings_without_corrupting_utf8(): void
    {
        $value = 'مرحبا بالعالم هذا نص طويل جدا';

        $truncated = Utf8::truncate($value, 12);

        $this->assertTrue(mb_check_encoding($truncated, 'UTF-8'));
        $this->assertSame('...', mb_substr($truncated, -3));
    }

    #[Test]
    public function it_sanitizes_invalid_utf8_sequences(): void
    {
        $invalid = "Valid prefix \xC3\x28 invalid suffix";

        $sanitized = Utf8::sanitize($invalid);

        $this->assertTrue(mb_check_encoding($sanitized, 'UTF-8'));
        $this->assertStringContainsString('Valid prefix', $sanitized);
    }

    #[Test]
    public function it_sanitizes_nested_payload_arrays(): void
    {
        $payload = [
            'project' => ['name' => "Name \xC3\x28"],
            'tasks' => [[
                'title' => 'Task',
                'meta' => ['icon' => '📅'],
            ]],
        ];

        $sanitized = Utf8::sanitizeRecursive($payload);

        $this->assertTrue(mb_check_encoding($sanitized['project']['name'], 'UTF-8'));
        $this->assertSame('📅', $sanitized['tasks'][0]['meta']['icon']);
        $this->assertIsString(json_encode($sanitized));
    }
}
