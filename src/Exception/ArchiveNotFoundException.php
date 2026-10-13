<?php

declare(strict_types=1);

namespace Forge\Archive\Exception;

use Forge\Archive\Support\Str;

final class ArchiveNotFoundException extends ArchiveException
{
    /**
     * @return self
     */
    public static function fromFile(string $file): self
    {
        return new self(Str::format('Archive (file) not found.', ['file' => $file]));
    }
}
