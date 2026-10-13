<?php

declare(strict_types=1);

namespace Forge\Archive\Tests\Support;

use Forge\Archive\Support\Str;
use PHPUnit\Framework\TestCase;

final class StrTest extends TestCase
{
    public function testFormatReplacesPlaceholders(): void
    {
        $this->assertSame('Archive release.zip not found.', Str::format('Archive (file) not found.', ['file' => 'release.zip']));
    }

    public function testFormatAcceptsIntegers(): void
    {
        $this->assertSame('Limit of 1024 bytes.', Str::format('Limit of (limit) bytes.', ['limit' => 1024]));
    }

    public function testFormatKeepsUnknownPlaceholders(): void
    {
        $this->assertSame('(missing) value', Str::format('(missing) (known)', ['known' => 'value']));
    }

    public function testFormatDoesNotReplacePlaceholdersInsideValues(): void
    {
        $this->assertSame(
            'Archive (entry).zip contains unsafe entry ../escape.',
            Str::format('Archive (file) contains unsafe entry (entry).', ['file' => '(entry).zip', 'entry' => '../escape']),
        );
    }
}
