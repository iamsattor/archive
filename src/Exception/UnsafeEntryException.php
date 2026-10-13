<?php

declare(strict_types=1);

namespace Forge\Archive\Exception;

use Forge\Archive\Support\Str;

final class UnsafeEntryException extends ArchiveException
{
    /**
     * @return self
     */
    public static function fromEntry(string $file, string $entry): self
    {
        return new self(Str::format('Archive (file) contains unsafe entry (entry).', ['file' => $file, 'entry' => $entry]));
    }

    /**
     * @return self
     */
    public static function fromType(string $file, string $entry): self
    {
        return new self(Str::format('Archive (file) contains unsupported entry (entry).', ['file' => $file, 'entry' => $entry]));
    }

    /**
     * @return self
     */
    public static function fromLink(string $file, string $link, string $target): self
    {
        return new self(Str::format('Archive (file) contains unsafe link (link) to (target).', ['file' => $file, 'link' => $link, 'target' => $target]));
    }
}
